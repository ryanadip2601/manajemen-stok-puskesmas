import { useEffect, useState, type FormEvent } from 'react';
import { downloadCsv, stamp } from './csv';
import { rpc } from './supabase';

type Totals = { total_aset: number; total_nilai_perolehan: number; total_akumulasi: number; total_nilai_buku: number; total_penyusutan?: number; aset_habis_umur?: number };
type Golongan = { golongan: string; umur_manfaat: number; jumlah_aset: number; total_nilai_perolehan: number; total_akumulasi: number; total_nilai_buku: number; total_penyusutan_tahun: number };
type TahunRow = { tahun_perolehan: number | null; jumlah_aset: number; total_nilai_perolehan: number; total_akumulasi: number; total_nilai_buku: number; total_penyusutan: number };
type Summary = { tahun: number; years: number[]; stored: Totals; realtime: Totals; golongan: Golongan[]; tahun_perolehan: TahunRow[] };
export type DepreciationRow = {
  id: number; kode_aset: string; nama_aset: string; kategori: string; golongan: string; ruangan: string; kondisi: string; jumlah: number;
  merk: string | null; sumber_dana: string | null; tahun_perolehan: number | null; tanggal_perolehan: string; nilai_perolehan: number;
  nilai_residu: number; umur_manfaat: number; dapat_disusutkan: boolean; penyusutan_per_tahun: number; penyusutan_per_bulan: number;
  jumlah_bulan: number; akumulasi_penyusutan: number; nilai_buku: number; persentase_penyusutan: number; sisa_umur_bulan: number | null; sudah_habis: boolean;
};
type Kartu = DepreciationRow & { riwayat: { tahun: number; bulan: number | null; nilai_perolehan: number; penyusutan_periode: number;
  akumulasi_awal: number; akumulasi_akhir: number; nilai_buku_akhir: number; umur_manfaat: number }[] };
type Option = { id: number; name: string };

const number = new Intl.NumberFormat('id-ID');
const rupiah = new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', maximumFractionDigits: 0 });
const money = (value: number | string | null | undefined) => rupiah.format(Number(value ?? 0));
export const golonganNames: Record<string, string> = {
  tanah: 'Tanah', peralatan_mesin: 'Peralatan dan Mesin', gedung_bangunan: 'Gedung dan Bangunan',
  jalan_irigasi: 'Jalan, Irigasi dan Jaringan', aset_tetap_lainnya: 'Aset Tetap Lainnya', kdp: 'Konstruksi Dalam Pengerjaan',
};
const thisYear = Number(new Date(Date.now() + 7 * 3600_000).toISOString().slice(0, 4));

function Message({ value }: { value: { text: string; ok: boolean } }) {
  return value.text ? <p className={value.ok ? 'success' : 'notice'} role={value.ok ? 'status' : 'alert'}>{value.text}</p> : null;
}

