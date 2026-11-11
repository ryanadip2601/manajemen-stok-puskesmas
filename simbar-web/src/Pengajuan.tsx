import { useEffect, useState, type FormEvent } from 'react';
import { rpc } from './supabase';
import { ItemThumb, PrivateThumb } from './media';
import { MyPickups } from './Pickup';
import { MyPermintaan } from './Permintaan';
import { MyUsulan } from './Usulan';
import { Pemeliharaan } from './Pemeliharaan';

// Pengajuan: one place for everything a person asks of the warehouse. "Buat pengajuan" asks what they need
// (ambil, minta, lapor aset) instead of making them pick a menu; the list below shows all their requests together.
export type RequestKind = 'pickup' | 'permintaan' | 'usulan' | 'pemeliharaan';
type Bucket = 'all' | 'menunggu' | 'revisi' | 'disetujui' | 'ditolak' | 'dibatalkan';
type Row = {
  kind: RequestKind; id: number; code: string | null; title: string; jumlah: number | null; satuan: string | null;
  tanggal: string; status: string; bucket: Exclude<Bucket, 'all'>; note: string | null; photo?: string | null; aset_foto?: string | null;
};
type Page = { kinds: RequestKind[]; counts: Record<Bucket, number>; total: number; rows: Row[] };
type View = { mode: 'list' } | { mode: 'choose' } | { mode: 'kind'; kind: RequestKind; start?: boolean; openId?: number };

export const REQUEST_KINDS: Record<RequestKind, { title: string; short: string; icon: string; color: string; when: string }> = {
  pickup: { title: 'Ambil barang dari gudang', short: 'Pengambilan', icon: 'fa-hand-holding', color: 'orange',
    when: 'Barangnya ada di gudang dan ingin dipakai. Setelah disetujui, stok langsung berkurang.' },
  permintaan: { title: 'Minta barang yang habis atau belum ada', short: 'Permintaan', icon: 'fa-clipboard-list', color: 'amber',
    when: 'Stok habis atau menipis, atau barangnya belum pernah ada di gudang.' },
  usulan: { title: 'Laporkan barang baru di ruangan', short: 'Usulan aset', icon: 'fa-file-signature', color: 'blue',
    when: 'Ruangan menerima barang baru, seperti meja, komputer atau alat medis, yang perlu dicatat sebagai aset.' },
  pemeliharaan: { title: 'Laporkan aset rusak', short: 'Pemeliharaan', icon: 'fa-screwdriver-wrench', color: 'purple',
    when: 'Aset di ruangan rusak atau perlu diperbaiki. Gudang menindaklanjuti dan memperbarui statusnya.' },
};
const ORDER: RequestKind[] = ['pickup', 'permintaan', 'usulan', 'pemeliharaan'];
const BUCKETS: [Bucket, string][] = [['all', 'Semua'], ['menunggu', 'Menunggu'], ['revisi', 'Perlu revisi'],
  ['disetujui', 'Disetujui / selesai'], ['ditolak', 'Ditolak'], ['dibatalkan', 'Dibatalkan']];
const STATUS: Record<RequestKind, Record<string, string>> = {
  pickup: { pending: 'Menunggu', approved: 'Disetujui', completed: 'Selesai', rejected: 'Ditolak', cancelled: 'Dibatalkan' },
  permintaan: { pending: 'Menunggu verifikasi', diproses: 'Sedang diproses', disetujui: 'Disetujui', ditolak: 'Ditolak',
    revisi: 'Perlu revisi', tersedia: 'Barang tersedia', dibaca: 'Sudah dibaca' },
  usulan: { pending: 'Menunggu verifikasi', diproses: 'Sedang diproses', disetujui: 'Disetujui', ditolak: 'Ditolak', revisi: 'Perlu revisi' },
  pemeliharaan: { menunggu: 'Menunggu', proses: 'Diproses', selesai: 'Selesai', rusak: 'Rusak (tidak dapat diperbaiki)' },
};
const BADGE_CLASS: Record<string, string> = { menunggu: 'pending', proses: 'diproses', selesai: 'completed', rusak: 'rejected' };
const perPage = 15;
const number = new Intl.NumberFormat('id-ID');
const dateFormat = new Intl.DateTimeFormat('id-ID', { dateStyle: 'medium', timeZone: 'UTC' });

