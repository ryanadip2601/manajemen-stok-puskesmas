import { lazy, Suspense, useEffect, useState, type FormEvent } from 'react';
import { rpc } from './supabase';
import { exportReport } from './cetak';

// Mutasi aset: pindah ruangan, khusus barang aset (barang habis pakai hanya masuk/keluar gudang).
// Every move is kept per asset: dari, ke, tanggal, petugas and alasan.
const QrScanner = lazy(() => import('./QrScanner').then((module) => ({ default: module.QrScanner })));

type Room = { id: number; name: string; active: boolean };
type Asset = { id: number; code: string; name: string; merk: string | null; room: string };
type Move = {
  id: number; tanggal: string; aset_id: number; code: string; name: string; merk: string | null;
  dari: string; ke: string; alasan: string | null; petugas: string | null;
};
type MovePage = { total: number; rows: Move[] };

const perPage = 15;
const number = new Intl.NumberFormat('id-ID');
const dateFormat = new Intl.DateTimeFormat('id-ID', { dateStyle: 'medium', timeZone: 'UTC' });
const today = () => new Date(Date.now() + 7 * 3600_000).toISOString().slice(0, 10); // Asia/Jakarta
const text = (data: FormData, name: string) => String(data.get(name) ?? '').trim();
const day = (value: string) => dateFormat.format(new Date(value.slice(0, 10)));

// Riwayat pindah ruangan of one asset, for the asset detail.
export function AssetMoves({ asetId }: { asetId: number }) {
  const [state, setState] = useState<{ rows?: Move[]; error?: string }>({});
  useEffect(() => {
    let active = true;
    setState({});
    rpc<MovePage>('simbar_mutasi_aset_list', { filters: { aset: asetId }, page_number: 1 })
      .then((page) => active && setState({ rows: page.rows })).catch((error: Error) => active && setState({ error: error.message }));
    return () => { active = false; };
  }, [asetId]);
  if (state.error) return <>Riwayat belum bisa dimuat.</>;
  if (!state.rows) return <>Memuat…</>;
  if (!state.rows.length) return <>Belum pernah dipindah.</>;
  return <ul className="plain-list">{state.rows.map((row) => <li key={row.id}>
    {day(row.tanggal)} · {row.dari} → {row.ke}{row.petugas ? ' · ' + row.petugas : ''}
    {row.alasan && <span className="cell-note">{row.alasan}</span>}
  </li>)}</ul>;
}

