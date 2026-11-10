import { useEffect, useState, type FormEvent } from 'react';
import { GroupedBars, RankedBars } from './charts';
import { downloadCsv, stamp } from './csv';
import { rpc } from './supabase';

type Kind = 'barang_masuk' | 'barang_keluar' | 'pengambilan' | 'permintaan_bhp' | 'usulan_aset';
type Row = Record<string, string | number | null>;
type ReportPage = { stats: Record<string, number>; total: number; rows: Row[] };
type Periode = { summary: Record<string, number>; rows: Row[] };
type Tahunan = { months: { month: number; stock_in: number; stock_out: number }[]; top_items: Row[] };

const perPage = 15;
const number = new Intl.NumberFormat('id-ID');
const rupiah = new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', maximumFractionDigits: 0 });
const dateFormat = new Intl.DateTimeFormat('id-ID', { dateStyle: 'medium', timeZone: 'UTC' });
const short = new Intl.DateTimeFormat('id-ID', { day: '2-digit', month: '2-digit', year: 'numeric', timeZone: 'UTC' });
const monthNames = ['Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];
const perolehan = ['APBD Balikpapan', 'BLUD', 'Dropping IFK/DKK - APBD Balikpapan', 'Dropping IFK/DKK - APBN', 'HIBAH OPD LAIN BALIKPAPAN'];
const now = new Date(Date.now() + 7 * 3600_000);
const thisYear = now.getUTCFullYear();
const date = (value: unknown, format = dateFormat) => typeof value === 'string' && value ? format.format(new Date(value.slice(0, 10))) : '-';
const cap = (value: unknown) => typeof value === 'string' && value ? value[0].toUpperCase() + value.slice(1).replace(/_/g, ' ') : '-';

type Column = { label: string; value: (row: Row) => string | number | null };
const definitions: Record<Kind, { title: string; file: string; filters: ('item' | 'status' | 'tipe' | 'prioritas')[]; statuses?: string[];
  stats: [string, string][]; columns: Column[] }> = {
  barang_masuk: { title: 'Barang masuk', file: 'laporan-barang-masuk', filters: [], stats: [['total_transaksi', 'Transaksi'], ['total_qty', 'Total jumlah']],
    columns: [{ label: 'Tanggal', value: (r) => date(r.date, short) }, { label: 'Kode Barang', value: (r) => r.code }, { label: 'Nama Barang', value: (r) => r.name },
      { label: 'Kategori', value: (r) => r.category }, { label: 'Satuan', value: (r) => r.unit }, { label: 'Jumlah', value: (r) => r.quantity },
      { label: 'Catatan', value: (r) => r.notes ?? '-' }, { label: 'Petugas', value: (r) => r.petugas ?? '-' }] },
  barang_keluar: { title: 'Barang keluar', file: 'laporan-barang-keluar', filters: [], stats: [['total_transaksi', 'Transaksi'], ['total_qty', 'Total jumlah']],
    columns: [] },
  pengambilan: { title: 'Pengambilan barang', file: 'laporan-pengambilan-barang', filters: ['status'], statuses: ['pending', 'approved', 'completed', 'rejected', 'cancelled'],
    stats: [['total', 'Sesuai filter'], ['pending', 'Menunggu'], ['completed', 'Selesai'], ['rejected', 'Ditolak']],
    columns: [{ label: 'Tanggal', value: (r) => date(r.date, short) }, { label: 'Pemohon', value: (r) => r.pemohon }, { label: 'Nama Barang', value: (r) => r.barang },
      { label: 'Jumlah', value: (r) => r.quantity }, { label: 'Status', value: (r) => cap(r.status) }, { label: 'Diproses Oleh', value: (r) => r.processed_by ?? '-' },
      { label: 'Tanggal Proses', value: (r) => date(r.processed_at, short) }, { label: 'Catatan', value: (r) => r.notes ?? '-' }] },
  permintaan_bhp: { title: 'Permintaan BHP', file: 'laporan-permintaan-bhp', filters: ['status', 'tipe'],
    statuses: ['pending', 'diproses', 'disetujui', 'ditolak', 'revisi', 'tersedia', 'dibaca'],
    stats: [['total', 'Sesuai filter'], ['pending', 'Menunggu'], ['disetujui', 'Disetujui'], ['restok', 'Restok'], ['barang_baru', 'Barang baru']],
    columns: [{ label: 'Kode', value: (r) => r.code }, { label: 'Tanggal', value: (r) => date(r.date, short) }, { label: 'Pemohon', value: (r) => r.pemohon },
      { label: 'Tipe', value: (r) => r.tipe === 'barang_baru' ? 'Barang Baru' : 'Restok' }, { label: 'Nama Barang', value: (r) => r.barang },
      { label: 'Jumlah', value: (r) => r.jumlah }, { label: 'Prioritas', value: (r) => cap(r.prioritas) }, { label: 'Status', value: (r) => cap(r.status) },
      { label: 'Diverifikasi Oleh', value: (r) => r.verified_by ?? '-' }] },
  usulan_aset: { title: 'Usulan aset', file: 'laporan-usulan-aset', filters: ['status', 'prioritas'], statuses: ['pending', 'diproses', 'disetujui', 'ditolak', 'revisi'],
    stats: [['total', 'Sesuai filter'], ['pending', 'Menunggu'], ['disetujui', 'Disetujui'], ['ditolak', 'Ditolak']],
    columns: [{ label: 'Kode', value: (r) => r.code }, { label: 'Tanggal', value: (r) => date(r.date, short) }, { label: 'Pengusul', value: (r) => r.pengusul },
      { label: 'Ruangan', value: (r) => r.ruangan }, { label: 'Nama Barang', value: (r) => r.barang }, { label: 'Merk', value: (r) => r.merk ?? '-' },
      { label: 'Jumlah', value: (r) => r.jumlah }, { label: 'Estimasi Harga', value: (r) => r.estimasi_harga === null ? '-' : rupiah.format(Number(r.estimasi_harga)) },
      { label: 'Prioritas', value: (r) => cap(r.prioritas) }, { label: 'Status', value: (r) => cap(r.status) }, { label: 'Diverifikasi Oleh', value: (r) => r.verified_by ?? '-' }] },
};
definitions.barang_keluar.columns = definitions.barang_masuk.columns;

export function Laporan() {
  const [tab, setTab] = useState<Kind | 'periode' | 'tahunan'>('periode');
  return <section className="panel">
    <h2>Laporan</h2>
    <p>Laporan stok dan transaksi. Setiap laporan bisa diekspor ke CSV.</p>
    <div className="filters" role="group" aria-label="Jenis laporan">
      {([['periode', 'Bulanan / triwulan'], ['tahunan', 'Tahunan'], ...Object.entries(definitions).map(([key, value]) => [key, value.title])] as [typeof tab, string][])
        .map(([value, label]) => <button key={value} className="secondary" aria-pressed={tab === value} onClick={() => setTab(value)}>{label}</button>)}
    </div>
    {tab === 'periode' ? <PeriodeReport /> : tab === 'tahunan' ? <TahunanReport /> : <ListReport key={tab} kind={tab} />}
  </section>;
}

function ListReport({ kind }: { kind: Kind }) {
  const definition = definitions[kind];
  const [filters, setFilters] = useState<Record<string, string>>({});
  const [page, setPage] = useState(1);
  const [state, setState] = useState<{ data?: ReportPage; error?: string }>({});
  const [items, setItems] = useState<{ id: number; name: string }[]>([]);

  useEffect(() => {
    if (kind === 'barang_masuk' || kind === 'barang_keluar') {
      rpc<{ items: { id: number; name: string }[] }>('simbar_stock_options').then((value) => setItems(value.items)).catch(() => {});
    }
  }, [kind]);
  useEffect(() => {
    let active = true;
    setState({});
    rpc<ReportPage>('simbar_laporan', { report_kind: kind, filters, page_number: page })
      .then((data) => active && setState({ data })).catch((error: Error) => active && setState({ error: error.message }));
    return () => { active = false; };
  }, [kind, filters, page]);

  function apply(event: FormEvent<HTMLFormElement>) {
    event.preventDefault();
    setFilters(Object.fromEntries([...new FormData(event.currentTarget).entries()].map(([key, value]) => [key, String(value).trim()])));
    setPage(1);
  }

  async function exportCsv() {
    try {
      const all = await rpc<ReportPage>('simbar_laporan', { report_kind: kind, filters, page_number: 0 });
      downloadCsv(`${definition.file}-${stamp()}.csv`, ['No', ...definition.columns.map((column) => column.label)],
        all.rows.map((row, index) => [index + 1, ...definition.columns.map((column) => column.value(row))]));
    } catch (error) {
      setState((current) => ({ ...current, error: (error as Error).message }));
    }
  }

  const total = state.data?.total ?? 0;
  const last = Math.max(1, Math.ceil(total / perPage));
  return <>
    <form className="asset-filter" onSubmit={apply}>
      <label>Dari tanggal<input name="dari" type="date" /></label>
      <label>Sampai tanggal<input name="sampai" type="date" /></label>
      <label>Cari<input name="search" type="search" maxLength={100} placeholder="Nama barang, kode, atau pemohon" /></label>
      {(kind === 'barang_masuk' || kind === 'barang_keluar') && <label>Barang<select name="item" defaultValue=""><option value="">Semua barang</option>
        {items.map((item) => <option key={item.id} value={item.id}>{item.name}</option>)}</select></label>}
      {definition.filters.includes('status') && <label>Status<select name="status" defaultValue=""><option value="">Semua</option>
        {definition.statuses!.map((status) => <option key={status} value={status}>{cap(status)}</option>)}</select></label>}
      {definition.filters.includes('tipe') && <label>Tipe<select name="tipe" defaultValue=""><option value="">Semua</option>
        <option value="restok">Restok</option><option value="barang_baru">Barang baru</option></select></label>}
      {definition.filters.includes('prioritas') && <label>Prioritas<select name="prioritas" defaultValue=""><option value="">Semua</option>
        <option value="rendah">Rendah</option><option value="sedang">Sedang</option><option value="tinggi">Tinggi</option></select></label>}
      <div className="form-actions wide">
        <button className="primary" type="submit">Terapkan</button>
        <button className="secondary" type="reset" onClick={() => { setFilters({}); setPage(1); }}>Hapus filter</button>
        <button className="secondary" type="button" onClick={exportCsv}>Export CSV</button>
      </div>
    </form>
    {state.error ? <p className="notice" role="alert">{state.error}</p> : !state.data ? <p role="status">Memuat laporan…</p> : <>
      <dl className="metrics compact">{definition.stats.map(([key, label]) =>
        <div className="metric" key={key}><dt>{label}</dt><dd>{number.format(Number(state.data!.stats[key] ?? 0))}</dd></div>)}</dl>
      {!total ? <p role="status">Tidak ada data pada filter ini.</p> : <>
        <div className="inventory-scroll" tabIndex={0} role="region" aria-label={'Tabel laporan ' + definition.title.toLowerCase()}>
          <table>
            <thead><tr>{definition.columns.map((column) => <th key={column.label} scope="col">{column.label}</th>)}</tr></thead>
            <tbody>{state.data.rows.map((row, index) => <tr key={String(row.id ?? index)}>
              {definition.columns.map((column) => <td key={column.label}>{column.value(row)}</td>)}</tr>)}</tbody>
          </table>
        </div>
        <nav className="inventory-pages" aria-label="Halaman laporan">
          <button className="secondary" disabled={page <= 1} onClick={() => setPage(page - 1)}>Sebelumnya</button>
          <span>Halaman {number.format(page)} dari {number.format(last)}</span>
          <button className="secondary" disabled={page >= last} onClick={() => setPage(page + 1)}>Berikutnya</button>
        </nav>
      </>}
    </>}
  </>;
}

function PeriodeReport() {
  const [mode, setMode] = useState<'monthly' | 'quarterly'>('monthly');
  const [month, setMonth] = useState(now.getUTCMonth() + 1);
  const [quarter, setQuarter] = useState(Math.ceil((now.getUTCMonth() + 1) / 3));
  const [year, setYear] = useState(thisYear);
  const [source, setSource] = useState('');
  const [state, setState] = useState<{ data?: Periode; error?: string }>({});
  const startMonth = mode === 'quarterly' ? (quarter - 1) * 3 + 1 : month;
  const endMonth = mode === 'quarterly' ? quarter * 3 : month;
  const dari = `${year}-${String(startMonth).padStart(2, '0')}-01`;
  const sampai = new Date(Date.UTC(year, endMonth, 0)).toISOString().slice(0, 10);
  const title = mode === 'quarterly' ? `Triwulan ${['I', 'II', 'III', 'IV'][quarter - 1]} ${year}` : `${monthNames[month - 1]} ${year}`;

  useEffect(() => {
    let active = true;
    setState({});
    rpc<Periode>('simbar_laporan_periode', { dari, sampai, perolehan_filter: source || null })
      .then((data) => active && setState({ data })).catch((error: Error) => active && setState({ error: error.message }));
    return () => { active = false; };
  }, [dari, sampai, source]);

  function exportCsv() {
    if (!state.data) return;
    const rows = state.data.rows;
    downloadCsv(`laporan_${mode === 'quarterly' ? 'Triwulan_' + quarter : 'Bulan_' + month}_${year}.csv`,
      ['No', 'Nama Barang', 'Perolehan', 'Jenis Barang', 'Satuan', 'Harga Satuan', 'Masuk Periode', 'Keluar Periode', 'Stok', 'Jumlah (Rp)'],
      [...rows.map((row, index) => [index + 1, row.name, row.perolehan ?? '-', row.category, row.unit, Number(row.price), row.total_in, row.total_out,
        row.stock, Number(row.nilai)]),
      ['', '', '', '', '', 'TOTAL:', state.data.summary.total_stock_in, state.data.summary.total_stock_out, state.data.summary.total_stock, Number(state.data.summary.total_value)]]);
  }

  return <>
    <div className="asset-filter">
      <label>Jenis<select value={mode} onChange={(event) => setMode(event.target.value as typeof mode)}>
        <option value="monthly">Bulanan</option><option value="quarterly">Triwulan</option></select></label>
      {mode === 'monthly' ? <label>Bulan<select value={month} onChange={(event) => setMonth(Number(event.target.value))}>
        {monthNames.map((name, index) => <option key={name} value={index + 1}>{name}</option>)}</select></label>
        : <label>Triwulan<select value={quarter} onChange={(event) => setQuarter(Number(event.target.value))}>
          {['I (Jan - Mar)', 'II (Apr - Jun)', 'III (Jul - Sep)', 'IV (Okt - Des)'].map((name, index) => <option key={name} value={index + 1}>Triwulan {name}</option>)}</select></label>}
      <label>Tahun<select value={year} onChange={(event) => setYear(Number(event.target.value))}>
        {Array.from({ length: 6 }, (_, index) => thisYear - index).map((value) => <option key={value}>{value}</option>)}</select></label>
      <label>Perolehan<select value={source} onChange={(event) => setSource(event.target.value)}><option value="">Semua</option>
        {perolehan.map((value) => <option key={value}>{value}</option>)}</select></label>
      <div className="form-actions wide"><button className="secondary" onClick={exportCsv} disabled={!state.data}>Export CSV</button></div>
    </div>
    {state.error ? <p className="notice" role="alert">{state.error}</p> : !state.data ? <p role="status">Menghitung laporan…</p> : <>
      <h3 className="section-title">{title} · {date(dari)} – {date(sampai)}</h3>
      <dl className="metrics compact">
        <div className="metric"><dt>Barang masuk</dt><dd>{number.format(state.data.summary.total_stock_in)}</dd></div>
        <div className="metric"><dt>Barang keluar</dt><dd>{number.format(state.data.summary.total_stock_out)}</dd></div>
        <div className="metric"><dt>Stok saat ini</dt><dd>{number.format(state.data.summary.total_stock)}</dd></div>
        <div className="metric"><dt>Nilai stok</dt><dd className="small">{rupiah.format(Number(state.data.summary.total_value))}</dd></div>
      </dl>
      <div className="inventory-scroll" tabIndex={0} role="region" aria-label="Tabel laporan periode">
        <table>
          <thead><tr><th scope="col">Barang</th><th scope="col">Perolehan</th><th scope="col">Harga</th><th scope="col">Masuk</th>
            <th scope="col">Keluar</th><th scope="col">Stok</th><th scope="col">Nilai</th></tr></thead>
          <tbody>{state.data.rows.map((row) => <tr key={String(row.id)}>
            <th scope="row"><strong>{row.name}</strong><span>{row.code} · {row.category}</span></th>
            <td>{row.perolehan ?? '-'}</td><td className="num">{rupiah.format(Number(row.price))}</td>
            <td className="num">{number.format(Number(row.total_in))}</td><td className="num">{number.format(Number(row.total_out))}</td>
            <td className="num">{number.format(Number(row.stock))} {row.unit}</td><td className="num">{rupiah.format(Number(row.nilai))}</td>
          </tr>)}</tbody>
        </table>
      </div>
    </>}
  </>;
}

function TahunanReport() {
  const [year, setYear] = useState(thisYear);
  const [state, setState] = useState<{ data?: Tahunan; error?: string }>({});
  useEffect(() => {
    let active = true;
    setState({});
    rpc<Tahunan>('simbar_laporan_tahunan', { tahun: year })
      .then((data) => active && setState({ data })).catch((error: Error) => active && setState({ error: error.message }));
    return () => { active = false; };
  }, [year]);

  const months = state.data?.months ?? [];
  const totalIn = months.reduce((sum, month) => sum + Number(month.stock_in), 0);
  const totalOut = months.reduce((sum, month) => sum + Number(month.stock_out), 0);
  function exportCsv() {
    downloadCsv(`laporan_tahunan_${year}.csv`, ['Bulan', 'Barang Masuk', 'Barang Keluar', 'Selisih'],
      [...months.map((month) => [monthNames[month.month - 1], month.stock_in, month.stock_out, Number(month.stock_in) - Number(month.stock_out)]),
        ['TOTAL', totalIn, totalOut, totalIn - totalOut]]);
  }
  return <>
    <div className="filters">
      <label className="sr-only" htmlFor="laporan-tahun">Tahun</label>
      <select id="laporan-tahun" className="year-select" value={year} onChange={(event) => setYear(Number(event.target.value))}>
        {Array.from({ length: 6 }, (_, index) => thisYear - index).map((value) => <option key={value}>{value}</option>)}</select>
      <button className="secondary" onClick={exportCsv} disabled={!state.data}>Export CSV</button>
    </div>
    {state.error ? <p className="notice" role="alert">{state.error}</p> : !state.data ? <p role="status">Memuat laporan…</p> : <>
      <dl className="metrics compact">
        <div className="metric"><dt>Barang masuk {year}</dt><dd>{number.format(totalIn)}</dd></div>
        <div className="metric"><dt>Barang keluar {year}</dt><dd>{number.format(totalOut)}</dd></div>
        <div className="metric"><dt>Selisih</dt><dd>{number.format(totalIn - totalOut)}</dd></div>
      </dl>
      <GroupedBars title={`Barang masuk dan keluar per bulan, ${year}`} labels={months.map((month) => monthNames[month.month - 1].slice(0, 3))}
        series={[{ name: 'Masuk', values: months.map((month) => Number(month.stock_in)) }, { name: 'Keluar', values: months.map((month) => Number(month.stock_out)) }]} />
      <RankedBars title={`10 barang dengan barang masuk terbanyak, ${year}`}
        rows={state.data.top_items.filter((item) => Number(item.total_in) > 0).map((item) => ({ label: String(item.name), value: Number(item.total_in),
          note: `${item.unit ?? ''} (keluar ${number.format(Number(item.total_out))})` }))} />
    </>}
  </>;
}