export function Penyusutan() {
  const [year, setYear] = useState(thisYear);
  const [tab, setTab] = useState<'golongan' | 'tahun' | 'detail'>('golongan');
  const [version, setVersion] = useState(0);
  const [state, setState] = useState<{ data?: Summary; error?: string }>({});
  const [message, setMessage] = useState({ text: '', ok: true });
  const [busy, setBusy] = useState(false);
  const [kartu, setKartu] = useState<Kartu | null>(null);

  useEffect(() => {
    let active = true;
    setState({});
    rpc<Summary>('simbar_penyusutan_summary', { tahun_filter: year })
      .then((data) => active && setState({ data })).catch((error: Error) => active && setState({ error: error.message }));
    return () => { active = false; };
  }, [year, version]);

  async function proses() {
    if (!confirm(`Proses penyusutan tahun ${year}? Posisi 31 Desember ${year} setiap aset aktif akan disimpan (data tahun ini ditimpa).`)) return;
    setBusy(true);
    try {
      const result = await rpc<{ aset_diproses: number; aset_skip: number; total_penyusutan: number }>('simbar_penyusutan_proses', { tahun_proses: year });
      setMessage({ text: `Penyusutan tahun ${year} berhasil diproses. ${result.aset_diproses} aset diproses, ${result.aset_skip} aset dilewati. Total penyusutan ${money(result.total_penyusutan)}.`, ok: true });
      setVersion((value) => value + 1);
    } catch (error) {
      setMessage({ text: (error as Error).message, ok: false });
    } finally {
      setBusy(false);
    }
  }

  async function exportYear() {
    try {
      const rows = await rpc<Record<string, string | number | null>[]>('simbar_penyusutan_export', { tahun_filter: year });
      if (!rows.length) return setMessage({ text: `Belum ada data penyusutan tersimpan untuk tahun ${year}.`, ok: false });
      downloadCsv(`laporan_penyusutan_${year}.csv`, ['Kode Aset', 'Nama Aset', 'Kategori', 'Golongan', 'Ruangan', 'Tahun Perolehan',
        'Nilai Perolehan', 'Umur Manfaat', 'Penyusutan/Tahun', 'Akumulasi Penyusutan', 'Nilai Buku'],
      rows.map((row) => [row.kode_aset, row.nama_aset, row.kategori, row.golongan, row.ruangan, row.tahun_perolehan, row.nilai_perolehan,
        row.umur_manfaat, row.penyusutan_per_tahun, row.akumulasi_penyusutan, row.nilai_buku]));
    } catch (error) {
      setMessage({ text: (error as Error).message, ok: false });
    }
  }

  async function openKartu(id: number) {
    try {
      setKartu(await rpc<Kartu>('simbar_penyusutan_kartu', { aset_id: id }));
    } catch (error) {
      setMessage({ text: (error as Error).message, ok: false });
    }
  }

  const data = state.data;
  const years = [...new Set([thisYear + 1, thisYear, ...(data?.years ?? [])])].sort((a, b) => b - a);

  return <section className="panel">
    <div className="panel-head">
      <h2>Penyusutan aset</h2>
      <div className="form-actions">
        <label className="sr-only" htmlFor="penyusutan-year">Tahun</label>
        <select id="penyusutan-year" className="year-select" value={year} onChange={(event) => setYear(Number(event.target.value))}>
          {years.map((value) => <option key={value} value={value}>{value}</option>)}
        </select>
        <button className="primary" disabled={busy || year < 2000 || year > thisYear + 1} onClick={proses}>{busy ? 'Memproses…' : `Proses tahun ${year}`}</button>
        <button className="secondary" onClick={exportYear}>Export CSV {year}</button>
      </div>
    </div>
    <p>Metode garis lurus per bulan, mulai bulan setelah perolehan. Tanah dan konstruksi dalam pengerjaan tidak disusutkan.</p>
    <Message value={message} />
    {state.error ? <p className="notice" role="alert">{state.error}</p> : !data ? <p role="status">Memuat penyusutan…</p> : <>
      <h3 className="section-title">Posisi hari ini (aset aktif bernilai)</h3>
      <dl className="metrics compact">
        <div className="metric"><dt>Aset</dt><dd>{number.format(data.realtime.total_aset)}</dd></div>
        <div className="metric"><dt>Nilai perolehan</dt><dd className="small">{money(data.realtime.total_nilai_perolehan)}</dd></div>
        <div className="metric"><dt>Akumulasi penyusutan</dt><dd className="small">{money(data.realtime.total_akumulasi)}</dd></div>
        <div className="metric"><dt>Nilai buku</dt><dd className="small">{money(data.realtime.total_nilai_buku)}</dd></div>
      </dl>
      <p className="cell-note">{number.format(data.realtime.aset_habis_umur ?? 0)} aset sudah habis umur manfaatnya.</p>
      <h3 className="section-title">Tersimpan untuk tahun {year}</h3>
      {Number(data.stored.total_aset) === 0 ? <p>Belum diproses. Tekan "Proses tahun {year}" untuk menyimpan posisi 31 Desember {year}.</p>
        : <dl className="metrics compact">
          <div className="metric"><dt>Aset diproses</dt><dd>{number.format(data.stored.total_aset)}</dd></div>
          <div className="metric"><dt>Penyusutan {year}</dt><dd className="small">{money(data.stored.total_penyusutan)}</dd></div>
          <div className="metric"><dt>Akumulasi</dt><dd className="small">{money(data.stored.total_akumulasi)}</dd></div>
          <div className="metric"><dt>Nilai buku</dt><dd className="small">{money(data.stored.total_nilai_buku)}</dd></div>
        </dl>}
      <div className="filters" role="group" aria-label="Laporan penyusutan">
        {([['golongan', 'Per golongan'], ['tahun', 'Per tahun perolehan'], ['detail', 'Detail per aset']] as const).map(([value, label]) =>
          <button key={value} className="secondary" aria-pressed={tab === value} onClick={() => setTab(value)}>{label}</button>)}
      </div>
      {tab === 'golongan' && <ReportTable caption={`Laporan per golongan ${year}`} head={['Golongan', 'Umur', 'Aset', 'Nilai perolehan', 'Penyusutan tahun ini', 'Akumulasi', 'Nilai buku']}
        rows={data.golongan.map((row) => [golonganNames[row.golongan], row.umur_manfaat ? row.umur_manfaat + ' th' : '—', number.format(row.jumlah_aset),
          money(row.total_nilai_perolehan), money(row.total_penyusutan_tahun), money(row.total_akumulasi), money(row.total_nilai_buku)])}
        total={['Total', '', number.format(data.golongan.reduce((sum, row) => sum + Number(row.jumlah_aset), 0)),
          money(data.golongan.reduce((sum, row) => sum + Number(row.total_nilai_perolehan), 0)),
          money(data.golongan.reduce((sum, row) => sum + Number(row.total_penyusutan_tahun), 0)),
          money(data.golongan.reduce((sum, row) => sum + Number(row.total_akumulasi), 0)),
          money(data.golongan.reduce((sum, row) => sum + Number(row.total_nilai_buku), 0))]} />}
      {tab === 'tahun' && (data.tahun_perolehan.length ? <ReportTable caption={`Laporan per tahun perolehan ${year}`}
        head={['Tahun perolehan', 'Aset', 'Nilai perolehan', 'Penyusutan', 'Akumulasi', 'Nilai buku']}
        rows={data.tahun_perolehan.map((row) => [row.tahun_perolehan ?? '—', number.format(row.jumlah_aset), money(row.total_nilai_perolehan),
          money(row.total_penyusutan), money(row.total_akumulasi), money(row.total_nilai_buku)])} />
        : <p>Belum ada data tersimpan untuk tahun {year}.</p>)}
      {tab === 'detail' && <DetailReport open={openKartu} />}
      {kartu && <KartuAset kartu={kartu} close={() => setKartu(null)} />}
    </>}
  </section>;
}