export function MutasiAset() {
  const [rooms, setRooms] = useState<Room[]>([]);
  const [filter, setFilter] = useState({ search: '', ruangan: '', from: '', to: '', page: 1 });
  const [state, setState] = useState<{ data?: MovePage; error?: string }>({});
  const [version, setVersion] = useState(0);
  const [adding, setAdding] = useState(false);
  const [message, setMessage] = useState({ text: '', ok: true });

  useEffect(() => {
    let active = true;
    rpc<{ rooms: Room[] }>('simbar_aset_options').then((value) => active && setRooms(value.rooms)).catch(() => {});
    return () => { active = false; };
  }, []);

  const { page, ...filters } = filter;
  useEffect(() => {
    let active = true;
    setState({});
    rpc<MovePage>('simbar_mutasi_aset_list', { filters, page_number: page })
      .then((data) => active && setState({ data })).catch((error: Error) => active && setState({ error: error.message }));
    return () => { active = false; };
  }, [filter, version]); // eslint-disable-line react-hooks/exhaustive-deps

  function applyFilter(event: FormEvent<HTMLFormElement>) {
    event.preventDefault();
    const data = new FormData(event.currentTarget);
    setFilter({ search: text(data, 'search'), ruangan: text(data, 'ruangan'), from: text(data, 'from'), to: text(data, 'to'), page: 1 });
  }

  async function exportCsv() {
    try {
      const all = await rpc<MovePage>('simbar_mutasi_aset_list', { filters, page_number: 0 });
      exportReport({ file: `mutasi_aset`, title: 'LAPORAN MUTASI ASET (PINDAH RUANGAN)',
    header: ['No', 'Tanggal', 'Kode Aset', 'Nama Aset', 'Merk', 'Dari Ruangan', 'Ke Ruangan', 'Alasan', 'Petugas'],
    rows: all.rows.map((row, index) => [index + 1, row.tanggal.slice(0, 10), row.code, row.name, row.merk ?? '-', row.dari, row.ke, row.alasan ?? '-', row.petugas ?? '-']) });
    } catch (error) {
      setMessage({ text: (error as Error).message, ok: false });
    }
  }

  const total = state.data?.total ?? 0;
  const lastPage = Math.max(1, Math.ceil(total / perPage));
  const filtered = filter.search || filter.ruangan || filter.from || filter.to;

  return <section className="panel">
    <div className="panel-head">
      <h2>Pindah ruangan aset</h2>
      <button className="primary" onClick={() => { setMessage({ text: '', ok: true }); setAdding(true); }}>Catat pindah ruangan</button>
    </div>
    <p className="cell-note">Khusus barang aset. Barang habis pakai dicatat lewat tab Masuk dan Keluar.</p>
    {message.text && <p className={message.ok ? 'success' : 'notice'} role={message.ok ? 'status' : 'alert'}>{message.text}</p>}
    {adding && <MoveForm rooms={rooms.filter((room) => room.active)} onCancel={() => setAdding(false)} onSaved={(note) => {
      setAdding(false);
      setMessage({ text: note, ok: true });
      setFilter({ search: '', ruangan: '', from: '', to: '', page: 1 });
      setVersion((value) => value + 1);
    }} />}
    <form className="movement-filter" onSubmit={applyFilter} key={filtered ? 'filtered' : 'all'}>
      <label>Cari<input name="search" type="search" maxLength={100} defaultValue={filter.search} placeholder="Nama, kode aset atau alasan" /></label>
      <label>Ruangan (asal atau tujuan)<select name="ruangan" defaultValue={filter.ruangan}><option value="">Semua ruangan</option>
        {rooms.map((room) => <option key={room.id} value={room.id}>{room.name}</option>)}</select></label>
      <label>Dari<input name="from" type="date" defaultValue={filter.from} /></label>
      <label>Sampai<input name="to" type="date" defaultValue={filter.to} /></label>
      <div className="form-actions">
        <button className="primary" type="submit">Terapkan</button>
        {filtered && <button className="secondary" type="button" onClick={() => setFilter({ search: '', ruangan: '', from: '', to: '', page: 1 })}>Hapus filter</button>}
        {total > 0 && <button className="secondary" type="button" onClick={exportCsv}>Unduh / Cetak</button>}
      </div>
    </form>
    {state.error ? <p className="notice" role="alert">{state.error}</p> : !state.data ? <p role="status">Memuat riwayat…</p>
      : !total ? <p role="status">Belum ada riwayat pindah ruangan.</p> : <>
        <p role="status">{number.format(total)} perpindahan.</p>
        <div className="inventory-scroll" tabIndex={0} role="region" aria-label="Tabel pindah ruangan aset">
          <table>
            <thead><tr><th scope="col">Aset</th><th scope="col">Tanggal</th><th scope="col">Dari</th><th scope="col">Ke</th>
              <th scope="col">Alasan</th><th scope="col">Dicatat oleh</th></tr></thead>
            <tbody>{state.data.rows.map((row) => <tr key={row.id}>
              <th scope="row"><strong>{row.name}</strong><span>{row.code}{row.merk ? ' · ' + row.merk : ''}</span></th>
              <td>{day(row.tanggal)}</td><td>{row.dari}</td><td>{row.ke}</td>
              <td>{row.alasan ?? '—'}</td><td>{row.petugas ?? '—'}</td>
            </tr>)}</tbody>
          </table>
        </div>
        <nav className="inventory-pages" aria-label="Halaman pindah ruangan">
          <button className="secondary" disabled={filter.page <= 1} onClick={() => setFilter({ ...filter, page: filter.page - 1 })}>Sebelumnya</button>
          <span>Halaman {number.format(filter.page)} dari {number.format(lastPage)}</span>
          <button className="secondary" disabled={filter.page >= lastPage} onClick={() => setFilter({ ...filter, page: filter.page + 1 })}>Berikutnya</button>
        </nav>
      </>}
  </section>;
}

