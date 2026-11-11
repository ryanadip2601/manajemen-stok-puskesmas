import { useEffect, useState, type FormEvent, type ReactNode } from 'react';
import { GroupedBars } from './charts';
import { downloadCsv, stamp } from './csv';
import { rpc } from './supabase';
import { ItemPicker, type PickItem } from './ItemPicker';
import { BADGE, KINDS, STATUS, type QueueKind } from './Verifikasi';
import { downloadXlsx } from './xlsx';

// Laporan: Persediaan (saldo per periode), Mutasi (masuk + keluar), Pengajuan (all request kinds) and Kartu Stok.
// Every report shares one column list for the screen table, Excel, CSV and the printed page (kop + tanda tangan).
type Row = Record<string, string | number | null>;
type Cell = string | number | null;
type Column = { label: string; value: (row: Row, index: number) => Cell; num?: boolean; screen?: (row: Row) => ReactNode };
type Tab = 'persediaan' | 'mutasi' | 'pengajuan' | 'kartu';

const number = new Intl.NumberFormat('id-ID');
const rupiah = new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', maximumFractionDigits: 0 });
const longDate = new Intl.DateTimeFormat('id-ID', { dateStyle: 'long', timeZone: 'UTC' });
const short = new Intl.DateTimeFormat('id-ID', { day: '2-digit', month: '2-digit', year: 'numeric', timeZone: 'UTC' });
const monthNames = ['Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];
const perolehan = ['APBD Balikpapan', 'BLUD', 'Dropping IFK/DKK - APBD Balikpapan', 'Dropping IFK/DKK - APBN', 'HIBAH OPD LAIN BALIKPAPAN'];
const now = new Date(Date.now() + 7 * 3600_000); // Asia/Jakarta, like the database
const today = now.toISOString().slice(0, 10);
const thisYear = now.getUTCFullYear();
const perPage = 15;
const day = (value: unknown, format = short) => typeof value === 'string' && value ? format.format(new Date(value.slice(0, 10))) : '-';
const n = (value: unknown) => Number(value ?? 0);
const lastDay = (year: number, month: number) => new Date(Date.UTC(year, month, 0)).toISOString().slice(0, 10);
const range = (from: string, to: string) => from === to ? day(from, longDate) : `${day(from, longDate)} s.d. ${day(to, longDate)}`;
const show = (cell: Cell, num?: boolean) => cell === null || cell === '' ? '-' : num && typeof cell === 'number' ? number.format(cell) : cell;

// ---------- Kop and tanda tangan, set once per browser ----------
type PrintSettings = { kop: string; kota: string; kiriJabatan: string; kiriNama: string; kiriNip: string; kananJabatan: string; kananNama: string; kananNip: string };
const defaultPrint: PrintSettings = {
  kop: 'PEMERINTAH KOTA BALIKPAPAN\nDINAS KESEHATAN\nUPTD PUSKESMAS KARANG REJO', kota: 'Balikpapan',
  kiriJabatan: 'Mengetahui,\nKepala UPTD Puskesmas Karang Rejo', kiriNama: '', kiriNip: '',
  kananJabatan: 'Pengurus Barang', kananNama: '', kananNip: '',
};
function loadPrint(): PrintSettings {
  try { return { ...defaultPrint, ...JSON.parse(localStorage.getItem('simbar-cetak') ?? '{}') }; } catch { return defaultPrint; }
}

function PrintSettingsForm({ value, onChange }: { value: PrintSettings; onChange: (next: PrintSettings) => void }) {
  const field = (key: keyof PrintSettings, label: string, multi = false) => <label className={multi ? 'wide' : undefined}>{label}
    {multi ? <textarea rows={3} value={value[key]} onChange={(event) => onChange({ ...value, [key]: event.target.value })} />
      : <input value={value[key]} maxLength={120} onChange={(event) => onChange({ ...value, [key]: event.target.value })} />}</label>;
  return <details className="print-settings">
    <summary><i className="fas fa-print" aria-hidden="true" /> Pengaturan cetak: kop dan tanda tangan</summary>
    <div className="record-form">
      {field('kop', 'Kop (satu baris per baris teks)', true)}
      {field('kota', 'Kota pada tanggal tanda tangan')}
      <span />
      {field('kiriJabatan', 'Tanda tangan kiri: jabatan', true)}
      {field('kananJabatan', 'Tanda tangan kanan: jabatan', true)}
      {field('kiriNama', 'Kiri: nama')}
      {field('kananNama', 'Kanan: nama')}
      {field('kiriNip', 'Kiri: NIP')}
      {field('kananNip', 'Kanan: NIP')}
      <p className="wide muted">Tersimpan di browser ini. Kosongkan nama untuk diisi tangan setelah dicetak.</p>
    </div>
  </details>;
}

// The printed page: kop, title, the full table and the signatures. Hidden on screen.
function PrintSheet({ settings, title, subtitle, columns, rows, footer, before }: {
  settings: PrintSettings; title: string; subtitle: string; columns: Column[]; rows: Row[]; footer?: Cell[]; before?: ReactNode;
}) {
  const sign = (jabatan: string, nama: string, nip: string, dated: boolean) => <div className="print-sign">
    <p>{dated ? `${settings.kota}, ${day(today, longDate)}` : ' '}</p>
    {jabatan.split('\n').map((line, i) => <p key={i}>{line}</p>)}
    <p className="print-sign-space" />
    <p><strong>{nama || '(.......................................)'}</strong></p>
    <p>NIP. {nip || '.......................................'}</p>
  </div>;
  return <div className="print-area" aria-hidden="true">
    <header className="print-kop">{settings.kop.split('\n').filter(Boolean).map((line, i) => <p key={i}>{line}</p>)}</header>
    <h1 className="print-title">{title}</h1>
    <p className="print-subtitle">{subtitle}</p>
    {before}
    <table className="print-table">
      <thead><tr>{columns.map((column) => <th key={column.label}>{column.label}</th>)}</tr></thead>
      <tbody>{rows.map((row, index) => <tr key={index}>{columns.map((column) =>
        <td key={column.label} className={column.num ? 'num' : undefined}>{show(column.value(row, index), column.num)}</td>)}</tr>)}</tbody>
      {footer && <tfoot><tr>{footer.map((cell, i) => <td key={i} className={columns[i]?.num ? 'num' : undefined}>{show(cell, columns[i]?.num)}</td>)}</tr></tfoot>}
    </table>
    <div className="print-signs">{sign(settings.kiriJabatan, settings.kiriNama, settings.kiriNip, false)}
      {sign(settings.kananJabatan, settings.kananNama, settings.kananNip, true)}</div>
  </div>;
}

// Prints once the full rows are on the page, then clears them.
function usePrint() {
  const [rows, setRows] = useState<Row[] | null>(null);
  useEffect(() => {
    if (!rows) return;
    const done = () => setRows(null);
    window.addEventListener('afterprint', done, { once: true });
    const timer = window.setTimeout(() => window.print(), 150);
    return () => { window.clearTimeout(timer); window.removeEventListener('afterprint', done); };
  }, [rows]);
  return [rows, setRows] as const;
}

function exportFile(kind: 'xlsx' | 'csv', file: string, title: string, subtitle: string, columns: Column[], rows: Row[], footer?: Cell[]) {
  const data = rows.map((row, index) => columns.map((column) => column.value(row, index)));
  if (kind === 'csv') downloadCsv(`${file}-${stamp()}.csv`, columns.map((c) => c.label), footer ? [...data, footer] : data);
  else downloadXlsx(`${file}-${stamp()}.xlsx`, { title: [...loadPrint().kop.split('\n').filter(Boolean), title, subtitle],
    header: columns.map((c) => c.label), rows: data, footer: footer ? [footer] : undefined });
}

function Actions({ busy, onExport, onPrint }: { busy: boolean; onExport: (kind: 'xlsx' | 'csv') => void; onPrint: () => void }) {
  return <div className="report-actions">
    <button className="secondary" type="button" disabled={busy} onClick={() => onExport('xlsx')}><i className="fas fa-file-excel" aria-hidden="true" /> Excel</button>
    <button className="secondary" type="button" disabled={busy} onClick={() => onExport('csv')}>CSV</button>
    <button className="primary" type="button" disabled={busy} onClick={onPrint}><i className="fas fa-print" aria-hidden="true" /> Cetak</button>
  </div>;
}

function Table({ label, columns, rows, footer, offset = 0 }: { label: string; columns: Column[]; rows: Row[]; footer?: Cell[]; offset?: number }) {
  return <div className="inventory-scroll" tabIndex={0} role="region" aria-label={label}>
    <table>
      <thead><tr>{columns.map((column) => <th key={column.label} scope="col" className={column.num ? 'num' : undefined}>{column.label}</th>)}</tr></thead>
      <tbody>{rows.map((row, index) => <tr key={String(row.id ?? '') + String(row.kind ?? row.jenis ?? '') + index}>
        {columns.map((column) => <td key={column.label} className={column.num ? 'num' : undefined}>
          {column.screen ? column.screen(row) : show(column.value(row, offset + index), column.num)}</td>)}</tr>)}</tbody>
      {footer && <tfoot><tr>{footer.map((cell, i) => <td key={i} className={columns[i]?.num ? 'num' : undefined}>{show(cell, columns[i]?.num)}</td>)}</tr></tfoot>}
    </table>
  </div>;
}

function Pages({ page, total, go }: { page: number; total: number; go: (page: number) => void }) {
  const last = Math.max(1, Math.ceil(total / perPage));
  return <nav className="inventory-pages" aria-label="Halaman laporan">
    <button className="secondary" disabled={page <= 1} onClick={() => go(page - 1)}>Sebelumnya</button>
    <span>Halaman {number.format(page)} dari {number.format(last)}</span>
    <button className="secondary" disabled={page >= last} onClick={() => go(page + 1)}>Berikutnya</button>
  </nav>;
}

export function Laporan() {
  const [tab, setTab] = useState<Tab>('persediaan');
  const [settings, setSettings] = useState<PrintSettings>(loadPrint);
  const save = (next: PrintSettings) => { setSettings(next); try { localStorage.setItem('simbar-cetak', JSON.stringify(next)); } catch { /* private mode */ } };
  return <section className="panel report-panel">
    <h2>Laporan</h2>
    <p>Pilih laporan, atur periode, lalu cetak dengan kop dan tanda tangan atau unduh sebagai Excel.</p>
    <div className="filters report-tabs" role="group" aria-label="Jenis laporan">
      {([['persediaan', 'Persediaan', 'fa-boxes-stacked'], ['mutasi', 'Mutasi', 'fa-right-left'], ['pengajuan', 'Pengajuan', 'fa-paper-plane'],
        ['kartu', 'Kartu Stok', 'fa-id-card']] as const).map(([value, label, icon]) =>
        <button key={value} className="secondary" aria-pressed={tab === value} onClick={() => setTab(value)}>
          <i className={'fas ' + icon} aria-hidden="true" /> {label}</button>)}
    </div>
    <PrintSettingsForm value={settings} onChange={save} />
    {tab === 'persediaan' ? <Persediaan settings={settings} /> : tab === 'mutasi' ? <Gabungan key="mutasi" kind="mutasi" settings={settings} />
      : tab === 'pengajuan' ? <Gabungan key="pengajuan" kind="pengajuan" settings={settings} /> : <KartuStok settings={settings} />}
  </section>;
}

// ---------- Persediaan ----------
type PersediaanData = { summary: Record<string, number>; rows: Row[] };
type PeriodMode = 'bulan' | 'triwulan' | 'semester' | 'tahun' | 'rentang';

function Persediaan({ settings }: { settings: PrintSettings }) {
  const [mode, setMode] = useState<PeriodMode>('bulan');
  const [year, setYear] = useState(thisYear);
  const [month, setMonth] = useState(now.getUTCMonth() + 1);
  const [quarter, setQuarter] = useState(Math.ceil((now.getUTCMonth() + 1) / 3));
  const [half, setHalf] = useState(now.getUTCMonth() < 6 ? 1 : 2);
  const [custom, setCustom] = useState({ dari: `${thisYear}-01-01`, sampai: today });
  const [source, setSource] = useState('');
  const [search, setSearch] = useState('');
  const [state, setState] = useState<{ data?: PersediaanData; error?: string }>({});
  const [months, setMonths] = useState<{ month: number; stock_in: number; stock_out: number }[] | null>(null);
  const [printRows, setPrintRows] = usePrint();

  const [startMonth, endMonth] = mode === 'bulan' ? [month, month] : mode === 'triwulan' ? [quarter * 3 - 2, quarter * 3]
    : mode === 'semester' ? [half * 6 - 5, half * 6] : [1, 12];
  const dari = mode === 'rentang' ? custom.dari : `${year}-${String(startMonth).padStart(2, '0')}-01`;
  const sampai = mode === 'rentang' ? custom.sampai : lastDay(year, endMonth);
  const periodName = mode === 'bulan' ? `${monthNames[month - 1]} ${year}` : mode === 'triwulan' ? `Triwulan ${['I', 'II', 'III', 'IV'][quarter - 1]} ${year}`
    : mode === 'semester' ? `Semester ${half === 1 ? 'I' : 'II'} ${year}` : mode === 'tahun' ? `Tahun ${year}` : range(dari, sampai);

  useEffect(() => {
    let live = true;
    setState((current) => ({ data: current.data }));
    rpc<PersediaanData>('simbar_laporan_persediaan', { dari, sampai, perolehan_filter: source || null, search_text: search })
      .then((data) => { if (live) setState({ data }); }).catch((error: Error) => { if (live) setState({ error: error.message }); });
    return () => { live = false; };
  }, [dari, sampai, source, search]);
  useEffect(() => {
    if (mode !== 'tahun') { setMonths(null); return; }
    let live = true;
    rpc<{ months: { month: number; stock_in: number; stock_out: number }[] }>('simbar_laporan_tahunan', { tahun: year })
      .then((data) => { if (live) setMonths(data.months); }).catch(() => {});
    return () => { live = false; };
  }, [mode, year]);

  const columns: Column[] = [
    { label: 'No', value: (_r, i) => i + 1, num: true },
    { label: 'Kode', value: (r) => r.code }, { label: 'Nama Barang', value: (r) => r.name,
      screen: (r) => <><strong>{r.name}</strong><span className="cell-sub">{r.code} · {r.perolehan ?? '-'}</span></> },
    { label: 'Jenis Barang', value: (r) => r.category }, { label: 'Satuan', value: (r) => r.unit },
    { label: 'Harga Satuan', value: (r) => n(r.price), num: true, screen: (r) => rupiah.format(n(r.price)) },
    { label: 'Saldo Awal', value: (r) => n(r.saldo_awal), num: true }, { label: 'Masuk', value: (r) => n(r.masuk), num: true },
    { label: 'Keluar', value: (r) => n(r.keluar), num: true }, { label: 'Saldo Akhir', value: (r) => n(r.saldo_akhir), num: true },
    { label: 'Nilai Akhir (Rp)', value: (r) => n(r.nilai_akhir), num: true, screen: (r) => rupiah.format(n(r.nilai_akhir)) },
  ];
  const screenColumns = columns.filter((c) => c.label !== 'Kode');
  const s = state.data?.summary;
  const footer = (cols: Column[]): Cell[] => cols.map((c) => c.label === 'Nama Barang' ? 'TOTAL' : c.label === 'Saldo Awal' ? n(s?.saldo_awal)
    : c.label === 'Masuk' ? n(s?.masuk) : c.label === 'Keluar' ? n(s?.keluar) : c.label === 'Saldo Akhir' ? n(s?.saldo_akhir)
    : c.label === 'Nilai Akhir (Rp)' ? n(s?.nilai_akhir) : '');
  const title = 'LAPORAN PERSEDIAAN BARANG' + (source ? ' — ' + source.toUpperCase() : '');
  const subtitle = `Periode ${periodName} (${range(dari, sampai)})`;

  function submit(event: FormEvent<HTMLFormElement>) {
    event.preventDefault();
    setSearch(String(new FormData(event.currentTarget).get('search') ?? '').trim());
  }

  return <>
    <form className="asset-filter" onSubmit={submit}>
      <label>Periode<select value={mode} onChange={(event) => setMode(event.target.value as PeriodMode)}>
        <option value="bulan">Bulanan</option><option value="triwulan">Triwulan</option><option value="semester">Semester</option>
        <option value="tahun">Tahunan</option><option value="rentang">Rentang tanggal</option></select></label>
      {mode === 'bulan' && <label>Bulan<select value={month} onChange={(event) => setMonth(Number(event.target.value))}>
        {monthNames.map((name, i) => <option key={name} value={i + 1}>{name}</option>)}</select></label>}
      {mode === 'triwulan' && <label>Triwulan<select value={quarter} onChange={(event) => setQuarter(Number(event.target.value))}>
        {['I (Jan – Mar)', 'II (Apr – Jun)', 'III (Jul – Sep)', 'IV (Okt – Des)'].map((name, i) => <option key={name} value={i + 1}>Triwulan {name}</option>)}</select></label>}
      {mode === 'semester' && <label>Semester<select value={half} onChange={(event) => setHalf(Number(event.target.value))}>
        <option value={1}>Semester I (Jan – Jun)</option><option value={2}>Semester II (Jul – Des)</option></select></label>}
      {mode === 'rentang' ? <>
        <label>Dari<input type="date" value={custom.dari} max={custom.sampai} onChange={(event) => setCustom({ ...custom, dari: event.target.value })} /></label>
        <label>Sampai<input type="date" value={custom.sampai} min={custom.dari} onChange={(event) => setCustom({ ...custom, sampai: event.target.value })} /></label>
      </> : <label>Tahun<select value={year} onChange={(event) => setYear(Number(event.target.value))}>
        {Array.from({ length: 6 }, (_, i) => thisYear - i).map((value) => <option key={value}>{value}</option>)}</select></label>}
      <label>Perolehan<select value={source} onChange={(event) => setSource(event.target.value)}><option value="">Semua</option>
        {perolehan.map((value) => <option key={value}>{value}</option>)}</select></label>
      <label>Cari<input name="search" type="search" maxLength={100} placeholder="Nama, kode atau jenis barang" /></label>
      <div className="form-actions wide"><button className="primary" type="submit">Terapkan</button></div>
    </form>
    <Actions busy={!state.data} onExport={(kind) => state.data && exportFile(kind, 'laporan-persediaan', title, subtitle, columns, state.data.rows, footer(columns))}
      onPrint={() => state.data && setPrintRows(state.data.rows)} />
    {state.error ? <p className="notice" role="alert">{state.error}</p> : !state.data ? <p role="status">Menghitung persediaan…</p> : <>
      <h3 className="section-title">{periodName} · {range(dari, sampai)}</h3>
      <dl className="metrics compact report-metrics">
        <div className="metric"><dt>Saldo awal</dt><dd>{number.format(n(s?.saldo_awal))}</dd></div>
        <div className="metric"><dt>Masuk</dt><dd>{number.format(n(s?.masuk))}</dd></div>
        <div className="metric"><dt>Keluar</dt><dd>{number.format(n(s?.keluar))}</dd></div>
        <div className="metric"><dt>Saldo akhir</dt><dd>{number.format(n(s?.saldo_akhir))}</dd></div>
        <div className="metric"><dt>Nilai akhir</dt><dd className="small">{rupiah.format(n(s?.nilai_akhir))}</dd></div>
      </dl>
      {months && <GroupedBars title={`Barang masuk dan keluar per bulan, ${year}`} labels={months.map((m) => monthNames[m.month - 1].slice(0, 3))}
        series={[{ name: 'Masuk', values: months.map((m) => n(m.stock_in)) }, { name: 'Keluar', values: months.map((m) => n(m.stock_out)) }]} />}
      {!state.data.rows.length ? <p role="status">Tidak ada barang pada filter ini.</p>
        : <Table label="Tabel laporan persediaan" columns={screenColumns} rows={state.data.rows} footer={footer(screenColumns)} />}
      <p className="muted report-note">Saldo dihitung mundur dari stok hari ini dan riwayat barang masuk/keluar, sehingga periode lama tetap tepat.
        Nilai memakai harga satuan saat ini.</p>
    </>}
    {printRows && <PrintSheet settings={settings} title={title} subtitle={subtitle} columns={columns} rows={printRows} footer={footer(columns)} />}
  </>;
}

// ---------- Mutasi and Pengajuan ----------
type ListPage = { stats: Record<string, number>; total: number; rows: Row[] };
const bucketLabels: Record<string, string> = { menunggu: 'Menunggu', revisi: 'Revisi', selesai: 'Selesai', ditolak: 'Ditolak', dibatalkan: 'Dibatalkan' };

function Gabungan({ kind, settings }: { kind: 'mutasi' | 'pengajuan'; settings: PrintSettings }) {
  const [filters, setFilters] = useState<Record<string, string>>({ dari: `${thisYear}-${String(now.getUTCMonth() + 1).padStart(2, '0')}-01`, sampai: today });
  const [page, setPage] = useState(1);
  const [state, setState] = useState<{ data?: ListPage; error?: string }>({});
  const [items, setItems] = useState<PickItem[]>([]);
  const [printRows, setPrintRows] = usePrint();
  const [busy, setBusy] = useState(false);
  const [formKey, setFormKey] = useState(0);

  useEffect(() => {
    if (kind === 'mutasi') rpc<{ items: PickItem[] }>('simbar_stock_options').then((v) => setItems(v.items)).catch(() => {});
  }, [kind]);
  useEffect(() => {
    let live = true;
    setState((current) => ({ data: current.data }));
    rpc<ListPage>('simbar_laporan_gabungan', { report_kind: kind, filters, page_number: page })
      .then((data) => { if (live) setState({ data }); }).catch((error: Error) => { if (live) setState({ error: error.message }); });
    return () => { live = false; };
  }, [kind, filters, page]);

  const columns: Column[] = kind === 'mutasi' ? [
    { label: 'No', value: (_r, i) => i + 1, num: true }, { label: 'Tanggal', value: (r) => day(r.date) },
    { label: 'Jenis', value: (r) => r.jenis === 'in' ? 'Masuk' : 'Keluar',
      screen: (r) => <span className={'badge ' + (r.jenis === 'in' ? 'completed' : 'rejected')}>{r.jenis === 'in' ? 'Masuk' : 'Keluar'}</span> },
    { label: 'Kode', value: (r) => r.code }, { label: 'Nama Barang', value: (r) => r.name }, { label: 'Jenis Barang', value: (r) => r.category },
    { label: 'Jumlah', value: (r) => n(r.quantity), num: true }, { label: 'Satuan', value: (r) => r.unit },
    { label: 'Keterangan', value: (r) => r.notes }, { label: 'Petugas', value: (r) => r.petugas },
  ] : [
    { label: 'No', value: (_r, i) => i + 1, num: true }, { label: 'Tanggal', value: (r) => day(r.tanggal) },
    { label: 'Jenis', value: (r) => KINDS[r.kind as QueueKind]?.label ?? r.kind },
    { label: 'Kode', value: (r) => r.code }, { label: 'Barang', value: (r) => r.title }, { label: 'Pengaju', value: (r) => r.requester },
    { label: 'Jumlah', value: (r) => r.jumlah === null ? null : n(r.jumlah), num: true },
    { label: 'Status', value: (r) => STATUS[r.kind as QueueKind]?.[String(r.status)] ?? r.status,
      screen: (r) => <span className={'badge ' + (r.kind === 'pemeliharaan' ? BADGE[String(r.status)] ?? '' : r.status)}>
        {STATUS[r.kind as QueueKind]?.[String(r.status)] ?? r.status}</span> },
    { label: 'Catatan', value: (r) => r.note },
  ];
  const stats: [string, string][] = kind === 'mutasi' ? [['total', 'Transaksi'], ['masuk', 'Jumlah masuk'], ['keluar', 'Jumlah keluar']]
    : [['total', 'Pengajuan'], ['menunggu', 'Menunggu'], ['selesai', 'Selesai'], ['ditolak', 'Ditolak']];
  const title = kind === 'mutasi' ? 'LAPORAN MUTASI BARANG' : 'LAPORAN PENGAJUAN';
  const jenisName = filters.jenis ? (kind === 'mutasi' ? (filters.jenis === 'in' ? ' — Barang masuk' : ' — Barang keluar')
    : ' — ' + KINDS[filters.jenis as QueueKind].label) : '';
  const subtitle = (filters.dari || filters.sampai ? `Periode ${range(filters.dari || '2000-01-01', filters.sampai || today)}` : 'Semua periode') + jenisName
    + (filters.status ? ' — ' + bucketLabels[filters.status] : '');

  function apply(event: FormEvent<HTMLFormElement>) {
    event.preventDefault();
    setFilters(Object.fromEntries([...new FormData(event.currentTarget).entries()].map(([key, value]) => [key, String(value).trim()])));
    setPage(1);
  }
  async function everything() {
    setBusy(true);
    try { return (await rpc<ListPage>('simbar_laporan_gabungan', { report_kind: kind, filters, page_number: 0 })).rows; }
    catch (error) { setState((current) => ({ ...current, error: (error as Error).message })); return null; }
    finally { setBusy(false); }
  }

  return <>
    <form className="asset-filter" onSubmit={apply} key={kind + formKey}>
      <label>Dari tanggal<input name="dari" type="date" defaultValue={filters.dari} /></label>
      <label>Sampai tanggal<input name="sampai" type="date" defaultValue={filters.sampai} /></label>
      <label>Jenis<select name="jenis" defaultValue=""><option value="">Semua</option>
        {kind === 'mutasi' ? <><option value="in">Barang masuk</option><option value="out">Barang keluar</option></>
          : (Object.keys(KINDS) as QueueKind[]).map((value) => <option key={value} value={value}>{KINDS[value].label}</option>)}</select></label>
      {kind === 'mutasi' ? <label>Barang<ItemPicker name="item" allLabel="Semua barang" items={items} /></label>
        : <label>Status<select name="status" defaultValue=""><option value="">Semua</option>
          {Object.entries(bucketLabels).map(([value, label]) => <option key={value} value={value}>{label}</option>)}</select></label>}
      <label>Cari<input name="search" type="search" maxLength={100} placeholder={kind === 'mutasi' ? 'Nama, kode atau keterangan' : 'Barang, kode atau pengaju'} /></label>
      <div className="form-actions wide">
        <button className="primary" type="submit">Terapkan</button>
        <button className="secondary" type="button" onClick={() => { setFilters({}); setPage(1); setFormKey((k) => k + 1); }}>Semua periode</button>
      </div>
    </form>
    <Actions busy={busy || !state.data} onExport={async (type) => { const rows = await everything(); if (rows) exportFile(type, 'laporan-' + kind, title, subtitle, columns, rows); }}
      onPrint={async () => { const rows = await everything(); if (rows) setPrintRows(rows); }} />
    {state.error ? <p className="notice" role="alert">{state.error}</p> : !state.data ? <p role="status">Memuat laporan…</p> : <>
      <dl className="metrics compact">{stats.map(([key, label]) =>
        <div className="metric" key={key}><dt>{label}</dt><dd>{number.format(n(state.data!.stats[key]))}</dd></div>)}</dl>
      {!state.data.total ? <p role="status">Tidak ada data pada filter ini.</p> : <>
        <Table label={'Tabel ' + title.toLowerCase()} columns={columns} rows={state.data.rows} offset={(page - 1) * perPage} />
        <Pages page={page} total={state.data.total} go={setPage} />
      </>}
    </>}
    {printRows && <PrintSheet settings={settings} title={title} subtitle={subtitle} columns={columns} rows={printRows} />}
  </>;
}

// ---------- Kartu Stok ----------
type Kartu = { item: Row; saldo_awal: number; masuk: number; keluar: number; saldo_akhir: number; rows: Row[] };

function KartuStok({ settings }: { settings: PrintSettings }) {
  const [items, setItems] = useState<PickItem[]>([]);
  const [item, setItem] = useState('');
  const [period, setPeriod] = useState({ dari: `${thisYear}-01-01`, sampai: today });
  const [state, setState] = useState<{ data?: Kartu; error?: string }>({});
  const [printRows, setPrintRows] = usePrint();

  useEffect(() => { rpc<{ items: PickItem[] }>('simbar_stock_options').then((v) => setItems(v.items)).catch(() => {}); }, []);
  useEffect(() => {
    if (!item) { setState({}); return; }
    let live = true;
    setState((current) => ({ data: current.data }));
    rpc<Kartu>('simbar_kartu_stok', { item_ref: Number(item), dari: period.dari, sampai: period.sampai })
      .then((data) => { if (live) setState({ data }); }).catch((error: Error) => { if (live) setState({ error: error.message }); });
    return () => { live = false; };
  }, [item, period]);

  const data = state.data;
  const lines: Row[] = data ? [{ id: 'awal', date: period.dari, jenis: 'awal', notes: 'Saldo awal', quantity: null, saldo: data.saldo_awal, petugas: null }, ...data.rows] : [];
  const columns: Column[] = [
    { label: 'No', value: (r, i) => r.jenis === 'awal' ? '' : i, num: true }, { label: 'Tanggal', value: (r) => day(r.date) },
    { label: 'Uraian', value: (r) => r.jenis === 'awal' ? 'Saldo awal' : r.notes || (r.jenis === 'in' ? 'Barang masuk' : 'Barang keluar') },
    { label: 'Masuk', value: (r) => r.jenis === 'in' ? n(r.quantity) : null, num: true },
    { label: 'Keluar', value: (r) => r.jenis === 'out' ? n(r.quantity) : null, num: true },
    { label: 'Saldo', value: (r) => n(r.saldo), num: true }, { label: 'Petugas', value: (r) => r.petugas },
  ];
  const footer: Cell[] = ['', '', 'Jumlah / saldo akhir', n(data?.masuk), n(data?.keluar), n(data?.saldo_akhir), ''];
  const info = data && <dl className="kartu-info">
    <div><dt>Barang</dt><dd>{data.item.name} ({data.item.code})</dd></div>
    <div><dt>Jenis barang</dt><dd>{data.item.category}</dd></div>
    <div><dt>Satuan</dt><dd>{data.item.unit_name}</dd></div>
    <div><dt>Harga satuan</dt><dd>{rupiah.format(n(data.item.price))}</dd></div>
  </dl>;
  const title = 'KARTU STOK BARANG';
  const subtitle = data ? `${data.item.name} (${data.item.code}) — Periode ${range(period.dari, period.sampai)}` : '';

  return <>
    <div className="asset-filter">
      <label className="wide">Barang<ItemPicker value={item} onChange={setItem} items={items} /></label>
      <label>Dari<input type="date" value={period.dari} max={period.sampai} onChange={(event) => setPeriod({ ...period, dari: event.target.value })} /></label>
      <label>Sampai<input type="date" value={period.sampai} min={period.dari} onChange={(event) => setPeriod({ ...period, sampai: event.target.value })} /></label>
    </div>
    {!item ? <p role="status">Pilih barang untuk melihat kartu stoknya.</p> : <>
      <Actions busy={!data} onExport={(kind) => data && exportFile(kind, 'kartu-stok-' + String(data.item.code), title, subtitle, columns, lines, footer)}
        onPrint={() => data && setPrintRows(lines)} />
      {state.error ? <p className="notice" role="alert">{state.error}</p> : !data ? <p role="status">Memuat kartu stok…</p> : <>
        {info}
        <dl className="metrics compact report-metrics">
          <div className="metric"><dt>Saldo awal</dt><dd>{number.format(data.saldo_awal)}</dd></div>
          <div className="metric"><dt>Masuk</dt><dd>{number.format(n(data.masuk))}</dd></div>
          <div className="metric"><dt>Keluar</dt><dd>{number.format(n(data.keluar))}</dd></div>
          <div className="metric"><dt>Saldo akhir</dt><dd>{number.format(n(data.saldo_akhir))}</dd></div>
        </dl>
        <Table label="Tabel kartu stok" columns={columns} rows={lines} footer={footer} />
      </>}
    </>}
    {printRows && data && <PrintSheet settings={settings} title={title} subtitle={subtitle} columns={columns} rows={printRows} footer={footer} before={info} />}
  </>;
}