function ReportTable({ caption, head, rows, total }: { caption: string; head: string[]; rows: (string | number)[][]; total?: (string | number)[] }) {
  return <div className="inventory-scroll" tabIndex={0} role="region" aria-label={caption}>
    <table>
      <thead><tr>{head.map((label) => <th key={label} scope="col">{label}</th>)}</tr></thead>
      <tbody>{rows.map((row, index) => <tr key={index}>{row.map((cell, cellIndex) => cellIndex === 0
        ? <th key={cellIndex} scope="row">{cell}</th> : <td key={cellIndex} className="num">{cell}</td>)}</tr>)}</tbody>
      {total && <tfoot><tr>{total.map((cell, index) => index === 0 ? <th key={index} scope="row">{cell}</th> : <td key={index} className="num"><strong>{cell}</strong></td>)}</tr></tfoot>}
    </table>
  </div>;
}

function DetailReport({ open }: { open: (id: number) => void }) {
  const [options, setOptions] = useState<{ rooms: Option[]; categories: Option[] } | null>(null);
  const [filters, setFilters] = useState({ golongan: '', ruangan: '', kategori: '' });
  const [state, setState] = useState<{ rows?: DepreciationRow[]; error?: string }>({});

  useEffect(() => {
    rpc<{ rooms: Option[]; categories: Option[] }>('simbar_aset_options').then(setOptions).catch(() => {});
  }, []);
  useEffect(() => {
    let active = true;
    setState({});
    rpc<DepreciationRow[]>('simbar_penyusutan_detail', { filters, tanggal: null })
      .then((rows) => active && setState({ rows })).catch((error: Error) => active && setState({ error: error.message }));
    return () => { active = false; };
  }, [filters]);

  function apply(event: FormEvent<HTMLFormElement>) {
    event.preventDefault();
    const data = new FormData(event.currentTarget);
    setFilters({ golongan: String(data.get('golongan')), ruangan: String(data.get('ruangan')), kategori: String(data.get('kategori')) });
  }

  const rows = state.rows ?? [];
  const sum = (key: 'nilai_perolehan' | 'akumulasi_penyusutan' | 'nilai_buku') => rows.reduce((total, row) => total + Number(row[key]), 0);
  return <>
    <form className="asset-filter" onSubmit={apply}>
      <label>Golongan<select name="golongan" defaultValue={filters.golongan}><option value="">Semua</option>
        {Object.entries(golonganNames).map(([key, label]) => <option key={key} value={key}>{label}</option>)}</select></label>
      <label>Ruangan<select name="ruangan" defaultValue={filters.ruangan}><option value="">Semua</option>
        {options?.rooms.map((room) => <option key={room.id} value={room.id}>{room.name}</option>)}</select></label>
      <label>Kategori<select name="kategori" defaultValue={filters.kategori}><option value="">Semua</option>
        {options?.categories.map((entry) => <option key={entry.id} value={entry.id}>{entry.name}</option>)}</select></label>
      <div className="form-actions wide"><button className="primary" type="submit">Terapkan</button></div>
    </form>
    {state.error ? <p className="notice" role="alert">{state.error}</p> : !state.rows ? <p role="status">Menghitung penyusutan…</p> : <>
      <p role="status">{number.format(rows.length)} aset · perolehan {money(sum('nilai_perolehan'))} · akumulasi {money(sum('akumulasi_penyusutan'))} · nilai buku {money(sum('nilai_buku'))}</p>
      <div className="inventory-scroll" tabIndex={0} role="region" aria-label="Detail penyusutan per aset">
        <table>
          <thead><tr><th scope="col">Aset</th><th scope="col">Golongan</th><th scope="col">Perolehan</th><th scope="col">Umur</th>
            <th scope="col">Akumulasi</th><th scope="col">Nilai buku</th><th scope="col"><span className="sr-only">Aksi</span></th></tr></thead>
          <tbody>{rows.map((row) => <tr key={row.id}>
            <th scope="row"><strong>{row.nama_aset}</strong><span>{row.kode_aset} · {row.ruangan}</span></th>
            <td>{golonganNames[row.golongan]}</td>
            <td className="num">{money(row.nilai_perolehan)}<span className="cell-note">{row.tahun_perolehan ?? ''}</span></td>
            <td>{row.dapat_disusutkan ? `${row.umur_manfaat} th` : 'Tidak disusutkan'}{row.sudah_habis && <span className="cell-note warn">Habis umur</span>}</td>
            <td className="num">{money(row.akumulasi_penyusutan)}<span className="cell-note">{number.format(row.persentase_penyusutan)}%</span></td>
            <td className="num">{money(row.nilai_buku)}</td>
            <td><button className="secondary" onClick={() => open(row.id)}>Kartu</button></td>
          </tr>)}</tbody>
        </table>
      </div>
    </>}
  </>;
}

