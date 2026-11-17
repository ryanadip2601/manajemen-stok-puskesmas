import { useEffect, useMemo, useState, type FormEvent } from 'react';
import { createPortal } from 'react-dom';
import qrcode from 'qrcode-generator';
import { rpc } from './supabase';
import { PrintKop, printWhenReady, usePrintSettings } from './cetak';

// Nomor & label: (1) fix assets whose jumlah looks like a typo, (2) give every asset a nomor register,
// (3) print small labels (A4 sticker sheet, 3 x 8) with QR code, kode aset, nomor register, name and ruangan.
// Scanning the QR opens the asset in SIMBAR. "Cetak dengan kop" puts the report kop above the labels
// (plain paper or PDF, 3 x 7 per sheet) using the same kop settings as the reports.
type Suspicious = { id: number; kode: string; nama: string; jumlah: number; tahun: number | null; ruangan: string | null };
type RegisterStatus = { total: number; tanpa_register: number; ditahan: number; unit_tanpa_register: number };
type LabelAsset = { id: number; kode: string; nama: string; merk: string | null; jumlah: number; register: string | null;
  tahun: number | null; ruangan: string | null; kategori: string | null };
type Option = { id: number; name: string; active?: boolean; assets?: number };
type Label = { asset: LabelAsset; unit: string };
type Progress = { total: number; difoto: number; ruangan: { id: number; nama: string; total: number; difoto: number }[] };

const number = new Intl.NumberFormat('id-ID');
const PER_SHEET = 24;
const PER_SHEET_KOP = 21; // the kop takes the top row
// Labels always point at the main address, whichever address the sheet is printed from, so phones open the installed app.
const LABEL_ORIGIN = 'https://simbarkro.brey26.id';

// One label per physical unit: "0005-0007" becomes 0005, 0006, 0007; without a register the unit is "1/3".
function unitsOf(asset: LabelAsset): string[] {
  const range = asset.register?.match(/^(\d+)\s*-\s*(\d+)$/);
  if (range) {
    const [from, to] = [Number(range[1]), Number(range[2])];
    if (to >= from && to - from < 1000) return Array.from({ length: to - from + 1 }, (_, i) => String(from + i).padStart(range[1].length, '0'));
  }
  if (asset.register) return asset.jumlah > 1 ? Array.from({ length: asset.jumlah }, (_, i) => `${asset.register} (${i + 1}/${asset.jumlah})`) : [asset.register];
  return asset.jumlah > 1 ? Array.from({ length: asset.jumlah }, (_, i) => `unit ${i + 1}/${asset.jumlah}`) : [''];
}

function Qr({ text }: { text: string }) {
  const path = useMemo(() => {
    const qr = qrcode(0, 'M');
    qr.addData(text);
    qr.make();
    const size = qr.getModuleCount();
    let d = '';
    for (let r = 0; r < size; r++) for (let c = 0; c < size; c++) if (qr.isDark(r, c)) d += `M${c} ${r}h1v1h-1z`;
    return { d, size };
  }, [text]);
  return <svg className="label-qr" viewBox={`-1 -1 ${path.size + 2} ${path.size + 2}`} shapeRendering="crispEdges" aria-hidden="true">
    <rect x="-1" y="-1" width={path.size + 2} height={path.size + 2} fill="#fff" /><path d={path.d} fill="#000" /></svg>;
}

function LabelCard({ label, header }: { label: Label; header: string }) {
  const { asset, unit } = label;
  return <div className="label-card">
    <Qr text={`${LABEL_ORIGIN}/?aset=${asset.id}`} />
    <div className="label-text">
      <span className="label-org">{header}</span>
      <strong className="label-code">{asset.kode}</strong>
      {unit && <span className="label-reg">No. Reg {unit}</span>}
      <span className="label-name">{asset.nama}{asset.merk ? ' · ' + asset.merk : ''}</span>
      <span className="label-room">{asset.ruangan ?? '-'}{asset.tahun ? ' · ' + asset.tahun : ''}</span>
    </div>
  </div>;
}