export function Pengajuan({ allowed, initial, canExport }: { allowed: RequestKind[]; initial?: RequestKind; canExport: boolean }) {
  const kinds = ORDER.filter((kind) => allowed.includes(kind));
  const [view, setView] = useState<View>(initial && kinds.includes(initial) ? { mode: 'kind', kind: initial } : { mode: 'list' });
  const [version, setVersion] = useState(0);
  const back = () => { setView({ mode: 'list' }); setVersion((v) => v + 1); window.scrollTo(0, 0); };

  if (!kinds.length) return <section className="panel"><h2>Pengajuan</h2><p role="status">Peran ini belum diberi jenis pengajuan.</p></section>;
  if (view.mode === 'kind') {
    const { kind, start, openId } = view;
    return kind === 'pickup' ? <MyPickups key={'p' + String(start)} start={start} onBack={back} />
      : kind === 'permintaan' ? <MyPermintaan key={'m' + String(start) + openId} start={start} openId={openId} onBack={back} />
      : kind === 'usulan' ? <MyUsulan key={'u' + String(start) + openId} canExport={canExport} start={start} openId={openId} onBack={back} />
      : <Pemeliharaan key={'r' + String(start) + openId} warehouse={canExport} start={start} openId={openId} onBack={back} backLabel="Semua pengajuan" />;
  }
  if (view.mode === 'choose') return <section className="panel">
    <div className="panel-head">
      <h2>Buat pengajuan</h2>
      <button className="secondary" onClick={() => setView({ mode: 'list' })}>Batal</button>
    </div>
    <p>Apa yang Anda butuhkan?</p>
    <div className="request-choices">
      {kinds.map((kind) => <button key={kind} className={'request-choice c-' + REQUEST_KINDS[kind].color}
        onClick={() => setView({ mode: 'kind', kind, start: true })}>
        <span className="request-choice-icon" aria-hidden="true"><i className={'fas ' + REQUEST_KINDS[kind].icon} /></span>
        <strong>{REQUEST_KINDS[kind].title}</strong>
        <span>{REQUEST_KINDS[kind].when}</span>
      </button>)}
    </div>
  </section>;
  return <RequestList kinds={kinds} version={version}
    create={() => setView(kinds.length === 1 ? { mode: 'kind', kind: kinds[0], start: true } : { mode: 'choose' })}
    open={(row) => setView({ mode: 'kind', kind: row.kind, openId: row.kind === 'pickup' ? undefined : row.id })} />;
}

