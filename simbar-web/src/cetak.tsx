import { useEffect, useState, type ReactNode } from 'react';
import { downloadCsv, stamp } from './csv';
import { rpc } from './supabase';
import { downloadXlsx } from './xlsx';

// Kop and tanda tangan for every report, plus one "Unduh / Cetak" dialog every export goes through:
// Excel with the kop, a printed page (or PDF) with kop, logos and signatures, or a plain CSV for further processing.
// The settings live in the database (one kop for every device) with a copy in this browser.
export type Cell = string | number | null | undefined;
export type PrintSettings = {
  kop: string; logo: string; kota: string; kiriJabatan: string; kiriNama: string; kiriNip: string;
  kananJabatan: string; kananNama: string; kananNip: string;
};
const OLD_KOP = 'PEMERINTAH KOTA BALIKPAPAN\nDINAS KESEHATAN\nUPTD PUSKESMAS KARANG REJO';
export const defaultPrint: PrintSettings = {
  kop: 'PEMERINTAH KOTA BALIKPAPAN\nDINAS KESEHATAN\nUPTD PUSKESMAS KARANG REJO\n'
    + 'Jl. Pangeran Antasari No. 01 RT 62 Karang Rejo, Telp. 0542-425231\nEmail : puskesmas_karang_rejo@yahoo.com, Kode Pos 76124',
  logo: 'ya', kota: 'Balikpapan',
  kiriJabatan: 'Mengetahui,\nKepala UPTD Puskesmas Karang Rejo', kiriNama: '', kiriNip: '',
  kananJabatan: 'Pengurus Barang', kananNama: '', kananNip: '',
};
const KEY = 'simbar-cetak';
const longDate = new Intl.DateTimeFormat('id-ID', { dateStyle: 'long', timeZone: 'UTC' });
const number = new Intl.NumberFormat('id-ID');
const today = () => new Date(Date.now() + 7 * 3600_000).toISOString().slice(0, 10); // Asia/Jakarta

function clean(value: unknown): PrintSettings {
  const stored = value && typeof value === 'object' ? value as Partial<PrintSettings> : {};
  const next = { ...defaultPrint };
  for (const key of Object.keys(defaultPrint) as (keyof PrintSettings)[]) if (typeof stored[key] === 'string') next[key] = stored[key] as string;
  if (next.kop.trim() === OLD_KOP) next.kop = defaultPrint.kop; // the three-line kop from before the address was added
  return next;
}
export function loadPrint(): PrintSettings {
  try { return clean(JSON.parse(localStorage.getItem(KEY) ?? '{}')); } catch { return defaultPrint; }
}
export function keepPrint(next: PrintSettings) {
  try { localStorage.setItem(KEY, JSON.stringify(next)); } catch { /* private mode */ }
}
// The shared settings from the database; falls back to this browser's copy when offline or nothing is saved yet.
export async function syncPrint(): Promise<PrintSettings> {
  try {
    const shared = await rpc<Partial<PrintSettings> | null>('simbar_cetak_settings');
    if (shared) { const next = clean(shared); keepPrint(next); return next; }
  } catch { /* keep the local copy */ }
  return loadPrint();
}
export function usePrintSettings(): [PrintSettings, (next: PrintSettings) => void] {
  const [settings, setSettings] = useState<PrintSettings>(loadPrint);
  useEffect(() => { let active = true; syncPrint().then((next) => active && setSettings(next)); return () => { active = false; }; }, []);
  return [settings, (next) => { setSettings(next); keepPrint(next); }];
}

const kopLines = (settings: PrintSettings) => settings.kop.split('\n').map((line) => line.trim()).filter(Boolean);