// Pick one or more assets (search, filter by current room, or scan the label), then the room they move to.
function MoveForm({ rooms, onSaved, onCancel }: { rooms: Room[]; onSaved: (note: string) => void; onCancel: () => void }) {
  const [query, setQuery] = useState({ search: '', ruangan: '' });
  const [found, setFound] = useState<{ rows?: Asset[]; total?: number; error?: string }>({});
  const [chosen, setChosen] = useState<Asset[]>([]);
  const [scanning, setScanning] = useState(false);
  const [busy, setBusy] = useState(false);
  const [problem, setProblem] = useState('');

  useEffect(() => {
    let active = true;
    setFound({});
    const timer = setTimeout(() => {
      rpc<{ total: number; rows: Asset[] }>('simbar_aset_list', { filters: { ...query, status: 'aktif' }, page_number: 1 })
        .then((page) => active && setFound({ rows: page.rows, total: page.total }))
        .catch((error: Error) => active && setFound({ error: error.message }));
    }, 300);
    return () => { active = false; clearTimeout(timer); };
  }, [query]);

  const has = (id: number) => chosen.some((asset) => asset.id === id);
  const toggle = (asset: Asset) => setChosen(has(asset.id) ? chosen.filter((entry) => entry.id !== asset.id) : [...chosen, asset]);

  async function scanned(id: number) {
    setScanning(false);
    try {
      const detail = await rpc<{ id: number; kode_aset: string; nama_aset: string; merk: string | null; ruangan: string }>('simbar_aset_get', { aset_id: id });
      setChosen((current) => current.some((asset) => asset.id === detail.id) ? current
        : [...current, { id: detail.id, code: detail.kode_aset, name: detail.nama_aset, merk: detail.merk, room: detail.ruangan }]);
    } catch (error) {
      setProblem((error as Error).message);
    }
  }

  async function save(event: FormEvent<HTMLFormElement>) {
    event.preventDefault();
    const data = new FormData(event.currentTarget);
    if (!chosen.length) { setProblem('Pilih minimal 1 aset terlebih dahulu.'); return; }
    setBusy(true);
    setProblem('');
    try {
      const result = await rpc<{ moved: number; skipped: number; room: string }>('simbar_mutasi_aset_move', {
        aset_ids: chosen.map((asset) => asset.id), target_room: Number(data.get('room')), moved_on: text(data, 'date'), reason: text(data, 'reason') || null,
      });
      onSaved(`${number.format(result.moved)} aset dipindahkan ke ${result.room}.`
        + (result.skipped ? ` ${number.format(result.skipped)} aset dilewati karena sudah di ruangan itu.` : ''));
    } catch (error) {
      setProblem((error as Error).message);
    } finally {
      setBusy(false);
    }
  }

  return <form className="record-form" onSubmit={save}>
    {scanning && <Suspense fallback={<div className="scanner"><p className="scanner-box" role="status">Membuka kamera…</p></div>}>
      <QrScanner onFound={scanned} onClose={() => setScanning(false)} /></Suspense>}
    <h3>Pindah ruangan baru</h3>
    {problem && <p className="notice wide" role="alert">{problem}</p>}
    <label>Cari aset<input type="search" maxLength={100} value={query.search} placeholder="Nama, kode atau merk"
      onChange={(event) => setQuery({ ...query, search: event.target.value })} /></label>
    <label>Ruangan asal<select value={query.ruangan} onChange={(event) => setQuery({ ...query, ruangan: event.target.value })}>
      <option value="">Semua ruangan</option>
      {rooms.map((room) => <option key={room.id} value={room.id}>{room.name}</option>)}</select></label>
    <div className="wide">
      {found.error ? <p className="notice" role="alert">{found.error}</p> : !found.rows ? <p role="status">Mencari aset…</p>
        : !found.rows.length ? <p role="status">Tidak ada aset yang cocok.</p> : <>
          <ul className="photo-list">{found.rows.map((asset) => <li key={asset.id}>
            <label className="inline"><input type="checkbox" checked={has(asset.id)} onChange={() => toggle(asset)} />
              <span><strong>{asset.name}</strong> · {asset.code}{asset.merk ? ' · ' + asset.merk : ''} · {asset.room}</span></label>
          </li>)}</ul>
          {(found.total ?? 0) > found.rows.length && <p className="cell-note">Menampilkan {found.rows.length} dari {number.format(found.total ?? 0)} aset. Persempit pencarian untuk melihat yang lain.</p>}
        </>}
      <button className="secondary" type="button" onClick={() => setScanning(true)}><i className="fas fa-qrcode" aria-hidden="true" /> Pindai label aset</button>
    </div>
    <div className="wide">
      <strong>{number.format(chosen.length)} aset dipilih</strong>
      {chosen.length > 0 && <ul className="photo-list">{chosen.map((asset) => <li key={asset.id}>
        <div><strong>{asset.name}</strong><span>{asset.code} · sekarang di {asset.room}</span></div>
        <button className="secondary" type="button" onClick={() => toggle(asset)}>Batal pilih</button>
      </li>)}</ul>}
    </div>
    <label>Ruangan tujuan<select name="room" required defaultValue=""><option value="" disabled>Pilih ruangan…</option>
      {rooms.map((room) => <option key={room.id} value={room.id}>{room.name}</option>)}</select></label>
    <label>Tanggal pindah<input name="date" type="date" required max={today()} defaultValue={today()} /></label>
    <label className="wide">Alasan / keterangan<textarea name="reason" rows={2} maxLength={500} placeholder="Contoh: dipinjam untuk pelayanan imunisasi" /></label>
    <div className="form-actions wide">
      <button className="primary" type="submit" disabled={busy || !chosen.length}>{busy ? 'Menyimpan…' : 'Pindahkan'}</button>
      <button className="secondary" type="button" onClick={onCancel}>Batal</button>
    </div>
  </form>;
}
