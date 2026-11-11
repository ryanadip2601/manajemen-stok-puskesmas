import { useEffect, useState, type FormEvent } from 'react';
import { PhotoField, StoredPhoto } from './media';
import { exportAssetsCsv } from './Penyusutan';
import { rpc } from './supabase';

type Room = { id: number; name: string; lokasi: string | null; keterangan: string | null; active: boolean; placeholder: boolean; assets: number };
type Category = {
  id: number; name: string; golongan: string; kodeGolongan: string | null; umurManfaat: number; deskripsi: string | null;
  active: boolean; assets: number;
};
type Options = { rooms: Room[]; categories: Category[]; years: number[] };
type Row = {
  id: number; code: string; name: string; merk: string | null; jumlah: number; kondisi: string; status: string; repair: boolean;
  year: number | null; price: number | null; register: string | null; category: string; room: string; has_photo: boolean;
};
type Summary = { total: number; baik: number; rusak_ringan: number; rusak_berat: number; butuh_perbaikan: number; nilai: number };
type Page = { summary: Summary; total: number; rows: Row[] };
type Detail = Record<string, string | number | boolean | null> & {
  id: number; kode_aset: string; nama_aset: string; foto: string | null; kategori: string; ruangan: string;
  pemeliharaan: { id: number; tanggal: string; jenis: string; status: string; teknisi: string; deskripsi: string }[];
};

const perPage = 15;
const number = new Intl.NumberFormat('id-ID');
const rupiah = new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', maximumFractionDigits: 0 });
const dateFormat = new Intl.DateTimeFormat('id-ID', { dateStyle: 'medium', timeZone: 'UTC' });
export const kondisiLabels: Record<string, string> = { baik: 'Baik', rusak_ringan: 'Rusak ringan', rusak_berat: 'Rusak berat' };
const statusLabels: Record<string, string> = { aktif: 'Aktif', tidak_aktif: 'Tidak aktif', dihapuskan: 'Dihapuskan', dipindahkan: 'Dipindahkan' };
const golonganLabels: Record<string, string> = {
  tanah: 'Tanah', peralatan_mesin: 'Peralatan dan mesin', gedung_bangunan: 'Gedung dan bangunan',
  jalan_irigasi: 'Jalan, irigasi, dan jaringan', aset_tetap_lainnya: 'Aset tetap lainnya', kdp: 'Konstruksi dalam pengerjaan',
};
export const jenisLabels: Record<string, string> = { rutinitas: 'Rutinitas', perbaikan: 'Perbaikan', penggantian: 'Penggantian' };
export const pemeliharaanStatus: Record<string, string> = { menunggu: 'Menunggu', proses: 'Proses', selesai: 'Selesai', rusak: 'Rusak' };
const text = (data: FormData, name: string) => String(data.get(name) ?? '').trim();
const date = (value: unknown) => typeof value === 'string' && value ? dateFormat.format(new Date(value.slice(0, 10))) : '—';
const money = (value: unknown) => value === null || value === undefined || value === '' ? '—' : rupiah.format(Number(value));

function Message({ value }: { value: { text: string; ok: boolean } }) {
  return value.text ? <p className={value.ok ? 'success' : 'notice'} role={value.ok ? 'status' : 'alert'}>{value.text}</p> : null;
}

function useOptions(version: number) {
  const [options, setOptions] = useState<Options | null>(null);
  useEffect(() => {
    let active = true;
    rpc<Options>('simbar_aset_options').then((value) => active && setOptions(value)).catch(() => {});
    return () => { active = false; };
  }, [version]);
  return options;
}

// `section` shows one part without the inner tab bar (the Aset menu has its own tabs); `onReport` adds "Laporkan rusak".
export function Assets({ warehouse, section, onReport }: { warehouse: boolean; section?: 'list' | 'rooms' | 'categories'; onReport?: (asetId: number) => void }) {
  const [chosen, setTab] = useState<'list' | 'rooms' | 'categories'>('list');
  const tab = section ?? chosen;
  const [version, setVersion] = useState(0);
  const options = useOptions(version);
  const refresh = () => setVersion((value) => value + 1);
  return <>
    {warehouse && !section && <div className="filters" role="group" aria-label="Bagian aset">
      {([['list', 'Barang aset'], ['rooms', 'Ruangan'], ['categories', 'Kategori aset']] as const).map(([value, label]) =>
        <button key={value} className="secondary" aria-pressed={tab === value} onClick={() => setTab(value)}>{label}</button>)}
    </div>}
    {!options ? <p role="status">Memuat data aset…</p>
      : tab === 'rooms' ? <Rooms options={options} refresh={refresh} />
      : tab === 'categories' ? <Categories options={options} refresh={refresh} />
      : <AssetList options={options} warehouse={warehouse} refresh={refresh} onReport={onReport} />}
  </>;
}