export function PrintSettingsForm({ value, onChange }: { value: PrintSettings; onChange: (next: PrintSettings) => void }) {
  const [state, setState] = useState<{ text: string; ok: boolean; busy?: boolean }>({ text: '', ok: true });
  const field = (key: keyof PrintSettings, label: string, rows = 0) => <label className={rows ? 'wide' : undefined}>{label}
    {rows ? <textarea rows={rows} value={value[key]} maxLength={800} onChange={(event) => onChange({ ...value, [key]: event.target.value })} />
      : <input value={value[key]} maxLength={120} onChange={(event) => onChange({ ...value, [key]: event.target.value })} />}</label>;
  async function share() {
    setState({ text: '', ok: true, busy: true });
    try {
      await rpc('simbar_cetak_settings_save', { settings: value });
      setState({ text: 'Tersimpan untuk semua perangkat.', ok: true });
    } catch (error) {
      setState({ text: (error as Error).message, ok: false });
    }
  }
  return <details className="print-settings">
    <summary><i className="fas fa-print" aria-hidden="true" /> Pengaturan cetak: kop dan tanda tangan</summary>
    <div className="record-form">
      {field('kop', 'Kop (baris 1-3 nama instansi, baris berikutnya alamat)', 5)}
      <label className="inline wide"><input type="checkbox" checked={value.logo === 'ya'}
        onChange={(event) => onChange({ ...value, logo: event.target.checked ? 'ya' : 'tidak' })} /> Tampilkan logo Kota Balikpapan dan logo Puskesmas pada cetakan</label>
      {field('kota', 'Kota pada tanggal tanda tangan')}
      <span />
      {field('kiriJabatan', 'Tanda tangan kiri: jabatan', 3)}
      {field('kananJabatan', 'Tanda tangan kanan: jabatan', 3)}
      {field('kiriNama', 'Kiri: nama')}
      {field('kananNama', 'Kanan: nama')}
      {field('kiriNip', 'Kiri: NIP')}
      {field('kananNip', 'Kanan: NIP')}
      <div className="form-actions wide">
        <button className="primary" type="button" disabled={state.busy && !state.text} onClick={share}>Simpan untuk semua perangkat</button>
        <button className="secondary" type="button" onClick={() => onChange({ ...defaultPrint, kiriNama: value.kiriNama, kiriNip: value.kiriNip, kananNama: value.kananNama, kananNip: value.kananNip })}>Kembalikan kop bawaan</button>
      </div>
      {state.text && <p className={'wide ' + (state.ok ? 'success' : 'notice')} role={state.ok ? 'status' : 'alert'}>{state.text}</p>}
      <p className="wide muted">Perubahan langsung berlaku di perangkat ini. Admin dan admin gudang bisa menyimpannya untuk semua perangkat. Kosongkan nama untuk diisi tangan setelah dicetak.</p>
    </div>
  </details>;
}

// The kop on a printed page: logo kota on the left, logo puskesmas on the right, the lines centred between them.
export function PrintKop({ settings }: { settings: PrintSettings }) {
  const lines = kopLines(settings);
  const logo = settings.logo === 'ya';
  return <header className={'print-kop' + (logo ? ' with-logo' : '')}>
    {logo && <img src="/kop/logo-kota.jpg" alt="" />}
    <div>{lines.map((line, i) => <p key={i} className={i === 2 ? 'kop-unit' : i > 2 ? 'kop-alamat' : undefined}>{line}</p>)}</div>
    {logo && <img src="/kop/logo-puskesmas.png" alt="" />}
  </header>;
}

export function PrintSigns({ settings }: { settings: PrintSettings }) {
  const sign = (jabatan: string, nama: string, nip: string, dated: boolean) => <div className="print-sign">
    <p>{dated ? `${settings.kota}, ${longDate.format(new Date(today()))}` : ' '}</p>
    {jabatan.split('\n').map((line, i) => <p key={i}>{line}</p>)}
    <p className="print-sign-space" />
    <p><strong>{nama || '(.......................................)'}</strong></p>
    <p>NIP. {nip || '.......................................'}</p>
  </div>;
  return <div className="print-signs">{sign(settings.kiriJabatan, settings.kiriNama, settings.kiriNip, false)}
    {sign(settings.kananJabatan, settings.kananNama, settings.kananNip, true)}</div>;
}

// Lines above the table in an Excel file: the kop, an empty line, then the report title.
export const xlsxTitle = (title: string, subtitle?: string) => [...kopLines(loadPrint()), '', title, ...(subtitle ? [subtitle] : [])];

// Opens the print preview once the logos on the print sheet have loaded. Returns a function that cancels it.
export function printWhenReady(selector = '.print-area img') {
  let timer = 0;
  let cancelled = false;
  const images = [...document.querySelectorAll<HTMLImageElement>(selector)];
  Promise.all(images.map((image) => image.complete ? null : new Promise((resolve) => { image.onload = resolve; image.onerror = resolve; })))
    .then(() => { if (!cancelled) timer = window.setTimeout(() => window.print(), 150); });
  return () => { cancelled = true; window.clearTimeout(timer); };
}