function KartuAset({ kartu, close }: { kartu: Kartu; close: () => void }) {
  return <div className="record-form">
    <h3>Kartu aset · {kartu.nama_aset}</h3>
    <dl className="detail-list">
      <div><dt>Kode</dt><dd>{kartu.kode_aset}</dd></div>
      <div><dt>Golongan</dt><dd>{golonganNames[kartu.golongan]} · {kartu.kategori}</dd></div>
      <div><dt>Tanggal perolehan</dt><dd>{kartu.tanggal_perolehan}</dd></div>
      <div><dt>Nilai perolehan</dt><dd>{money(kartu.nilai_perolehan)} (residu {money(kartu.nilai_residu)})</dd></div>
      <div><dt>Umur manfaat</dt><dd>{kartu.dapat_disusutkan ? `${kartu.umur_manfaat} tahun` : 'Tidak disusutkan'}</dd></div>
      <div><dt>Penyusutan</dt><dd>{money(kartu.penyusutan_per_tahun)}/tahun · {money(kartu.penyusutan_per_bulan)}/bulan</dd></div>
      <div><dt>Sudah berjalan</dt><dd>{number.format(kartu.jumlah_bulan)} bulan{kartu.sisa_umur_bulan !== null && ` · sisa ${number.format(kartu.sisa_umur_bulan)} bulan`}</dd></div>
      <div><dt>Posisi hari ini</dt><dd>Akumulasi {money(kartu.akumulasi_penyusutan)} · nilai buku {money(kartu.nilai_buku)}</dd></div>
    </dl>
    {kartu.riwayat.length ? <ReportTable caption="Riwayat penyusutan tersimpan" head={['Periode', 'Penyusutan', 'Akumulasi awal', 'Akumulasi akhir', 'Nilai buku akhir']}
      rows={kartu.riwayat.map((entry) => [entry.bulan ? `${entry.bulan}/${entry.tahun}` : entry.tahun, money(entry.penyusutan_periode),
        money(entry.akumulasi_awal), money(entry.akumulasi_akhir), money(entry.nilai_buku_akhir)])} />
      : <p className="wide">Belum ada penyusutan tersimpan untuk aset ini.</p>}
    <div className="form-actions wide"><button className="secondary" onClick={close}>Tutup</button></div>
  </div>;
}