function AssetList({ options, warehouse, refresh, onReport }: { options: Options; warehouse: boolean; refresh: () => void; onReport?: (asetId: number) => void }) {
  const empty = { search: '', kategori: '', ruangan: '', kondisi: '', status: '', tahun: '', butuh_perbaikan: false };
  const [filters, setFilters] = useState(empty);
  const [page, setPage] = useState(1);
  const [version, setVersion] = useState(0);
  const [state, setState] = useState<{ data?: Page; error?: string }>({});
  const [selected, setSelected] = useState<Set<number>>(new Set());
  const [view, setView] = useState<{ mode: 'detail' | 'edit'; detail: Detail } | { mode: 'new' } | null>(null);
  const [message, setMessage] = useState({ text: '', ok: true });
  const [busy, setBusy] = useState(false);

  useEffect(() => {
    let active = true;
    setState({});
    rpc<Page>('simbar_aset_list', { filters, page_number: page })
      .then((data) => active && setState({ data })).catch((error: Error) => active && setState({ error: error.message }));
    return () => { active = false; };
  }, [filters, page, version]);

  async function open(id: number) {
    setMessage({ text: '', ok: true });
    try {
      setView({ mode: 'detail', detail: await rpc<Detail>('simbar_aset_get', { aset_id: id }) });
    } catch (error) {
      setMessage({ text: (error as Error).message, ok: false });
    }
  }

  async function run(action: () => Promise<unknown>, success: (result: unknown) => string) {
    setBusy(true);
    try {
      const result = await action();
      setMessage({ text: success(result), ok: true });
      setView(null);
      setSelected(new Set());
      setVersion((value) => value + 1);
      refresh();
    } catch (error) {
      setMessage({ text: (error as Error).message, ok: false });
    } finally {
      setBusy(false);
    }
  }

  function applyFilters(event: FormEvent<HTMLFormElement>) {
    event.preventDefault();
    const data = new FormData(event.currentTarget);
    setFilters({ search: text(data, 'search'), kategori: text(data, 'kategori'), ruangan: text(data, 'ruangan'),
      kondisi: text(data, 'kondisi'), status: text(data, 'status'), tahun: text(data, 'tahun'), butuh_perbaikan: data.get('repair') === 'on' });
    setPage(1);
  }

  function bulkMove() {
    const room = (document.getElementById('bulk-room') as HTMLSelectElement | null)?.value;
    if (!room) return setMessage({ text: 'Pilih ruangan tujuan terlebih dahulu.', ok: false });
    run(() => rpc<{ moved: number; skipped: number; room: string }>('simbar_aset_move', { aset_ids: [...selected], target_room: Number(room) }),
      (result) => {
        const { moved, skipped, room: name } = result as { moved: number; skipped: number; room: string };
        return `Berhasil memindahkan ${moved} aset ke ruangan ${name}.` + (skipped ? ` ${skipped} aset sudah berada di ruangan tersebut.` : '');
      });
  }

  const summary = state.data?.summary;
  const total = state.data?.total ?? 0;
  const last = Math.max(1, Math.ceil(total / perPage));
  const activeRooms = options.rooms.filter((room) => room.active);

  return <section className="panel">
    <div className="panel-head">
      <h2>Barang aset</h2>
      {warehouse && !view && <button className="primary" onClick={() => { setMessage({ text: '', ok: true }); setView({ mode: 'new' }); }}>Tambah aset</button>}
    </div>
    {summary && <dl className="metrics compact">
      <div className="metric"><dt>Total aset</dt><dd>{number.format(summary.total)}</dd></div>
      <div className="metric"><dt>Baik</dt><dd>{number.format(summary.baik)}</dd></div>
      <div className="metric"><dt>Rusak (ringan/berat)</dt><dd>{number.format(summary.rusak_ringan)} / {number.format(summary.rusak_berat)}</dd></div>
      <div className="metric"><dt>Nilai perolehan</dt><dd className="small">{rupiah.format(summary.nilai)}</dd></div>
    </dl>}
    <Message value={message} />
    {view?.mode === 'detail' && <AssetDetail detail={view.detail} warehouse={warehouse} busy={busy}
      onEdit={() => setView({ mode: 'edit', detail: view.detail })} onClose={() => setView(null)}
      onReport={onReport ? () => onReport(view.detail.id) : undefined}
      onDelete={() => { if (confirm(`Hapus aset ${view.detail.nama_aset}? Riwayat pemeliharaannya ikut terhapus.`)) {
        run(() => rpc('simbar_aset_delete', { aset_ids: [view.detail.id] }), () => 'Barang aset berhasil dihapus.');
      } }} />}
    {view && view.mode !== 'detail' && <AssetForm options={options} detail={view.mode === 'edit' ? view.detail : null} busy={busy}
      onCancel={() => setView(null)}
      onSubmit={(form) => run(() => rpc('simbar_aset_save', { aset_id: view.mode === 'edit' ? view.detail.id : null, form }),
        () => view.mode === 'edit' ? 'Barang aset berhasil diupdate.' : 'Barang aset berhasil ditambahkan.')} />}
    <form className="asset-filter" onSubmit={applyFilters}>
      <label className="wide">Cari<input name="search" type="search" maxLength={100} defaultValue={filters.search} placeholder="Nama, kode, merk, atau nomor register" /></label>
      <label>Kategori<select name="kategori" defaultValue={filters.kategori}><option value="">Semua</option>
        {options.categories.map((entry) => <option key={entry.id} value={entry.id}>{entry.name}</option>)}</select></label>
      <label>Ruangan<select name="ruangan" defaultValue={filters.ruangan}><option value="">Semua</option>
        {options.rooms.map((entry) => <option key={entry.id} value={entry.id}>{entry.name}{entry.active ? '' : ' (nonaktif)'}</option>)}</select></label>
      <label>Kondisi<select name="kondisi" defaultValue={filters.kondisi}><option value="">Semua</option>
        {Object.entries(kondisiLabels).map(([value, label]) => <option key={value} value={value}>{label}</option>)}</select></label>
      <label>Status<select name="status" defaultValue={filters.status}><option value="">Semua</option>
        {Object.entries(statusLabels).map(([value, label]) => <option key={value} value={value}>{label}</option>)}</select></label>
      <label>Tahun<select name="tahun" defaultValue={filters.tahun}><option value="">Semua</option>
        {options.years.map((year) => <option key={year} value={year}>{year}</option>)}</select></label>
      <label className="inline"><input type="checkbox" name="repair" defaultChecked={filters.butuh_perbaikan} /> Butuh perbaikan</label>
      <div className="form-actions wide">
        <button className="primary" type="submit">Terapkan</button>
        <button className="secondary" type="reset" onClick={() => { setFilters(empty); setPage(1); }}>Hapus filter</button>
        {warehouse && <button className="secondary" type="button" onClick={() => exportAssetsCsv(filters)
          .catch((error: Error) => setMessage({ text: error.message, ok: false }))}>Export CSV</button>}
      </div>
    </form>
    {warehouse && selected.size > 0 && <div className="bulk-bar" role="region" aria-label="Aksi massal">
      <strong>{number.format(selected.size)} aset dipilih</strong>
      <label className="sr-only" htmlFor="bulk-room">Ruangan tujuan</label>
      <select id="bulk-room" defaultValue=""><option value="" disabled>Pindah ke ruangan…</option>
        {activeRooms.map((room) => <option key={room.id} value={room.id}>{room.name}</option>)}</select>
      <button className="primary" disabled={busy} onClick={bulkMove}>Pindahkan</button>
      <button className="danger" disabled={busy} onClick={() => {
        if (confirm(`Hapus ${selected.size} aset beserta riwayat pemeliharaannya?`)) {
          run(() => rpc<number>('simbar_aset_delete', { aset_ids: [...selected] }), (count) => `Berhasil menghapus ${count} aset.`);
        }
      }}>Hapus</button>
      <button className="secondary" onClick={() => setSelected(new Set())}>Batal pilih</button>
    </div>}
    {state.error ? <p className="notice" role="alert">{state.error}</p> : !state.data ? <p role="status">Memuat aset…</p>
      : !total ? <p role="status">Tidak ada aset.</p> : <>
        <p role="status">{number.format(total)} aset ditemukan.</p>
        <div className="inventory-scroll" tabIndex={0} role="region" aria-label="Tabel barang aset">
          <table>
            <thead><tr>{warehouse && <th scope="col"><span className="sr-only">Pilih</span></th>}
              <th scope="col">Nama dan kode</th><th scope="col">Kategori</th><th scope="col">Ruangan</th>
              <th scope="col">Kondisi</th><th scope="col">Tahun</th><th scope="col">Nilai</th><th scope="col"><span className="sr-only">Aksi</span></th></tr></thead>
            <tbody>{state.data.rows.map((row) => <tr key={row.id}>
              {warehouse && <td><input type="checkbox" aria-label={'Pilih ' + row.name} checked={selected.has(row.id)} onChange={(event) => {
                const next = new Set(selected);
                if (event.target.checked) next.add(row.id); else next.delete(row.id);
                setSelected(next);
              }} /></td>}
              <th scope="row"><strong>{row.name}</strong><span>{row.code}{row.merk ? ' · ' + row.merk : ''}</span></th>
              <td>{row.category}</td><td>{row.room}</td>
              <td><span className={'badge kondisi-' + row.kondisi}>{kondisiLabels[row.kondisi]}</span>
                {row.repair && <span className="cell-note warn">Butuh perbaikan</span>}
                {row.status !== 'aktif' && <span className="cell-note">{statusLabels[row.status]}</span>}</td>
              <td>{row.year ?? '—'}</td><td>{money(row.price)}</td>
              <td><button className="secondary" onClick={() => open(row.id)}>Detail</button></td>
            </tr>)}</tbody>
          </table>
        </div>
        <nav className="inventory-pages" aria-label="Halaman aset">
          <button className="secondary" disabled={page <= 1} onClick={() => setPage(page - 1)}>Sebelumnya</button>
          <span>Halaman {number.format(page)} dari {number.format(last)}</span>
          <button className="secondary" disabled={page >= last} onClick={() => setPage(page + 1)}>Berikutnya</button>
        </nav>
      </>}
  </section>;
}