// ---------- One dialog for every export ----------
export type Report = { file: string; title: string; subtitle?: string; header: string[]; rows: Cell[][]; footer?: Cell[]; before?: ReactNode };
let show: ((report: Report | null) => void) | null = null;
// Call with the full rows; the user then picks Excel, Cetak/PDF or CSV.
export function exportReport(report: Report) {
  if (show) show(report);
  else downloadCsv(`${report.file}_${stamp()}.csv`, report.header, report.rows);
}

const cell = (value: Cell) => value === null || value === undefined || value === '' ? '-' : typeof value === 'number' ? number.format(value) : value;

export function ExportDialog() {
  const [report, setReport] = useState<Report | null>(null);
  const [printing, setPrinting] = useState<PrintSettings | null>(null);
  useEffect(() => { show = setReport; return () => { show = null; }; }, []);
  useEffect(() => {
    if (!printing) return;
    const done = () => { setPrinting(null); setReport(null); };
    window.addEventListener('afterprint', done, { once: true });
    const cancel = printWhenReady();
    return () => { cancel(); window.removeEventListener('afterprint', done); };
  }, [printing]);
  useEffect(() => {
    if (!report) return;
    const close = (event: KeyboardEvent) => { if (event.key === 'Escape') setReport(null); };
    window.addEventListener('keydown', close);
    return () => window.removeEventListener('keydown', close);
  }, [report]);
  if (!report) return null;

  const body = report.footer ? [...report.rows, report.footer] : report.rows;
  const numeric = report.header.map((_, i) => report.rows.length > 0 && report.rows.every((row) => typeof row[i] === 'number' || row[i] === null || row[i] === '-'));
  const excel = async () => {
    await syncPrint();
    downloadXlsx(`${report.file}_${stamp()}.xlsx`, { title: xlsxTitle(report.title, report.subtitle), header: report.header,
      rows: report.rows, footer: report.footer ? [report.footer] : undefined });
    setReport(null);
  };
  return <>
    <div className="export-dialog" role="dialog" aria-modal="true" aria-label={'Unduh ' + report.title} onClick={(event) => { if (event.target === event.currentTarget) setReport(null); }}>
      <div className="export-box">
        <strong>{report.title}</strong>
        <p className="muted">{number.format(report.rows.length)} baris{report.subtitle ? ' · ' + report.subtitle : ''}</p>
        <button className="primary" type="button" onClick={excel}><i className="fas fa-file-excel" aria-hidden="true" /> Excel dengan kop</button>
        <button className="primary" type="button" disabled={!!printing} onClick={() => syncPrint().then(setPrinting)}>
          <i className="fas fa-print" aria-hidden="true" /> Cetak / simpan PDF dengan kop dan tanda tangan</button>
        <button className="secondary" type="button" onClick={() => { downloadCsv(`${report.file}_${stamp()}.csv`, report.header, body); setReport(null); }}>
          CSV polos (data mentah, tanpa kop)</button>
        <button className="secondary" type="button" onClick={() => setReport(null)}>Batal</button>
      </div>
    </div>
    {printing && <div className="print-area" aria-hidden="true">
      <PrintKop settings={printing} />
      <h1 className="print-title">{report.title}</h1>
      {report.subtitle && <p className="print-subtitle">{report.subtitle}</p>}
      {report.before}
      <table className="print-table">
        <thead><tr>{report.header.map((label) => <th key={label}>{label}</th>)}</tr></thead>
        <tbody>{report.rows.map((row, r) => <tr key={r}>{report.header.map((_, c) =>
          <td key={c} className={numeric[c] ? 'num' : undefined}>{cell(row[c])}</td>)}</tr>)}</tbody>
        {report.footer && <tfoot><tr>{report.header.map((_, c) => <td key={c} className={numeric[c] ? 'num' : undefined}>{cell(report.footer![c])}</td>)}</tr></tfoot>}
      </table>
      <PrintSigns settings={printing} />
    </div>}
  </>;
}
