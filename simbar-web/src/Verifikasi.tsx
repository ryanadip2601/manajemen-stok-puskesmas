import { useEffect, useState, type FormEvent } from 'react';
import { rpc } from './supabase';
import { ItemThumb, PrivateThumb } from './media';
import { Checklist } from './Pickup';
import { AdminPermintaan } from './Permintaan';
import { AdminUsulan } from './Usulan';
import { Pemeliharaan } from './Pemeliharaan';

// Verifikasi: the warehouse inbox. Everything people asked for, in one queue, waiting items oldest first.
// Choosing a row opens the existing approval screen of that kind; "Kembali ke antrean" comes back here.
export type QueueKind = 'pickup' | 'permintaan' | 'usulan' | 'pemeliharaan';
type Bucket = 'all' | 'menunggu' | 'revisi' | 'selesai' | 'ditolak' | 'dibatalkan';
type Row = {
  kind: QueueKind; id: number; code: string | null; title: string; requester: string | null; jumlah: number | null;
  satuan: string | null; tanggal: string; status: string; bucket: Exclude<Bucket, 'all'>; note: string | null;
  photo?: string | null; aset_foto?: string | null;
};
type Queue = { kinds: QueueKind[]; waiting: Record<'all' | QueueKind, number>; counts: Record<Bucket, number>; total: number; rows: Row[] };

export const KINDS: Record<QueueKind, { label: string; icon: string; color: string }> = {
  pickup: { label: 'Pengambilan', icon: 'fa-hand-holding', color: 'orange' },
  permintaan: { label: 'Permintaan', icon: 'fa-clipboard-list', color: 'amber' },
  usulan: { label: 'Usulan aset', icon: 'fa-file-signature', color: 'blue' },
  pemeliharaan: { label: 'Pemeliharaan', icon: 'fa-screwdriver-wrench', color: 'purple' },
};
const BUCKETS: [Bucket, string][] = [['menunggu', 'Menunggu'], ['revisi', 'Di pengaju (revisi)'], ['selesai', 'Selesai'],
  ['ditolak', 'Ditolak'], ['dibatalkan', 'Dibatalkan'], ['all', 'Semua']];
export const STATUS: Record<QueueKind, Record<string, string>> = {
  pickup: { pending: 'Menunggu', approved: 'Disetujui', completed: 'Selesai', rejected: 'Ditolak', cancelled: 'Dibatalkan' },
  permintaan: { pending: 'Menunggu verifikasi', diproses: 'Sedang diproses', disetujui: 'Disetujui', ditolak: 'Ditolak',
    revisi: 'Perlu revisi', tersedia: 'Barang tersedia', dibaca: 'Sudah dibaca' },
  usulan: { pending: 'Menunggu verifikasi', diproses: 'Sedang diproses', disetujui: 'Disetujui', ditolak: 'Ditolak', revisi: 'Perlu revisi' },
  pemeliharaan: { menunggu: 'Menunggu', proses: 'Diproses', selesai: 'Selesai', rusak: 'Rusak' },
};
export const BADGE: Record<string, string> = { menunggu: 'pending', proses: 'diproses', selesai: 'completed', rusak: 'rejected' };
const perPage = 15;
const number = new Intl.NumberFormat('id-ID');
const dateFormat = new Intl.DateTimeFormat('id-ID', { dateStyle: 'medium', timeZone: 'UTC' });
const ageDays = (value: string) => Math.floor((Date.now() - new Date(value.slice(0, 10)).getTime()) / 86_400_000);

export function Verifikasi({ initial, onChange }: { initial?: QueueKind; onChange?: () => void }) {
  const [view, setView] = useState<{ kind: QueueKind; openId?: number } | null>(initial ? { kind: initial } : null);
  const [version, setVersion] = useState(0);
  const back = () => { setView(null); setVersion((v) => v + 1); onChange?.(); window.scrollTo(0, 0); };

  if (view) {
    const { kind, openId } = view;
    return kind === 'pickup' ? <Checklist onBack={back} />
      : kind === 'permintaan' ? <AdminPermintaan key={openId} openId={openId} onBack={back} />
      : kind === 'usulan' ? <AdminUsulan key={openId} openId={openId} onBack={back} />
      : <Pemeliharaan key={openId} warehouse openId={openId} onBack={back} />;
  }
  return <QueueList version={version} open={(row) => setView({ kind: row.kind, openId: row.kind === 'pickup' ? undefined : row.id })}
    openKind={(kind) => setView({ kind })} />;
}