function AssetDetail({ detail, warehouse, busy, onEdit, onDelete, onClose, onReport }: {
  detail: Detail; warehouse: boolean; busy: boolean; onEdit: () => void; onDelete: () => void; onClose: () => void; onReport?: () => void;
}) {
  const fields: [string, React.ReactNode][] = [
    ['Kode aset', detail.kode_aset], ['Kategori', `${detail.kategori} · ${golonganLabels[String(detail.golongan)] ?? detail.golongan}`],
    ['Ruangan', detail.ruangan], ['Kondisi', kondisiLabels[String(detail.kondisi)] + (detail.butuh_perbaikan ? ' · butuh perbaikan' : '')],
    ['Status', statusLabels[String(detail.status_aset)]], ['Jumlah', number.format(Number(detail.jumlah))],
    ['Tanggal perolehan', date(detail.tanggal_masuk)], ['Tahun perolehan', detail.tahun_perolehan ?? '—'],
    ['Harga perolehan', money(detail.harga_perolehan)], ['Nilai residu', money(detail.nilai_residu)],
    ['Umur manfaat', `${detail.umur_manfaat_override ?? detail.umur_manfaat_kategori} tahun${detail.umur_manfaat_override !== null ? ' (khusus)' : ''}`],
    ['Sumber dana', detail.sumber_dana ?? '—'], ['Merk', detail.merk ?? '—'], ['Nomor register', detail.nomor_register ?? '—'],
    ['No. sertifikat', detail.no_sertifikat ?? '—'], ['Asal perolehan', detail.asal_perolehan ?? '—'],
    ['Ukuran/konstruksi', detail.ukuran_konstruksi ?? '—'], ['Dicatat oleh', detail.created_by_name ?? '—'],
  ];
  return <div className="record-form">
    <h3>{detail.nama_aset}</h3>
    <dl className="detail-list">
      {fields.map(([label, value]) => <div key={label}><dt>{label}</dt><dd>{value}</dd></div>)}
      {detail.spesifikasi && <div className="wide"><dt>Spesifikasi</dt><dd>{detail.spesifikasi}</dd></div>}
      {detail.keterangan && <div className="wide"><dt>Keterangan</dt><dd>{detail.keterangan}</dd></div>}
      {detail.foto && <div className="wide"><dt>Foto</dt><dd><StoredPhoto reference={detail.foto} alt={'Foto ' + detail.nama_aset} /></dd></div>}
      <div className="wide"><dt>Pemeliharaan terakhir</dt><dd>{detail.pemeliharaan.length ? <ul className="plain-list">
        {detail.pemeliharaan.map((entry) => <li key={entry.id}>{date(entry.tanggal)} · {jenisLabels[entry.jenis]} · {pemeliharaanStatus[entry.status]} · {entry.teknisi}
          <span className="cell-note">{entry.deskripsi}</span></li>)}
      </ul> : 'Belum ada.'}</dd></div>
    </dl>
    <div className="form-actions wide">
      {onReport && <button className="primary" onClick={onReport}><i className="fas fa-screwdriver-wrench" aria-hidden="true" /> Laporkan rusak</button>}
      {warehouse && <button className="primary" onClick={onEdit}>Ubah</button>}
      {warehouse && <button className="danger" disabled={busy} onClick={onDelete}>Hapus aset</button>}
      <button className="secondary" onClick={onClose}>Tutup</button>
    </div>
  </div>;
}