export function AsetLabel() {
  const [suspicious, setSuspicious] = useState<Suspicious[] | null>(null);
  const [status, setStatus] = useState<RegisterStatus | null>(null);
  const [progress, setProgress] = useState<Progress | null>(null);
  const [showRooms, setShowRooms] = useState(false);
  const [options, setOptions] = useState<{ rooms: Option[]; categories: Option[] } | null>(null);
  const [filters, setFilters] = useState<Record<string, string>>({});
  const [assets, setAssets] = useState<LabelAsset[] | null>(null);
  const [picked, setPicked] = useState<Set<number>>(new Set());
  const [skip, setSkip] = useState(0);
  const [header, setHeader] = useState(() => { try { return localStorage.getItem('simbar-label-header') || 'UPTD PUSKESMAS KARANG REJO'; } catch { return 'UPTD PUSKESMAS KARANG REJO'; } });
  const [version, setVersion] = useState(0);
  const [message, setMessage] = useState({ text: '', ok: true });
  const [busy, setBusy] = useState(false);
  const [printing, setPrinting] = useState(false);
  // The kop is on unless this device turned it off (sticker sheets).
  const [withKop, setWithKop] = useState(() => { try { return localStorage.getItem('simbar-label-kop') !== 'tidak'; } catch { return true; } });
  const [printSettings] = usePrintSettings();
  const perSheet = withKop ? PER_SHEET_KOP : PER_SHEET;

  useEffect(() => {
    rpc<Suspicious[]>('simbar_aset_suspicious').then(setSuspicious).catch((error: Error) => setMessage({ text: error.message, ok: false }));
    rpc<RegisterStatus>('simbar_aset_register_status').then(setStatus).catch(() => {});
    rpc<Progress>('simbar_aset_foto_progress').then(setProgress).catch(() => {});
  }, [version]);
  useEffect(() => { rpc<{ rooms: Option[]; categories: Option[] }>('simbar_aset_options').then(setOptions).catch(() => {}); }, []);
  useEffect(() => {
    let live = true;
    setAssets(null);
    rpc<LabelAsset[]>('simbar_aset_labels', { filters }).then((rows) => { if (live) { setAssets(rows); setPicked(new Set(rows.map((r) => r.id))); } })
      .catch((error: Error) => { if (live) setMessage({ text: error.message, ok: false }); });
    return () => { live = false; };
  }, [filters, version]);
  useEffect(() => {
    if (!printing) return;
    // The sheets sit directly under <body> while printing, so the A4 portrait label page applies and the app is hidden.
    document.body.classList.add('printing-labels');
    const done = () => setPrinting(false);
    window.addEventListener('afterprint', done, { once: true });
    // With the kop, wait for its logos before opening the preview.
    const cancel = printWhenReady('.label-print img');
    return () => { cancel(); window.removeEventListener('afterprint', done); document.body.classList.remove('printing-labels'); };
  }, [printing]);

  async function run(action: () => Promise<unknown>, text: (result: unknown) => string) {
    setBusy(true);
    try {
      const result = await action();
      setMessage({ text: text(result), ok: true });
      setVersion((v) => v + 1);
    } catch (error) {
      setMessage({ text: (error as Error).message, ok: false });
    } finally {
      setBusy(false);
    }
  }
  function fix(event: FormEvent<HTMLFormElement>, row: Suspicious) {
    event.preventDefault();
    const data = new FormData(event.currentTarget);
    const tahun = String(data.get('tahun') ?? '').trim();
    run(() => rpc('simbar_aset_fix_jumlah', { aset_id: row.id, new_jumlah: Number(data.get('jumlah')), new_tahun: tahun ? Number(tahun) : null }),
      () => `Jumlah ${row.kode} diperbaiki.`);
  }
  function apply(event: FormEvent<HTMLFormElement>) {
    event.preventDefault();
    setFilters(Object.fromEntries([...new FormData(event.currentTarget).entries()].map(([k, v]) => [k, String(v).trim()])));
  }
  const saveHeader = (value: string) => { setHeader(value); try { localStorage.setItem('simbar-label-header', value); } catch { /* private mode */ } };
  const saveKop = (on: boolean) => {
    setWithKop(on);
    setSkip((value) => Math.min(value, (on ? PER_SHEET_KOP : PER_SHEET) - 1));
    try { localStorage.setItem('simbar-label-kop', on ? 'ya' : 'tidak'); } catch { /* private mode */ }
  };

  const labels: Label[] = (assets ?? []).filter((a) => picked.has(a.id)).flatMap((asset) => unitsOf(asset).map((unit) => ({ asset, unit })));
  const sheets = Math.ceil((labels.length + skip) / perSheet);
  const allPicked = !!assets?.length && picked.size === assets.length;

  return <>
    {message.text && <p className={message.ok ? 'success' : 'notice'} role={message.ok ? 'status' : 'alert'}>{message.text}</p>}

    {!!suspicious?.length && <section className="panel">
      <h2><i className="fas fa-triangle-exclamation" aria-hidden="true" /> Periksa jumlah aset</h2>
      <p>{suspicious.length} aset tercatat dengan jumlah lebih dari 100. Kebanyakan tahun perolehan terketik di kolom jumlah.
        Perbaiki dulu; aset ini tidak diberi nomor dan tidak dicetak labelnya sampai jumlahnya wajar.</p>
      <div className="inventory-scroll" tabIndex={0} role="region" aria-label="Aset dengan jumlah mencurigakan">
        <table><thead><tr><th scope="col">Aset</th><th scope="col">Ruangan</th><th scope="col" className="num">Jumlah tercatat</th>
          <th scope="col">Perbaikan</th></tr></thead>
          <tbody>{suspicious.map((row) => <tr key={row.id}>
            <th scope="row"><strong>{row.nama}</strong><span>{row.kode}</span></th><td>{row.ruangan ?? '-'}</td>
            <td className="num">{number.format(row.jumlah)}</td>
            <td><form className="fix-row" onSubmit={(event) => fix(event, row)}>
              <label>Jumlah<input name="jumlah" type="number" min={1} max={10000} required defaultValue={1} /></label>
              <label>Tahun<input name="tahun" type="number" min={1950} max={2100}
                defaultValue={row.tahun ?? (row.jumlah >= 1950 && row.jumlah <= 2100 ? row.jumlah : '')} /></label>
              <button className="primary" type="submit" disabled={busy}>Simpan</button>
            </form></td>
          </tr>)}</tbody></table>
      </div>
    </section>}

    {progress && <section className="panel">
      <div className="panel-head">
        <h2><i className="fas fa-camera" aria-hidden="true" /> Foto aset</h2>
        <button className="secondary" onClick={() => setShowRooms(!showRooms)} aria-expanded={showRooms}>{showRooms ? 'Tutup' : 'Per ruangan'}</button>
      </div>
      <div className="foto-progress" role="img" aria-label={`${progress.difoto} dari ${progress.total} aset sudah difoto`}>
        <span style={{ width: `${progress.total ? (progress.difoto / progress.total) * 100 : 0}%` }} /></div>
      <p>{number.format(progress.difoto)} dari {number.format(progress.total)} aset sudah difoto. Cara cepat: tempel label, tekan
        <strong> Pindai label</strong> di menu Aset, pindai QR, lalu <strong>Ambil foto</strong> dan <strong>Pindai berikutnya</strong>.</p>
      {showRooms && <ul className="room-progress">{progress.ruangan.map((room) => <li key={room.id}>
        <span>{room.nama}</span>
        <span className="foto-progress small"><span style={{ width: `${(room.difoto / room.total) * 100}%` }} /></span>
        <span className={room.difoto === room.total ? 'done' : undefined}>{room.difoto}/{room.total}</span>
      </li>)}</ul>}
    </section>}

    <section className="panel">
      <h2><i className="fas fa-hashtag" aria-hidden="true" /> Nomor register</h2>
      {!status ? <p role="status">Memuat…</p> : status.tanpa_register > 0 ? <>
        <p>{number.format(status.tanpa_register)} aset ({number.format(status.unit_tanpa_register)} unit) belum punya nomor register.
          Nomor diberikan berurutan per kategori (0001, 0002, …); aset dengan beberapa unit mendapat rentang, misalnya 0005-0007.
          Nomor yang sudah ada tidak diubah.</p>
        <button className="primary" disabled={busy} onClick={() => {
          if (confirm(`Beri nomor register untuk ${status.tanpa_register} aset (${status.unit_tanpa_register} unit)?`)) {
            run(() => rpc<{ aset: number; unit: number }>('simbar_aset_register_assign'),
              (r) => `Nomor register diberikan untuk ${(r as { aset: number }).aset} aset (${(r as { unit: number }).unit} unit).`);
          }
        }}><i className="fas fa-wand-magic-sparkles" aria-hidden="true" /> Beri nomor otomatis</button>
      </> : <p role="status">Semua aset sudah punya nomor register{status.ditahan ? `, kecuali ${status.ditahan} aset yang jumlahnya perlu diperiksa` : ''}.</p>}
    </section>

    <section className="panel">
      <h2><i className="fas fa-tags" aria-hidden="true" /> Cetak label aset</h2>
      <p>Satu label untuk setiap unit. Kertas stiker A4 isi 24 (3 × 8, ukuran 64 × 34 mm), atau 21 per lembar bila dicetak dengan kop.
        Pindai QR dengan HP untuk membuka aset di SIMBAR.</p>
      <form className="asset-filter" onSubmit={apply}>
        <label>Ruangan<select name="ruangan" defaultValue=""><option value="">Semua ruangan</option>
          {/* Only rooms that hold assets: empty rooms and the system archive have nothing to label. */}
          {options?.rooms.filter((room) => (room.assets ?? 0) > 0).map((room) => <option key={room.id} value={room.id}>
            {room.name} ({number.format(room.assets ?? 0)}){room.active === false ? ' · nonaktif' : ''}</option>)}</select></label>
        <label>Kategori<select name="kategori" defaultValue=""><option value="">Semua kategori</option>
          {options?.categories.map((category) => <option key={category.id} value={category.id}>{category.name}</option>)}</select></label>
        <label>Cari<input name="search" type="search" maxLength={100} placeholder="Nama atau kode aset" /></label>
        <div className="form-actions wide"><button className="primary" type="submit">Tampilkan</button></div>
      </form>
      <div className="record-form label-options">
        <label>Tulisan atas label<input value={header} maxLength={40} onChange={(event) => saveHeader(event.target.value)} /></label>
        <label>Lewati label di awal lembar (kertas bekas)<input type="number" min={0} max={perSheet - 1} value={skip}
          onChange={(event) => setSkip(Math.max(0, Math.min(perSheet - 1, Number(event.target.value) || 0)))} /></label>
        <label className="inline wide"><input type="checkbox" checked={withKop} onChange={(event) => saveKop(event.target.checked)} />
          Cetak dengan kop Puskesmas di atas tiap lembar (kertas biasa / PDF, 3 × 7 label). Kop mengikuti pengaturan cetak di menu Laporan.</label>
      </div>
      {!assets ? <p role="status">Memuat aset…</p> : !assets.length ? <p role="status">Tidak ada aset pada filter ini.</p> : <>
        <div className="report-actions label-actions">
          <span>{number.format(picked.size)} aset dipilih · <strong>{number.format(labels.length)} label</strong> · {number.format(sheets)} lembar</span>
          <button className="secondary" onClick={() => setPicked(allPicked ? new Set() : new Set(assets.map((a) => a.id)))}>
            {allPicked ? 'Batal pilih semua' : 'Pilih semua'}</button>
          <button className="primary" disabled={!labels.length} onClick={() => setPrinting(true)}><i className="fas fa-print" aria-hidden="true" /> Cetak label</button>
        </div>
        <div className="inventory-scroll" tabIndex={0} role="region" aria-label="Aset untuk label">
          <table><thead><tr><th scope="col"><span className="sr-only">Pilih</span></th><th scope="col">Aset</th><th scope="col">Ruangan</th>
            <th scope="col">No. register</th><th scope="col" className="num">Label</th></tr></thead>
            <tbody>{assets.map((asset) => <tr key={asset.id}>
              <td><input type="checkbox" aria-label={'Pilih ' + asset.nama} checked={picked.has(asset.id)} onChange={() => {
                const next = new Set(picked); if (next.has(asset.id)) next.delete(asset.id); else next.add(asset.id); setPicked(next);
              }} /></td>
              <th scope="row"><strong>{asset.nama}</strong><span>{asset.kode}{asset.kategori ? ' · ' + asset.kategori : ''}</span></th>
              <td>{asset.ruangan ?? '-'}</td><td>{asset.register ?? <em className="muted">belum ada</em>}</td>
              <td className="num">{number.format(asset.jumlah)}</td>
            </tr>)}</tbody></table>
        </div>
        {labels.length > 0 && <>
          <h3 className="section-title">Contoh label</h3>
          <div className="label-preview">{labels.slice(0, 3).map((label, i) => <LabelCard key={i} label={label} header={header} />)}</div>
        </>}
      </>}
    </section>

    {printing && createPortal(<div className="label-print" aria-hidden="true">
      {Array.from({ length: sheets }, (_, sheet) => <div className={'label-sheet' + (withKop ? ' with-kop' : '')} key={sheet}>
        {withKop && <PrintKop settings={printSettings} />}
        {Array.from({ length: perSheet }, (_, slot) => {
          const index = sheet * perSheet + slot - skip;
          const label = index >= 0 ? labels[index] : undefined;
          return label ? <LabelCard key={slot} label={label} header={header} /> : <div key={slot} className="label-card empty" />;
        })}
      </div>)}
    </div>, document.body)}
  </>;
}
