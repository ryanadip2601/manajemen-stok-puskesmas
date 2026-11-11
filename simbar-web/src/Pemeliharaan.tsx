import { useEffect, useState, type FormEvent } from 'react';
import { jenisLabels, kondisiLabels, pemeliharaanStatus } from './Aset';
import { downloadCsv, stamp } from './csv';
import { PhotoField, StoredPhoto } from './media';
import { rpc } from './supabase';

type Option = { id: number; name: string };
type AssetOption = Option & { code: string; roomId: number; kondisi: string };
type Options = { rooms: Option[]; assets: AssetOption[] };
type Row = {
  id: number; date: string; jenis: string; status: string; teknisi: string; biaya: number | null; deskripsi: string;
  tindakan: string | null; selesai: string | null; code: string; name: string; room: string; has_photo: boolean;
};
type Counts = { total: number; menunggu: number; proses: number; selesai: number; rusak: number };
type Page = { counts: Counts; total: number; rows: Row[] };
type Detail = {
  id: number; barang_aset_id: number; ruangan_id: number; tanggal_pemeliharaan: string; jenis_pemeliharaan: string;
  deskripsi_masalah: string; tindakan_perbaikan: string | null; status: string; teknisi: string; biaya: number | null;
  lampiran_foto: string | null; tanggal_selesai: string | null; catatan: string | null; kode_aset: string; nama_aset: string;
  kondisi_aset: string; kategori: string; ruangan: string; created_by_name: string | null;
};

const perPage = 15;
const number = new Intl.NumberFormat('id-ID');
const rupiah = new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', maximumFractionDigits: 0 });
const dateFormat = new Intl.DateTimeFormat('id-ID', { dateStyle: 'medium', timeZone: 'UTC' });
const today = () => new Date(Date.now() + 7 * 3600_000).toISOString().slice(0, 10);
const text = (data: FormData, name: string) => String(data.get(name) ?? '').trim();
const date = (value: string | null) => value ? dateFormat.format(new Date(value.slice(0, 10))) : '—';

function Message({ value }: { value: { text: string; ok: boolean } }) {
  return value.text ? <p className={value.ok ? 'success' : 'notice'} role={value.ok ? 'status' : 'alert'}>{value.text}</p> : null;
}