function AssetForm({ options, detail, busy, onSubmit, onCancel }: {
  options: Options; detail: Detail | null; busy: boolean; onSubmit: (form: Record<string, unknown>) => void; onCancel: () => void;
}) {
  const [photo, setPhoto] = useState<string | null>(detail?.foto ?? null);
  const [code, setCode] = useState(detail?.kode_aset ?? '');
  const value = (name: string) => detail?.[name] === null || detail?.[name] === undefined ? '' : String(detail[name]);

  async function suggest(kategori: string) {
    if (detail || !kategori) return;
    setCode(await rpc<string>('simbar_aset_next_code', { kategori_ref: Number(kategori) }).catch(() => code));
  }

  function submit(event: FormEvent<HTMLFormElement>) {
    event.preventDefault();
    const data = new FormData(event.currentTarget);
    const form: Record<string, unknown> = Object.fromEntries([...data.entries()].map(([name, entry]) => [name, String(entry).trim()]));
    form.kode_aset = code.trim();
    form.foto = photo;
    onSubmit(form);
  }

  return <form className="record-form" onSubmit={submit}>
    <h3>{detail ? 'Ubah ' + detail.nama_aset : 'Aset baru'}</h3>
    <label>Kategori aset<select name="kategori_aset_id" required defaultValue={value('kategori_aset_id')} onChange={(event) => suggest(event.target.value)}>
      <option value="" disabled>Pilih kategori</option>
      {options.categories.filter((entry) => entry.active || String(entry.id) === value('kategori_aset_id'))
        .map((entry) => <option key={entry.id} value={entry.id}>{entry.name}</option>)}
    </select></label>
    <label>Kode aset<input required maxLength={50} value={code} onChange={(event) => setCode(event.target.value)} placeholder="Pilih kategori untuk kode otomatis" /></label>
    <label className="wide">Nama aset<input name="nama_aset" required maxLength={255} defaultValue={value('nama_aset')} /></label>
    <label>Ruangan<select name="ruangan_id" required defaultValue={value('ruangan_id')}>
      <option value="" disabled>Pilih ruangan</option>
      {options.rooms.filter((entry) => entry.active || String(entry.id) === value('ruangan_id'))
        .map((entry) => <option key={entry.id} value={entry.id}>{entry.name}</option>)}
    </select></label>
    <label>Kondisi<select name="kondisi" required defaultValue={value('kondisi') || 'baik'}>
      {Object.entries(kondisiLabels).map(([key, label]) => <option key={key} value={key}>{label}</option>)}
    </select></label>
    <label>Tanggal perolehan<input name="tanggal_masuk" type="date" required defaultValue={value('tanggal_masuk')} /></label>
    <label>Tahun perolehan<input name="tahun_perolehan" type="number" min="1980" max="2100" defaultValue={value('tahun_perolehan')} placeholder="Otomatis dari tanggal" /></label>
    <label>Jumlah<input name="jumlah" type="number" min="1" step="1" required defaultValue={value('jumlah') || '1'} /></label>
    <label>Harga perolehan (Rp)<input name="harga_perolehan" type="number" min="0" step="any" required={!detail} defaultValue={value('harga_perolehan')} /></label>
    <label>Nilai residu (Rp)<input name="nilai_residu" type="number" min="0" step="any" defaultValue={value('nilai_residu') || '0'} /></label>
    <label>Umur manfaat khusus (tahun)<input name="umur_manfaat_override" type="number" min="0" step="1" defaultValue={value('umur_manfaat_override')} placeholder="Ikuti kategori" /></label>
    <label>Sumber dana<input name="sumber_dana" maxLength={100} defaultValue={value('sumber_dana')} /></label>
    <label>Merk<input name="merk" maxLength={100} defaultValue={value('merk')} /></label>
    <label>Nomor register<input name="nomor_register" maxLength={50} defaultValue={value('nomor_register')} /></label>
    <label>No. sertifikat<input name="no_sertifikat" maxLength={255} defaultValue={value('no_sertifikat')} /></label>
    <label>Asal perolehan<input name="asal_perolehan" maxLength={255} defaultValue={value('asal_perolehan')} /></label>
    <label>Ukuran/konstruksi<input name="ukuran_konstruksi" maxLength={255} defaultValue={value('ukuran_konstruksi')} /></label>
    <label className="wide">Spesifikasi<input name="spesifikasi" maxLength={255} defaultValue={value('spesifikasi')} /></label>
    <label className="wide">Keterangan<textarea name="keterangan" rows={2} defaultValue={value('keterangan')} /></label>
    <PhotoField label="Foto aset" value={photo} onChange={setPhoto} />
    <div className="form-actions wide">
      <button className="primary" type="submit" disabled={busy}>{busy ? 'Menyimpan…' : 'Simpan'}</button>
      <button className="secondary" type="button" onClick={onCancel}>Batal</button>
    </div>
  </form>;
}

