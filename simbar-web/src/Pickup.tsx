import { useEffect, useState, type FormEvent } from 'react';
import { rpc } from './supabase';

type Status = 'pending' | 'approved' | 'completed' | 'rejected' | 'cancelled';
type ItemOption = { id: number; code: string; name: string; stock: number; unit: string; category: string };
type Request = {
  id: number; status: Status; quantity: number; notes: string | null; request_date: string; pickup_date: string | null;
  reject_reason: string | null; item: string; code: string; stock: number; unit: string;
  requested_by: string; processed_by: string | null;
};
type RequestPage = { counts: Record<Status, number>; total: number; rows: Request[] };
type Line = { key: number; item: string; quantity: string; notes: string };

const perPage = 15;
const number = new Intl.NumberFormat('id-ID');
const dateFormat = new Intl.DateTimeFormat('id-ID', { dateStyle: 'medium', timeZone: 'UTC' });
const statusLabels: Record<Status, string> = {
  pending: 'Menunggu', approved: 'Disetujui', completed: 'Selesai', rejected: 'Ditolak', cancelled: 'Dibatalkan',
};
const date = (value: string | null) => value ? dateFormat.format(new Date(value.slice(0, 10))) : '—';

function useRequests(mine: boolean, status: 'all' | Status, page: number, version: number) {
  const [state, setState] = useState<{ data?: RequestPage; error?: string }>({});
  useEffect(() => {
    let active = true;
    setState({});
    rpc<RequestPage>('simbar_pickup_list', { mine_only: mine, status_filter: status, page_number: page })
      .then((data) => active && setState({ data })).catch((error: Error) => active && setState({ error: error.message }));
    return () => { active = false; };
  }, [mine, status, page, version]);
  return state;
}

function Message({ value }: { value: { text: string; ok: boolean } }) {
  return value.text ? <p className={value.ok ? 'success' : 'notice'} role={value.ok ? 'status' : 'alert'}>{value.text}</p> : null;
}

function RequestTable({ page, mine, children }: { page: RequestPage; mine: boolean; children: (row: Request) => React.ReactNode }) {
  return <div className="inventory-scroll" tabIndex={0} role="region" aria-label="Tabel pengambilan barang">
    <table>
      <thead><tr><th scope="col">Barang</th><th scope="col">Jumlah</th>{!mine && <th scope="col">Pemohon</th>}
        <th scope="col">Diajukan</th><th scope="col">Status</th><th scope="col"><span className="sr-only">Aksi</span></th></tr></thead>
      <tbody>{page.rows.map((row) => <tr key={row.id}>
        <th scope="row"><strong>{row.item}</strong><span>{row.code}{row.notes ? ' · ' + row.notes : ''}</span></th>
        <td>{number.format(row.quantity)} {row.unit}{!mine && row.status === 'pending' &&
          <span className={row.quantity > row.stock ? 'cell-note warn' : 'cell-note'}>stok {number.format(row.stock)}</span>}</td>
        {!mine && <td>{row.requested_by}</td>}
        <td>{date(row.request_date)}</td>
        <td><span className={'badge ' + row.status}>{statusLabels[row.status]}</span>
          {row.processed_by && <span className="cell-note">oleh {row.processed_by}</span>}
          {row.reject_reason && <span className="cell-note">{row.reject_reason}</span>}</td>
        <td>{children(row)}</td>
      </tr>)}</tbody>
    </table>
  </div>;
}

function Pages({ page, total, go }: { page: number; total: number; go: (page: number) => void }) {
  const last = Math.max(1, Math.ceil(total / perPage));
  return <nav className="inventory-pages" aria-label="Halaman pengambilan">
    <button className="secondary" disabled={page <= 1} onClick={() => go(page - 1)}>Sebelumnya</button>
    <span>Halaman {number.format(page)} dari {number.format(last)}</span>
    <button className="secondary" disabled={page >= last} onClick={() => go(page + 1)}>Berikutnya</button>
  </nav>;
}