// Opened from Verifikasi: `openId` opens one report, `onBack` returns to the queue.
export function Pemeliharaan({ warehouse, openId, onBack, start, preset, backLabel = 'Kembali ke antrean' }: {
  warehouse: boolean; openId?: number; onBack?: () => void; start?: boolean; preset?: number; backLabel?: string;
}) {
  const empty = { search: '', ruangan: '', status: '', jenis: '', teknisi: '', dari: '', sampai: '' };
  const [options, setOptions] = useState<Options | null>(null);
  const [filters, setFilters] = useState(empty);
  const [page, setPage] = useState(1);
  const [version, setVersion] = useState(0);
  const [state, setState] = useState<{ data?: Page; error?: string }>({});
  const [view, setView] = useState<{ mode: 'detail' | 'edit'; detail: Detail } | { mode: 'new' } | null>(start ? { mode: 'new' } : null);
  const [message, setMessage] = useState({ text: '', ok: true });
  const [busy, setBusy] = useState(false);

  useEffect(() => {
    let active = true;
    rpc<Options>('simbar_pemeliharaan_options').then((value) => active && setOptions(value)).catch(() => {});
    return () => { active = false; };
  }, [version]);

  useEffect(() => {
    let active = true;
    setState({});
    rpc<Page>('simbar_pemeliharaan_list', { filters, page_number: page })
      .then((data) => active && setState({ data })).catch((error: Error) => active && setState({ error: error.message }));
    return () => { active = false; };
  }, [filters, page, version]);

  useEffect(() => { if (openId) open(openId); }, [openId]); // eslint-disable-line react-hooks/exhaustive-deps

  async function open(id: number) {
    setMessage({ text: '', ok: true });
    try {
      setView({ mode: 'detail', detail: await rpc<Detail>('simbar_pemeliharaan_get', { pemeliharaan_id: id }) });
    } catch (error) {
      setMessage({ text: (error as Error).message, ok: false });
    }
  }

  async function run(action: () => Promise<unknown>, success: string) {
    setBusy(true);
    try {
      await action();
      setMessage({ text: success, ok: true });
      setView(null);
      setVersion((value) => value + 1);
    } catch (error) {
      setMessage({ text: (error as Error).message, ok: false });
    } finally {
      setBusy(false);
    }
  }

  function applyFilters(event: FormEvent<HTMLFormElement>) {
    event.preventDefault();
    const data = new FormData(event.currentTarget);
    setFilters({ search: text(data, 'search'), ruangan: text(data, 'ruangan'), status: text(data, 'status'), jenis: text(data, 'jenis'),
      teknisi: text(data, 'teknisi'), dari: text(data, 'dari'), sampai: text(data, 'sampai') });
    setPage(1);
  }

  async function exportCsv() {
    try {
      const { rows } = await rpc<Page>('simbar_pemeliharaan_list', { filters, page_number: 0 });
      downloadCsv(`pemeliharaan_aset_${stamp()}.csv`,
        ['No', 'Tanggal', 'Kode Aset', 'Nama Aset', 'Ruangan', 'Jenis', 'Deskripsi Masalah', 'Tindakan', 'Status', 'Teknisi', 'Biaya', 'Tanggal Selesai'],
        rows.map((row, index) => [index + 1, date(row.date), row.code, row.name, row.room, jenisLabels[row.jenis], row.deskripsi,
          row.tindakan ?? '-', pemeliharaanStatus[row.status], row.teknisi, row.biaya ?? 0, date(row.selesai)]));
    } catch (error) {
      setMessage({ text: (error as Error).message, ok: false });
    }
  }

  function quickStatus(detail: Detail, status: string) {
    const tindakan = status === 'selesai' ? prompt('Tindakan perbaikan (opsional):', detail.tindakan_perbaikan ?? '') : null;
    if (status === 'selesai' && tindakan === null) return;
    run(() => rpc('simbar_pemeliharaan_status', { pemeliharaan_id: detail.id, new_status: status, tindakan }), 'Status berhasil diupdate.');
  }

  const counts = state.data?.counts;
  const total = state.data?.total ?? 0;
  const last = Math.max(1, Math.ceil(total / perPage));

  return <section className="panel">
    {onBack && <button className="secondary back-link" onClick={onBack}><i className="fas fa-arrow-left" aria-hidden="true" /> {backLabel}</button>}
    <div className="panel-head">
      <h2>Pemeliharaan aset</h2>
      {!view && <button className="primary" disabled={!options} onClick={() => { setMessage({ text: '', ok: true }); setView({ mode: 'new' }); }}>Laporkan pemeliharaan</button>}
    </div>
    <p>{warehouse ? 'Catat dan pantau pemeliharaan aset. Status selesai/rusak otomatis memperbarui kondisi aset.' : 'Laporkan kerusakan atau pemeliharaan aset di ruangan Anda.'}</p>
    {counts && <dl className="metrics compact">
      {(['menunggu', 'proses', 'selesai', 'rusak'] as const).map((key) =>
        <div className="metric" key={key}><dt>{pemeliharaanStatus[key]}</dt><dd>{number.format(counts[key])}</dd></div>)}
    </dl>}
    <Message value={message} />
    {view?.mode === 'detail' && <div className="record-form">
      <h3>{view.detail.nama_aset}</h3>
      <dl className="detail-list">
        <div><dt>Aset</dt><dd>{view.detail.kode_aset} · {view.detail.kategori}</dd></div>
        <div><dt>Ruangan</dt><dd>{view.detail.ruangan}</dd></div>
        <div><dt>Tanggal</dt><dd>{date(view.detail.tanggal_pemeliharaan)}</dd></div>
        <div><dt>Jenis</dt><dd>{jenisLabels[view.detail.jenis_pemeliharaan]}</dd></div>
        <div><dt>Status</dt><dd><span className={'badge status-' + view.detail.status}>{pemeliharaanStatus[view.detail.status]}</span></dd></div>
        <div><dt>Kondisi aset sekarang</dt><dd>{kondisiLabels[view.detail.kondisi_aset]}</dd></div>
        <div><dt>Teknisi</dt><dd>{view.detail.teknisi}</dd></div>
        <div><dt>Biaya</dt><dd>{view.detail.biaya === null ? '—' : rupiah.format(view.detail.biaya)}</dd></div>
        <div><dt>Tanggal selesai</dt><dd>{date(view.detail.tanggal_selesai)}</dd></div>
        <div><dt>Dilaporkan oleh</dt><dd>{view.detail.created_by_name ?? '—'}</dd></div>
        <div className="wide"><dt>Deskripsi masalah</dt><dd>{view.detail.deskripsi_masalah}</dd></div>
        {view.detail.tindakan_perbaikan && <div className="wide"><dt>Tindakan perbaikan</dt><dd>{view.detail.tindakan_perbaikan}</dd></div>}
        {view.detail.catatan && <div className="wide"><dt>Catatan</dt><dd>{view.detail.catatan}</dd></div>}
        {view.detail.lampiran_foto && <div className="wide"><dt>Lampiran foto</dt><dd>
          <StoredPhoto reference={view.detail.lampiran_foto} alt={'Lampiran ' + view.detail.nama_aset} /></dd></div>}
      </dl>
      <div className="form-actions wide">
        {warehouse && <>
          {view.detail.status !== 'proses' && <button className="secondary" disabled={busy} onClick={() => quickStatus(view.detail, 'proses')}>Tandai proses</button>}
          {view.detail.status !== 'selesai' && <button className="primary" disabled={busy} onClick={() => quickStatus(view.detail, 'selesai')}>Tandai selesai</button>}
          {view.detail.status !== 'rusak' && <button className="danger" disabled={busy} onClick={() => quickStatus(view.detail, 'rusak')}>Tandai rusak</button>}
          <button className="secondary" onClick={() => setView({ mode: 'edit', detail: view.detail })}>Ubah</button>
          <button className="danger" disabled={busy} onClick={() => {
            if (confirm('Hapus laporan pemeliharaan ini?')) run(() => rpc('simbar_pemeliharaan_delete', { pemeliharaan_id: view.detail.id }), 'Laporan pemeliharaan berhasil dihapus.');
          }}>Hapus</button>
        </>}
        <button className="secondary" onClick={() => setView(null)}>Tutup</button>
      </div>
    </div>}
    {view && view.mode !== 'detail' && options && <PemeliharaanForm options={options} detail={view.mode === 'edit' ? view.detail : null} busy={busy} preset={preset}
      onCancel={() => setView(null)} onSubmit={(form) => run(() => rpc('simbar_pemeliharaan_save', {
        pemeliharaan_id: view.mode === 'edit' ? view.detail.id : null, form,
      }), view.mode === 'edit' ? 'Laporan pemeliharaan berhasil diupdate.' : 'Laporan pemeliharaan berhasil dibuat.')} />}
    <form className="asset-filter" onSubmit={applyFilters}>
      <label className="wide">Cari<input name="search" type="search" maxLength={100} defaultValue={filters.search} placeholder="Aset, kode, teknisi, atau masalah" /></label>
      <label>Ruangan<select name="ruangan" defaultValue={filters.ruangan}><option value="">Semua</option>
        {options?.rooms.map((entry) => <option key={entry.id} value={entry.id}>{entry.name}</option>)}</select></label>
      <label>Status<select name="status" defaultValue={filters.status}><option value="">Semua</option>
        {Object.entries(pemeliharaanStatus).map(([key, label]) => <option key={key} value={key}>{label}</option>)}</select></label>
      <label>Jenis<select name="jenis" defaultValue={filters.jenis}><option value="">Semua</option>
        {Object.entries(jenisLabels).map(([key, label]) => <option key={key} value={key}>{label}</option>)}</select></label>
      <label>Teknisi<input name="teknisi" maxLength={100} defaultValue={filters.teknisi} /></label>
      <label>Dari<input name="dari" type="date" defaultValue={filters.dari} /></label>
      <label>Sampai<input name="sampai" type="date" defaultValue={filters.sampai} /></label>
      <div className="form-actions wide">
        <button className="primary" type="submit">Terapkan</button>
        <button className="secondary" type="reset" onClick={() => { setFilters(empty); setPage(1); }}>Hapus filter</button>
        {warehouse && <button className="secondary" type="button" onClick={exportCsv}>Export CSV</button>}
      </div>
    </form>
    {state.error ? <p className="notice" role="alert">{state.error}</p> : !state.data ? <p role="status">Memuat pemeliharaan…</p>
      : !total ? <p role="status">Belum ada laporan pemeliharaan.</p> : <>
        <div className="inventory-scroll" tabIndex={0} role="region" aria-label="Tabel pemeliharaan">
          <table>
            <thead><tr><th scope="col">Aset</th><th scope="col">Ruangan</th><th scope="col">Tanggal</th><th scope="col">Jenis</th>
              <th scope="col">Teknisi</th><th scope="col">Status</th><th scope="col"><span className="sr-only">Aksi</span></th></tr></thead>
            <tbody>{state.data.rows.map((row) => <tr key={row.id}>
              <th scope="row"><strong>{row.name}</strong><span>{row.code} · {row.deskripsi}</span></th>
              <td>{row.room}</td><td>{date(row.date)}</td><td>{jenisLabels[row.jenis]}</td><td>{row.teknisi}</td>
              <td><span className={'badge status-' + row.status}>{pemeliharaanStatus[row.status]}</span></td>
              <td><button className="secondary" onClick={() => open(row.id)}>Detail</button></td>
            </tr>)}</tbody>
          </table>
        </div>
        <nav className="inventory-pages" aria-label="Halaman pemeliharaan">
          <button className="secondary" disabled={page <= 1} onClick={() => setPage(page - 1)}>Sebelumnya</button>
          <span>Halaman {number.format(page)} dari {number.format(last)}</span>
          <button className="secondary" disabled={page >= last} onClick={() => setPage(page + 1)}>Berikutnya</button>
        </nav>
      </>}
  </section>;
}