function Rooms({ options, refresh }: { options: Options; refresh: () => void }) {
  const [editing, setEditing] = useState<Room | 'new' | null>(null);
  const [message, setMessage] = useState({ text: '', ok: true });
  const [busy, setBusy] = useState(false);

  async function run(action: () => Promise<unknown>, success: string) {
    setBusy(true);
    try {
      await action();
      setMessage({ text: success, ok: true });
      setEditing(null);
      refresh();
    } catch (error) {
      setMessage({ text: (error as Error).message, ok: false });
    } finally {
      setBusy(false);
    }
  }

  function save(event: FormEvent<HTMLFormElement>) {
    event.preventDefault();
    const data = new FormData(event.currentTarget);
    const room = editing === 'new' ? null : editing;
    run(() => rpc('simbar_ruangan_save', { room_id: room?.id ?? null, room_name: text(data, 'name'), room_lokasi: text(data, 'lokasi'),
      room_keterangan: text(data, 'keterangan'), room_active: room ? data.get('active') === 'on' : true }),
    room ? 'Ruangan berhasil diupdate.' : 'Ruangan berhasil ditambahkan.');
  }

  const room = editing === 'new' ? null : editing;
  return <section className="panel">
    <div className="panel-head"><h2>Ruangan</h2>
      {!editing && <button className="primary" onClick={() => setEditing('new')}>Tambah ruangan</button>}</div>
    <Message value={message} />
    {editing && <form className="record-form" onSubmit={save} key={room?.id ?? 'new'}>
      <h3>{room ? 'Ubah ' + room.name : 'Ruangan baru'}</h3>
      <label>Nama ruangan<input name="name" required maxLength={100} defaultValue={room?.name} /></label>
      <label>Lokasi<input name="lokasi" maxLength={255} defaultValue={room?.lokasi ?? ''} /></label>
      <label className="wide">Keterangan<textarea name="keterangan" rows={2} defaultValue={room?.keterangan ?? ''} /></label>
      {room && <label className="inline wide"><input type="checkbox" name="active" defaultChecked={room.active} /> Ruangan aktif</label>}
      <div className="form-actions wide">
        <button className="primary" type="submit" disabled={busy}>Simpan</button>
        <button className="secondary" type="button" onClick={() => setEditing(null)}>Batal</button>
      </div>
    </form>}
    <ul className="photo-list">{options.rooms.map((entry) => <li key={entry.id}>
      <div><strong>{entry.name}</strong><span>{number.format(entry.assets)} aset{entry.lokasi ? ' · ' + entry.lokasi : ''}
        {entry.active ? '' : ' · nonaktif'}{entry.placeholder ? ' · dari data lama' : ''}</span></div>
      <div className="form-actions">
        <button className="secondary" onClick={() => setEditing(entry)}>Ubah</button>
        <button className="secondary" disabled={busy || entry.assets > 0} title={entry.assets ? 'Masih memiliki aset' : undefined} onClick={() => {
          if (confirm(`Hapus ruangan ${entry.name}? Riwayat yang memakai ruangan ini dipindah ke "Arsip Ruangan Dihapus".`)) {
            run(() => rpc('simbar_ruangan_delete', { room_id: entry.id }), 'Ruangan berhasil dihapus.');
          }
        }}>Hapus</button>
      </div>
    </li>)}</ul>
  </section>;
}

