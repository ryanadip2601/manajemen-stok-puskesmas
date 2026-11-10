import { useEffect, useState } from 'react';
import { GroupedBars, RankedBars } from './charts';
import { rpc } from './supabase';

type Board = {
  range_days: number;
  stats: { total_items: number; total_stock: number; low_stock: number; categories: number; assets: number };
  low_stock_items: { id: number; code: string; name: string; stock: number; minimum: number; unit: string }[];
  monthly: { month: string; stock_in: number; stock_out: number }[];
  categories: { name: string; items: number; stock: number }[];
  top_items: { name: string; stock: number; unit: string }[];
  recent: { kind: 'in' | 'out'; id: number; date: string; quantity: number; name: string; unit: string; petugas: string | null }[];
  queues?: Record<'pending_pickup' | 'pending_bhp' | 'pending_usulan' | 'pending_pemeliharaan' | 'pending_users', number>;
  funnel?: { total: number; pending: number; approved: number; completed: number; rejected: number; cancelled: number;
    avg_approval_hours: number; processed_within_24h: number; processed: number };
  pareto?: { name: string; unit: string; total_out: number }[];
  aging?: Record<string, number>;
};
export type Section = 'checklist' | 'verifikasi' | 'usulan-admin' | 'pemeliharaan' | 'users' | 'catalog';

const number = new Intl.NumberFormat('id-ID');
const monthFormat = new Intl.DateTimeFormat('id-ID', { month: 'short', year: '2-digit', timeZone: 'UTC' });
const dateFormat = new Intl.DateTimeFormat('id-ID', { dateStyle: 'medium', timeZone: 'UTC' });

export function Overview({ go, isAdmin }: { go: (section: Section) => void; isAdmin: boolean }) {
  const [range, setRange] = useState(30);
  const [state, setState] = useState<{ data?: Board; error?: string }>({});
  useEffect(() => {
    let active = true;
    rpc<Board>('simbar_dashboard', { range_days: range })
      .then((data) => active && setState({ data })).catch((error: Error) => active && setState({ error: error.message }));
    return () => { active = false; };
  }, [range]);

  if (state.error) return <p className="notice" role="alert">{state.error}</p>;
  if (!state.data) return <p role="status">Memuat ringkasan…</p>;
  const data = state.data;
  const queues = data.queues && ([
    ['pending_pickup', 'Pengambilan menunggu', 'checklist'], ['pending_bhp', 'Permintaan barang', 'verifikasi'],
    ['pending_usulan', 'Usulan penerimaan', 'usulan-admin'], ['pending_pemeliharaan', 'Pemeliharaan menunggu', 'pemeliharaan'],
    ...(isAdmin ? [['pending_users', 'Akun menunggu persetujuan', 'users']] : []),
  ] as [keyof NonNullable<Board['queues']>, string, Section][]);

  return <>
    <dl className="metrics">
      <div className="metric"><dt>Jenis barang</dt><dd>{number.format(data.stats.total_items)}</dd></div>
      <div className="metric"><dt>Total stok</dt><dd>{number.format(data.stats.total_stock)}</dd></div>
      <div className="metric"><dt>Stok menipis</dt><dd>{number.format(data.stats.low_stock)}</dd></div>
      <div className="metric"><dt>Aset tercatat</dt><dd>{number.format(data.stats.assets)}</dd></div>
    </dl>
    {queues && <section className="panel">
      <h2>Perlu ditindaklanjuti</h2>
      <ul className="queue-list">{queues.map(([key, label, section]) => <li key={key}>
        <button className="secondary" onClick={() => go(section)}><strong>{number.format(data.queues![key])}</strong> {label}</button>
      </li>)}</ul>
    </section>}
    <section className="panel">
      <GroupedBars title="Barang masuk dan keluar, 6 bulan terakhir" labels={data.monthly.map((entry) => monthFormat.format(new Date(entry.month + '-01')))}
        series={[{ name: 'Masuk', values: data.monthly.map((entry) => Number(entry.stock_in)) },
          { name: 'Keluar', values: data.monthly.map((entry) => Number(entry.stock_out)) }]} />
    </section>
    <div className="overview-grid">
      <section className="panel">
        <h2>Stok menipis</h2>
        {data.low_stock_items.length ? <ul className="photo-list">{data.low_stock_items.map((item) => <li key={item.id}>
          <div><strong>{item.name}</strong><span>{item.code} · minimum {number.format(item.minimum)}</span></div>
          <span className={item.stock === 0 ? 'badge rejected' : 'badge pending'}>{number.format(item.stock)} {item.unit}</span>
        </li>)}</ul> : <p>Semua stok di atas minimum.</p>}
      </section>
      <section className="panel">
        <RankedBars title="Stok per jenis barang" rows={data.categories.filter((entry) => Number(entry.stock) > 0).slice(0, 8)
          .map((entry) => ({ label: entry.name, value: Number(entry.stock), note: `(${number.format(entry.items)} barang)` }))} />
      </section>
    </div>
    {data.funnel && data.pareto && data.aging && <>
      <div className="panel-head insight-head">
        <h2>Insight pengambilan barang</h2>
        <div className="filters" role="group" aria-label="Rentang waktu">
          {[30, 90, 180].map((days) => <button key={days} className="secondary" aria-pressed={range === days} onClick={() => setRange(days)}>{days} hari</button>)}
        </div>
      </div>
      <div className="overview-grid">
        <section className="panel">
          <h2>Status permintaan ({data.range_days} hari)</h2>
          <dl className="detail-list">
            <div><dt>Total permintaan</dt><dd>{number.format(data.funnel.total)}</dd></div>
            <div><dt>Selesai</dt><dd>{number.format(data.funnel.completed)}</dd></div>
            <div><dt>Menunggu</dt><dd>{number.format(data.funnel.pending)}</dd></div>
            <div><dt>Ditolak / dibatalkan</dt><dd>{number.format(data.funnel.rejected)} / {number.format(data.funnel.cancelled)}</dd></div>
            <div><dt>Rata-rata waktu proses</dt><dd>{number.format(data.funnel.avg_approval_hours)} jam</dd></div>
            <div><dt>Diproses ≤ 24 jam</dt><dd>{data.funnel.processed ? number.format(Math.round(data.funnel.processed_within_24h / data.funnel.processed * 1000) / 10) + '%' : '—'}</dd></div>
          </dl>
        </section>
        <section className="panel">
          <RankedBars title={`Barang paling banyak keluar (${data.range_days} hari)`}
            rows={data.pareto.map((entry) => ({ label: entry.name, value: Number(entry.total_out), note: entry.unit }))} />
        </section>
        <section className="panel">
          <RankedBars title="Umur pergerakan stok (sejak transaksi terakhir)"
            rows={Object.entries(data.aging).map(([label, value]) => ({ label, value: Number(value), note: 'barang' }))} />
        </section>
        <section className="panel">
          <h2>Transaksi terbaru</h2>
          <ul className="photo-list">{data.recent.map((entry) => <li key={entry.kind + entry.id}>
            <div><strong>{entry.name}</strong><span>{dateFormat.format(new Date(entry.date))} · {entry.petugas ?? '—'}</span></div>
            <span className={entry.kind === 'in' ? 'badge completed' : 'badge diproses'}>{entry.kind === 'in' ? '+' : '−'}{number.format(entry.quantity)} {entry.unit}</span>
          </li>)}</ul>
        </section>
      </div>
    </>}
  </>;
}