function QueueList({ version, open, openKind }: { version: number; open: (row: Row) => void; openKind: (kind: QueueKind) => void }) {
  const [kind, setKind] = useState<'all' | QueueKind>('all');
  const [bucket, setBucket] = useState<Bucket>('menunggu');
  const [draft, setDraft] = useState('');
  const [search, setSearch] = useState('');
  const [page, setPage] = useState(1);
  const [state, setState] = useState<{ data?: Queue; error?: string }>({});

  useEffect(() => {
    let live = true;
    setState((current) => ({ data: current.data }));
    rpc<Queue>('simbar_queue', { kind_filter: kind, bucket_filter: bucket, search_text: search, page_number: page })
      .then((data) => { if (live) setState({ data }); })
      .catch((error: Error) => { if (live) setState({ error: error.message }); });
    return () => { live = false; };
  }, [kind, bucket, search, page, version]);

  function submit(event: FormEvent) {
    event.preventDefault();
    setSearch(draft.trim());
    setPage(1);
  }

  const data = state.data;
  const lastPage = Math.max(1, Math.ceil((data?.total ?? 0) / perPage));
  const pick = (value: 'all' | QueueKind) => { setKind(value); setPage(1); };
  return <section className="panel">
    <h2>Verifikasi</h2>
    <p>Semua yang diajukan pegawai dan menunggu gudang, dari yang paling lama. Pilih baris untuk memproses.</p>
    {data && data.kinds.length > 0 && <div className="queue-summary" role="group" aria-label="Menunggu per jenis">
      <button className="queue-card" aria-pressed={kind === 'all'} onClick={() => pick('all')}>
        <strong>{number.format(data.waiting.all)}</strong><span>Semua menunggu</span></button>
      {data.kinds.map((value) => <button key={value} className={'queue-card c-' + KINDS[value].color} aria-pressed={kind === value} onClick={() => pick(value)}>
        <i className={'fas ' + KINDS[value].icon} aria-hidden="true" />
        <strong>{number.format(data.waiting[value])}</strong><span>{KINDS[value].label}</span></button>)}
    </div>}
    <div className="filters request-buckets" role="group" aria-label="Status">
      {BUCKETS.filter(([value]) => value !== 'dibatalkan' || (data?.counts.dibatalkan ?? 0) > 0).map(([value, label]) =>
        <button key={value} className="secondary" aria-pressed={bucket === value} onClick={() => { setBucket(value); setPage(1); }}>
          {label}{data && <span className="request-count">{number.format(data.counts[value] ?? 0)}</span>}
        </button>)}
    </div>
    <form className="filters" role="search" onSubmit={submit}>
      <input type="search" aria-label="Cari di antrean" placeholder="Barang, kode atau nama pengaju" maxLength={100}
        value={draft} onChange={(event) => setDraft(event.target.value)} />
      <button className="primary" type="submit">Cari</button>
      {search && <button className="secondary" type="button" onClick={() => { setDraft(''); setSearch(''); setPage(1); }}>Hapus filter</button>}
      {kind !== 'all' && <button className="secondary" type="button" onClick={() => openKind(kind)}>
        Buka halaman {KINDS[kind].label.toLowerCase()}</button>}
    </form>
    {state.error ? <p className="notice" role="alert">{state.error}</p> : !data ? <p role="status">Memuat antrean…</p>
      : !data.kinds.length ? <p role="status">Peran ini belum diberi hak verifikasi.</p>
      : !data.total ? <p role="status">{bucket === 'menunggu' ? 'Tidak ada yang menunggu. Semua sudah diproses.' : 'Tidak ada data pada filter ini.'}</p> : <>
        <div className="inventory-scroll" tabIndex={0} role="region" aria-label="Tabel antrean verifikasi">
          <table className="request-table">
            <thead><tr><th scope="col"><span className="sr-only">Foto</span></th><th scope="col">Barang</th><th scope="col">Jenis</th><th scope="col">Pengaju</th><th scope="col">Jumlah</th>
              <th scope="col">Tanggal</th><th scope="col">Status</th><th scope="col"><span className="sr-only">Aksi</span></th></tr></thead>
            <tbody>{data.rows.map((row) => {
              const age = ageDays(row.tanggal);
              return <tr key={row.kind + row.id}>
                <td className="thumb-cell">{row.aset_foto ? <PrivateThumb reference={row.aset_foto} alt={'Foto ' + row.title} size={96} display={44} caption={row.title} />
                  : <ItemThumb url={row.photo} name={row.title} />}</td>
                <th scope="row"><strong>{row.title}</strong>{row.code && <span>{row.code}</span>}</th>
                <td><span className={'request-kind c-' + KINDS[row.kind].color}>
                  <i className={'fas ' + KINDS[row.kind].icon} aria-hidden="true" /> {KINDS[row.kind].label}</span></td>
                <td>{row.requester ?? '—'}</td>
                <td>{row.jumlah === null ? '—' : number.format(row.jumlah) + (row.satuan ? ' ' + row.satuan : '')}</td>
                <td>{dateFormat.format(new Date(row.tanggal.slice(0, 10)))}
                  {row.bucket === 'menunggu' && age > 0 && <small className={'request-note' + (age >= 3 ? ' queue-late' : '')}>{age} hari menunggu</small>}</td>
                <td><span className={'badge ' + (row.kind === 'pemeliharaan' ? BADGE[row.status] ?? '' : row.status)}>{STATUS[row.kind][row.status] ?? row.status}</span>
                  {row.note && <small className="request-note">{row.note}</small>}</td>
                <td><button className={row.bucket === 'menunggu' ? 'primary' : 'secondary'} onClick={() => open(row)}>
                  {row.bucket === 'menunggu' ? 'Proses' : 'Detail'}</button></td>
              </tr>;
            })}</tbody>
          </table>
        </div>
        <nav className="inventory-pages" aria-label="Halaman antrean">
          <button className="secondary" disabled={page <= 1} onClick={() => setPage(page - 1)}>Sebelumnya</button>
          <span>Halaman {number.format(page)} dari {number.format(lastPage)}</span>
          <button className="secondary" disabled={page >= lastPage} onClick={() => setPage(page + 1)}>Berikutnya</button>
        </nav>
      </>}
  </section>;
}