function Categories({ options, refresh }: { options: Options; refresh: () => void }) {
  const [editing, setEditing] = useState<Category | 'new' | null>(null);
  const [message, setMessage] = useState({ text: '', ok: true });
  const [busy, setBusy] = useState(false);

  async function run(action: () => Promise<unknown>, success: string) {
    setBusy(true);
    try {
      await action();
      setMessage({ text: success, ok: true });
      setEditing(null);
      refresh();
    } catch (error) {
      setMessage({ text: (error as Error).message, ok: false });
    } finally {
      setBusy(false);
    }
  }

  function save(event: FormEvent<HTMLFormElement>) {
    event.preventDefault();
    const data = new FormData(event.currentTarget);
    const category = editing === 'new' ? null : editing;
    run(() => rpc('simbar_kategori_aset_save', { kategori_id: category?.id ?? null, form: {
      nama_kategori: text(data, 'name'), kode_golongan: text(data, 'kode'), golongan: text(data, 'golongan'),
      umur_manfaat: Number(data.get('umur')), deskripsi: text(data, 'deskripsi'), is_active: category ? data.get('active') === 'on' : true,
    } }), category ? 'Kategori aset berhasil diupdate.' : 'Kategori aset berhasil ditambahkan.');
  }

  const category = editing === 'new' ? null : editing;
  return <section className="panel">
    <div className="panel-head"><h2>Kategori aset</h2>
      {!editing && <button className="primary" onClick={() => setEditing('new')}>Tambah kategori</button>}</div>
    <p>Golongan tanah dan konstruksi dalam pengerjaan tidak disusutkan.</p>
    <Message value={message} />
    {editing && <form className="record-form" onSubmit={save} key={category?.id ?? 'new'}>
      <h3>{category ? 'Ubah ' + category.name : 'Kategori baru'}</h3>
      <label>Nama kategori<input name="name" required maxLength={100} defaultValue={category?.name} /></label>
      <label>Kode golongan<input name="kode" maxLength={20} defaultValue={category?.kodeGolongan ?? ''} /></label>
      <label>Golongan<select name="golongan" required defaultValue={category?.golongan ?? 'peralatan_mesin'}>
        {Object.entries(golonganLabels).map(([key, label]) => <option key={key} value={key}>{label}</option>)}
      </select></label>
      <label>Umur manfaat (tahun)<input name="umur" type="number" min="0" step="1" required defaultValue={category?.umurManfaat ?? 5} /></label>
      <label className="wide">Deskripsi<textarea name="deskripsi" rows={2} defaultValue={category?.deskripsi ?? ''} /></label>
      {category && <label className="inline wide"><input type="checkbox" name="active" defaultChecked={category.active} /> Kategori aktif</label>}
      <div className="form-actions wide">
        <button className="primary" type="submit" disabled={busy}>Simpan</button>
        <button className="secondary" type="button" onClick={() => setEditing(null)}>Batal</button>
      </div>
    </form>}
    <ul className="photo-list">{options.categories.map((entry) => <li key={entry.id}>
      <div><strong>{entry.name}</strong><span>{golonganLabels[entry.golongan]} · {entry.umurManfaat} tahun · {number.format(entry.assets)} aset
        {entry.active ? '' : ' · nonaktif'}</span></div>
      <div className="form-actions">
        <button className="secondary" onClick={() => setEditing(entry)}>Ubah</button>
        <button className="secondary" disabled={busy || entry.assets > 0} onClick={() => {
          if (confirm(`Hapus kategori ${entry.name}?`)) run(() => rpc('simbar_kategori_aset_delete', { kategori_id: entry.id }), 'Kategori aset berhasil dihapus.');
        }}>Hapus</button>
      </div>
    </li>)}</ul>
  </section>;
}