function PemeliharaanForm({ options, detail, busy, onSubmit, onCancel, preset }: {
  options: Options; detail: Detail | null; busy: boolean; onSubmit: (form: Record<string, unknown>) => void; onCancel: () => void; preset?: number;
}) {
  const presetAset = !detail && preset ? options.assets.find((asset) => asset.id === preset) : undefined;
  const [room, setRoom] = useState(detail ? String(detail.ruangan_id) : presetAset ? String(presetAset.roomId) : '');
  const [photo, setPhoto] = useState<string | null>(detail?.lampiran_foto ?? null);
  const assets = options.assets.filter((asset) => String(asset.roomId) === room || asset.id === detail?.barang_aset_id);

  function submit(event: FormEvent<HTMLFormElement>) {
    event.preventDefault();
    const data = new FormData(event.currentTarget);
    onSubmit({
      barang_aset_id: Number(data.get('aset')), ruangan_id: Number(room), tanggal_pemeliharaan: text(data, 'tanggal'),
      jenis_pemeliharaan: text(data, 'jenis'), deskripsi_masalah: text(data, 'deskripsi'), tindakan_perbaikan: text(data, 'tindakan'),
      teknisi: text(data, 'teknisi'), biaya: text(data, 'biaya'), catatan: text(data, 'catatan'), lampiran_foto: photo,
      ...(detail ? { status: text(data, 'status'), tanggal_selesai: text(data, 'selesai') } : {}),
    });
  }

  return <form className="record-form" onSubmit={submit}>
    <h3>{detail ? 'Ubah pemeliharaan ' + detail.nama_aset : 'Laporan pemeliharaan baru'}</h3>
    <label>Ruangan<select required value={room} onChange={(event) => setRoom(event.target.value)}>
      <option value="" disabled>Pilih ruangan</option>{options.rooms.map((entry) => <option key={entry.id} value={entry.id}>{entry.name}</option>)}
    </select></label>
    <label>Barang aset<select name="aset" required defaultValue={detail?.barang_aset_id ?? (presetAset && String(presetAset.roomId) === room ? presetAset.id : '')} key={room}>
      <option value="" disabled>{room ? (assets.length ? 'Pilih aset' : 'Tidak ada aset di ruangan ini') : 'Pilih ruangan dulu'}</option>
      {assets.map((asset) => <option key={asset.id} value={asset.id}>{asset.name} ({asset.code}) — {kondisiLabels[asset.kondisi]}</option>)}
    </select></label>
    <label>Tanggal<input name="tanggal" type="date" required defaultValue={detail?.tanggal_pemeliharaan ?? today()} /></label>
    <label>Jenis<select name="jenis" required defaultValue={detail?.jenis_pemeliharaan ?? 'perbaikan'}>
      {Object.entries(jenisLabels).map(([key, label]) => <option key={key} value={key}>{label}</option>)}
    </select></label>
    <label>Teknisi<input name="teknisi" required maxLength={100} defaultValue={detail?.teknisi ?? ''} /></label>
    <label>Biaya (Rp)<input name="biaya" type="number" min="0" step="any" defaultValue={detail?.biaya ?? ''} /></label>
    {detail && <>
      <label>Status<select name="status" required defaultValue={detail.status}>
        {Object.entries(pemeliharaanStatus).map(([key, label]) => <option key={key} value={key}>{label}</option>)}
      </select></label>
      <label>Tanggal selesai<input name="selesai" type="date" defaultValue={detail.tanggal_selesai ?? ''} /></label>
    </>}
    <label className="wide">Deskripsi masalah<textarea name="deskripsi" rows={2} required defaultValue={detail?.deskripsi_masalah ?? ''} /></label>
    <label className="wide">Tindakan perbaikan<textarea name="tindakan" rows={2} defaultValue={detail?.tindakan_perbaikan ?? ''} /></label>
    <label className="wide">Catatan<textarea name="catatan" rows={2} defaultValue={detail?.catatan ?? ''} /></label>
    <PhotoField label="Lampiran foto" value={photo} onChange={setPhoto} />
    <div className="form-actions wide">
      <button className="primary" type="submit" disabled={busy}>{busy ? 'Menyimpan…' : 'Simpan'}</button>
      <button className="secondary" type="button" onClick={onCancel}>Batal</button>
    </div>
  </form>;
}