// Opened from Pengajuan: `start` opens the form at once, `onBack` returns to the combined list.
export function MyPickups({ start, onBack }: { start?: boolean; onBack?: () => void } = {}) {
  const [items, setItems] = useState<ItemOption[] | null>(null);
  const [lines, setLines] = useState<Line[] | null>(null);
  const [page, setPage] = useState(1);
  const [version, setVersion] = useState(0);
  const [message, setMessage] = useState({ text: '', ok: true });
  const [busy, setBusy] = useState(false);
  const state = useRequests(true, 'all', page, version);
  useEffect(() => { if (start) open(); }, []); // eslint-disable-line react-hooks/exhaustive-deps

  async function open() {
    setMessage({ text: '', ok: true });
    try {
      setItems(await rpc<ItemOption[]>('simbar_pickup_options'));
      setLines([{ key: Date.now(), item: '', quantity: '1', notes: '' }]);
    } catch (error) {
      setMessage({ text: (error as Error).message, ok: false });
    }
  }

  async function act(action: () => Promise<unknown>, success: string) {
    setBusy(true);
    try {
      await action();
      setMessage({ text: success, ok: true });
      setLines(null);
      setVersion((value) => value + 1);
    } catch (error) {
      setMessage({ text: (error as Error).message, ok: false });
    } finally {
      setBusy(false);
    }
  }

  function submit(event: FormEvent) {
    event.preventDefault();
    if (!lines) return;
    const payload = lines.map((line) => ({ item_id: Number(line.item), quantity: Number(line.quantity), notes: line.notes.trim() }));
    act(() => rpc('simbar_pickup_submit', { lines: payload }),
      'Permintaan pengambilan berhasil diajukan. Mohon tunggu persetujuan dari Admin Gudang.');
  }

  const update = (key: number, patch: Partial<Line>) => setLines((current) => current?.map((line) => line.key === key ? { ...line, ...patch } : line) ?? null);
  const chosen = new Set(lines?.map((line) => line.item));

  return <section className="panel">
      {onBack && <button className="secondary back-link" onClick={onBack}><i className="fas fa-arrow-left" aria-hidden="true" /> Semua pengajuan</button>}
    <div className="panel-head">
      <h2>Pengambilan barang</h2>
      {!lines && <button className="primary" onClick={open}>Ajukan pengambilan</button>}
    </div>
    <p>Ajukan barang yang ingin diambil dari gudang. Admin Gudang akan memproses permintaan Anda.</p>
    <Message value={message} />
    {lines && items && <form className="record-form" onSubmit={submit}>
      <h3>Pengajuan baru</h3>
      {lines.map((line, index) => {
        const selected = items.find((item) => String(item.id) === line.item);
        return <fieldset className="pickup-line wide" key={line.key}>
          <legend>Barang {index + 1}</legend>
          <label>Barang<select required value={line.item} onChange={(event) => update(line.key, { item: event.target.value })}>
            <option value="" disabled>Pilih barang</option>
            {items.map((item) => <option key={item.id} value={item.id} disabled={chosen.has(String(item.id)) && line.item !== String(item.id)}>
              {item.name} — stok {number.format(item.stock)} {item.unit}</option>)}
          </select></label>
          <label>Jumlah{selected && ` (maks. ${number.format(selected.stock)})`}
            <input type="number" min="1" max={selected?.stock} step="1" required value={line.quantity}
              onChange={(event) => update(line.key, { quantity: event.target.value })} /></label>
          <label className="wide">Keterangan<input maxLength={500} value={line.notes} onChange={(event) => update(line.key, { notes: event.target.value })} /></label>
          {lines.length > 1 && <button className="secondary" type="button" onClick={() => setLines(lines.filter((entry) => entry.key !== line.key))}>Hapus baris</button>}
        </fieldset>;
      })}
      <div className="form-actions wide">
        <button className="secondary" type="button" disabled={lines.length >= 50}
          onClick={() => setLines([...lines, { key: Date.now(), item: '', quantity: '1', notes: '' }])}>Tambah barang</button>
        <button className="primary" type="submit" disabled={busy}>{busy ? 'Mengirim…' : 'Ajukan'}</button>
        <button className="secondary" type="button" onClick={() => setLines(null)}>Batal</button>
      </div>
    </form>}
    {state.error ? <p className="notice" role="alert">{state.error}</p> : !state.data ? <p role="status">Memuat pengajuan…</p>
      : !state.data.total ? <p role="status">Belum ada pengajuan.</p> : <>
        <RequestTable page={state.data} mine>{(row) => row.status === 'pending' &&
          <button className="secondary" disabled={busy} onClick={() => {
            if (confirm(`Batalkan permintaan ${row.item}?`)) act(() => rpc('simbar_pickup_cancel', { request_id: row.id }), 'Permintaan berhasil dibatalkan.');
          }}>Batalkan</button>}</RequestTable>
        <Pages page={page} total={state.data.total} go={setPage} />
      </>}
  </section>;
}