function RequestList({ kinds, version, create, open }: {
  kinds: RequestKind[]; version: number; create: () => void; open: (row: Row) => void;
}) {
  const [kind, setKind] = useState<'all' | RequestKind>('all');
  const [bucket, setBucket] = useState<Bucket>('all');
  const [draft, setDraft] = useState('');
  const [search, setSearch] = useState('');
  const [page, setPage] = useState(1);
  const [state, setState] = useState<{ data?: Page; error?: string }>({});

  useEffect(() => {
    let live = true;
    setState((current) => ({ data: current.data }));
    rpc<Page>('simbar_my_requests', { kind_filter: kind, bucket_filter: bucket, search_text: search, page_number: page })
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
  return <section className="panel">
    <div className="panel-head">
      <h2>Pengajuan saya</h2>
      <button className="primary" onClick={create}><i className="fas fa-plus" aria-hidden="true" /> Buat pengajuan</button>
    </div>
    <p>Semua pengambilan, permintaan dan usulan Anda di satu tempat. Pilih baris untuk melihat detail, mengubah atau membatalkan.</p>
    <div className="filters request-buckets" role="group" aria-label="Status pengajuan">
      {BUCKETS.filter(([value]) => value !== 'dibatalkan' || (data?.counts.dibatalkan ?? 0) > 0).map(([value, label]) =>
        <button key={value} className="secondary" aria-pressed={bucket === value} onClick={() => { setBucket(value); setPage(1); }}>
          {label}{data && <span className="request-count">{number.format(data.counts[value] ?? 0)}</span>}
        </button>)}
    </div>
    <form className="filters" role="search" onSubmit={submit}>
      {kinds.length > 1 && <select aria-label="Jenis pengajuan" value={kind} onChange={(event) => { setKind(event.target.value as typeof kind); setPage(1); }}>
        <option value="all">Semua jenis</option>
        {kinds.map((value) => <option key={value} value={value}>{REQUEST_KINDS[value].short}</option>)}
      </select>}
      <input type="search" aria-label="Cari pengajuan" placeholder="Nama barang atau kode" maxLength={100}
        value={draft} onChange={(event) => setDraft(event.target.value)} />
      <button className="primary" type="submit">Cari</button>
      {search && <button className="secondary" type="button" onClick={() => { setDraft(''); setSearch(''); setPage(1); }}>Hapus filter</button>}
    </form>
    {state.error ? <p className="notice" role="alert">{state.error}</p> : !data ? <p role="status">Memuat pengajuan…</p>
      : !data.total ? <p role="status">{bucket === 'all' && kind === 'all' && !search
        ? 'Belum ada pengajuan. Tekan "Buat pengajuan" untuk mulai.' : 'Tidak ada pengajuan pada filter ini.'}</p> : <>
        <div className="inventory-scroll" tabIndex={0} role="region" aria-label="Tabel pengajuan saya">
          <table className="request-table">
            <thead><tr><th scope="col"><span className="sr-only">Foto</span></th><th scope="col">Barang</th><th scope="col">Jenis</th><th scope="col">Jumlah</th><th scope="col">Tanggal</th>
              <th scope="col">Status</th><th scope="col"><span className="sr-only">Aksi</span></th></tr></thead>
            <tbody>{data.rows.map((row) => <tr key={row.kind + row.id}>
              <td className="thumb-cell">{row.aset_foto ? <PrivateThumb reference={row.aset_foto} alt={'Foto ' + row.title} size={96} display={44} caption={row.title} />
                : <ItemThumb url={row.photo} name={row.title} />}</td>
              <th scope="row"><strong>{row.title}</strong>{row.code && <span>{row.code}</span>}</th>
              <td><span className={'request-kind c-' + REQUEST_KINDS[row.kind].color}>
                <i className={'fas ' + REQUEST_KINDS[row.kind].icon} aria-hidden="true" /> {REQUEST_KINDS[row.kind].short}</span></td>
              <td>{row.jumlah === null ? '—' : number.format(row.jumlah) + (row.satuan ? ' ' + row.satuan : '')}</td>
              <td>{dateFormat.format(new Date(row.tanggal.slice(0, 10)))}</td>
              <td><span className={'badge ' + (row.kind === 'pemeliharaan' ? BADGE_CLASS[row.status] ?? '' : row.status)}>{STATUS[row.kind][row.status] ?? row.status}</span>
                {row.note && (row.bucket === 'revisi' || row.bucket === 'ditolak') && <small className="request-note">{row.note}</small>}</td>
              <td><button className={row.bucket === 'revisi' ? 'primary' : 'secondary'} onClick={() => open(row)}>
                {row.bucket === 'revisi' ? 'Perbaiki' : row.kind === 'pickup' ? 'Buka' : 'Detail'}</button></td>
            </tr>)}</tbody>
          </table>
        </div>
        <nav className="inventory-pages" aria-label="Halaman pengajuan">
          <button className="secondary" disabled={page <= 1} onClick={() => setPage(page - 1)}>Sebelumnya</button>
          <span>Halaman {number.format(page)} dari {number.format(lastPage)}</span>
          <button className="secondary" disabled={page >= lastPage} onClick={() => setPage(page + 1)}>Berikutnya</button>
        </nav>
      </>}
  </section>;
}