// Barang aset CSV export (BarangAsetController::exportExcel) with today's depreciation columns.
export async function exportAssetsCsv(filters: { ruangan?: string; kategori?: string; kondisi?: string }) {
  const rows = await rpc<DepreciationRow[]>('simbar_penyusutan_detail', { filters: { ...filters, aktif_only: false }, tanggal: null });
  const kondisi: Record<string, string> = { baik: 'Baik', rusak_ringan: 'Rusak Ringan', rusak_berat: 'Rusak Berat' };
  downloadCsv(`barang_aset_${stamp()}.csv`, ['No', 'Kode Aset', 'Nama Aset', 'Kategori', 'Golongan', 'Ruangan', 'Kondisi', 'Jumlah',
    'Tahun Perolehan', 'Harga Perolehan', 'Umur Manfaat', 'Akum. Penyusutan', 'Nilai Buku', 'Sumber Dana', 'Merk'],
  rows.map((row, index) => [index + 1, row.kode_aset, row.nama_aset, row.kategori, row.golongan, row.ruangan, kondisi[row.kondisi] ?? row.kondisi,
    row.jumlah, row.tahun_perolehan ?? '-', row.nilai_perolehan, row.umur_manfaat, row.akumulasi_penyusutan, row.nilai_buku,
    row.sumber_dana ?? '-', row.merk ?? '-']));
}