// Opened from Verifikasi: `onBack` returns to the queue.
export function Checklist({ onBack }: { onBack?: () => void } = {}) {
  const [status, setStatus] = useState<'all' | Status>('pending');
  const [page, setPage] = useState(1);
  const [version, setVersion] = useState(0);
  const [message, setMessage] = useState({ text: '', ok: true });
  const [busy, setBusy] = useState(false);
  const state = useRequests(false, status, page, version);

  async function act(row: Request, action: 'approve' | 'reject' | 'complete' | 'undo') {
    let reason: string | null = null;
    if (action === 'reject') {
      reason = prompt(`Alasan menolak ${row.item} untuk ${row.requested_by} (opsional):`, '');
      if (reason === null) return;
    } else if (action === 'approve' && !confirm(`Setujui ${row.item} sebanyak ${row.quantity} ${row.unit} untuk ${row.requested_by}? Stok langsung dikurangi.`)) {
      return;
    }
    setBusy(true);
    try {
      await rpc('simbar_checklist_act', { request_id: row.id, request_action: action, reason });
      setMessage({ text: {
        approve: 'Permintaan disetujui dan diselesaikan. Stok telah dikurangi.', reject: 'Permintaan berhasil ditolak.',
        complete: 'Pengambilan diselesaikan. Stok telah dikurangi.', undo: 'Persetujuan dibatalkan. Status kembali ke "Menunggu".',
      }[action], ok: true });
      setVersion((value) => value + 1);
    } catch (error) {
      setMessage({ text: (error as Error).message, ok: false });
    } finally {
      setBusy(false);
    }
  }

  const counts = state.data?.counts;
  const tabs: ['all' | Status, string][] = [['pending', 'Menunggu'], ['approved', 'Disetujui'], ['completed', 'Selesai'], ['rejected', 'Ditolak'], ['all', 'Semua']];

  return <section className="panel">
    {onBack && <button className="secondary back-link" onClick={onBack}><i className="fas fa-arrow-left" aria-hidden="true" /> Kembali ke antrean</button>}
    <h2>Checklist pengambilan</h2>
    <p>Setujui permintaan untuk langsung mencatat barang keluar.</p>
    <div className="filters" role="group" aria-label="Status permintaan">
      {tabs.map(([value, label]) => <button key={value} className="secondary" aria-pressed={status === value}
        onClick={() => { setStatus(value); setPage(1); }}>{label}{counts && value !== 'all' ? ` (${number.format(counts[value])})` : ''}</button>)}
    </div>
    <Message value={message} />
    {state.error ? <p className="notice" role="alert">{state.error}</p> : !state.data ? <p role="status">Memuat checklist…</p>
      : !state.data.total ? <p role="status">Tidak ada permintaan.</p> : <>
        <RequestTable page={state.data} mine={false}>{(row) => <div className="form-actions">
          {row.status === 'pending' && <>
            <button className="primary" disabled={busy || row.quantity > row.stock} onClick={() => act(row, 'approve')}>Setujui</button>
            <button className="danger" disabled={busy} onClick={() => act(row, 'reject')}>Tolak</button>
          </>}
          {row.status === 'approved' && <>
            <button className="primary" disabled={busy} onClick={() => act(row, 'complete')}>Selesaikan</button>
            <button className="secondary" disabled={busy} onClick={() => act(row, 'undo')}>Batalkan persetujuan</button>
          </>}
        </div>}</RequestTable>
        <Pages page={page} total={state.data.total} go={setPage} />
      </>}
  </section>;
}
