import { useEffect, useState, type FormEvent } from 'react';
import { rpc } from './supabase';

type Role = 'admin' | 'admin_gudang' | 'staff';
type UserRow = {
  id: number; name: string; email: string; phone: string | null; role: Role; approved: boolean; created_at: string | null;
  approved_at: string | null; last_login_at: string | null; last_activity_at: string | null; linked: boolean; online: boolean;
};
type UserPage = { stats: Record<string, number>; total: number; rows: UserRow[] };
type Detail = UserRow & {
  approved_by: string | null; last_ip_address: string | null;
  stats: { total_logins: number; total_activities: number; total_requests: number; completed_requests: number };
  logins: { action: string; ip_address: string | null; device: string; browser: string; platform: string; created_at: string }[];
  activities: { action: string; description: string | null; created_at: string }[];
};
type HistoryRow = { id: number; action: string; ip_address: string | null; device: string; browser: string; platform: string; created_at: string; name: string; role: Role };
type LogRow = { id: number; action: string; description: string | null; created_at: string; name: string | null };

const number = new Intl.NumberFormat('id-ID');
const when = new Intl.DateTimeFormat('id-ID', { dateStyle: 'medium', timeStyle: 'short', timeZone: 'UTC' });
const at = (value: string | null) => value ? when.format(new Date(value.replace(' ', 'T') + (value.endsWith('Z') ? '' : 'Z'))) : '—';
const roleNames: Record<Role, string> = { admin: 'Administrator', admin_gudang: 'Admin Gudang', staff: 'Staf' };
const text = (data: FormData, name: string) => String(data.get(name) ?? '').trim();

function Message({ value }: { value: { text: string; ok: boolean } }) {
  return value.text ? <p className={value.ok ? 'success' : 'notice'} role={value.ok ? 'status' : 'alert'}>{value.text}</p> : null;
}

function Pages({ page, total, size, go }: { page: number; total: number; size: number; go: (page: number) => void }) {
  const last = Math.max(1, Math.ceil(total / size));
  return <nav className="inventory-pages" aria-label="Halaman">
    <button className="secondary" disabled={page <= 1} onClick={() => go(page - 1)}>Sebelumnya</button>
    <span>Halaman {number.format(page)} dari {number.format(last)}</span>
    <button className="secondary" disabled={page >= last} onClick={() => go(page + 1)}>Berikutnya</button>
  </nav>;
}

export function Users({ selfId }: { selfId: number | null }) {
  const [tab, setTab] = useState<'users' | 'logins' | 'logs'>('users');
  return <section className="panel">
    <h2>Kelola pengguna</h2>
    <div className="filters" role="group" aria-label="Bagian pengguna">
      {([['users', 'Pengguna'], ['logins', 'Riwayat login'], ['logs', 'Log aktivitas']] as const).map(([value, label]) =>
        <button key={value} className="secondary" aria-pressed={tab === value} onClick={() => setTab(value)}>{label}</button>)}
    </div>
    {tab === 'users' ? <UserList selfId={selfId} /> : tab === 'logins' ? <LoginHistory /> : <ActivityLogs />}
  </section>;
}

function UserList({ selfId }: { selfId: number | null }) {
  const [filter, setFilter] = useState<'all' | 'pending' | 'approved' | 'online' | 'active' | 'today'>('pending');
  const [role, setRole] = useState('');
  const [search, setSearch] = useState('');
  const [page, setPage] = useState(1);
  const [version, setVersion] = useState(0);
  const [state, setState] = useState<{ data?: UserPage; error?: string }>({});
  const [detail, setDetail] = useState<Detail | null>(null);
  const [message, setMessage] = useState({ text: '', ok: true });
  const [busy, setBusy] = useState(false);

  useEffect(() => {
    let active = true;
    setState({});
    rpc<UserPage>('simbar_users_list', { user_filter: filter, role_filter: role || null, search_text: search, page_number: page })
      .then((data) => active && setState({ data })).catch((error: Error) => active && setState({ error: error.message }));
    return () => { active = false; };
  }, [filter, role, search, page, version]);

  async function open(id: number) {
    try {
      setDetail(await rpc<Detail>('simbar_user_detail', { target_id: id }));
    } catch (error) {
      setMessage({ text: (error as Error).message, ok: false });
    }
  }

  async function act(action: () => Promise<unknown>, success: string, close = true) {
    setBusy(true);
    try {
      await action();
      setMessage({ text: success, ok: true });
      if (close) setDetail(null);
      setVersion((value) => value + 1);
    } catch (error) {
      setMessage({ text: (error as Error).message, ok: false });
    } finally {
      setBusy(false);
    }
  }

  function setPassword(event: FormEvent<HTMLFormElement>) {
    event.preventDefault();
    if (!detail) return;
    const form = event.currentTarget;
    const password = text(new FormData(form), 'password');
    act(() => rpc('simbar_user_set_password', { target_id: detail.id, new_password: password }),
      `Password ${detail.name} sudah diganti. Sampaikan password baru secara langsung dan minta ia menggantinya di menu Profil.`, false)
      .then(() => form.reset());
  }

  const stats = state.data?.stats;
  const self = detail?.id === selfId;
  return <>
    {stats && <dl className="metrics compact">
      <div className="metric"><dt>Menunggu persetujuan</dt><dd>{number.format(stats.pending)}</dd></div>
      <div className="metric"><dt>Aktif</dt><dd>{number.format(stats.approved)}</dd></div>
      <div className="metric"><dt>Online sekarang</dt><dd>{number.format(stats.online)}</dd></div>
      <div className="metric"><dt>Admin / gudang / staf</dt><dd className="small">{stats.admin} / {stats.admin_gudang} / {stats.staff}</dd></div>
    </dl>}
    <Message value={message} />
    {detail && <div className="record-form">
      <h3>{detail.name}</h3>
      <dl className="detail-list">
        <div><dt>Email</dt><dd>{detail.email}</dd></div>
        <div><dt>Nomor HP</dt><dd>{detail.phone ?? '—'}</dd></div>
        <div><dt>Role</dt><dd>{roleNames[detail.role]}</dd></div>
        <div><dt>Status</dt><dd><span className={detail.approved ? 'badge completed' : 'badge pending'}>{detail.approved ? 'Aktif' : 'Menunggu persetujuan'}</span>
          {!detail.linked && <span className="cell-note warn">Belum punya login</span>}</dd></div>
        <div><dt>Terdaftar</dt><dd>{at(detail.created_at)}</dd></div>
        <div><dt>Disetujui</dt><dd>{at(detail.approved_at)}{detail.approved_by ? ' oleh ' + detail.approved_by : ''}</dd></div>
        <div><dt>Login terakhir</dt><dd>{at(detail.last_login_at)}{detail.last_ip_address ? ' · ' + detail.last_ip_address : ''}</dd></div>
        <div><dt>Aktivitas</dt><dd>{number.format(detail.stats.total_logins)} login · {number.format(detail.stats.total_activities)} aktivitas ·
          {' '}{number.format(detail.stats.completed_requests)}/{number.format(detail.stats.total_requests)} pengambilan selesai</dd></div>
        <div className="wide"><dt>Aktivitas terakhir</dt><dd>{detail.activities.length ? <ul className="plain-list">{detail.activities.map((entry, index) =>
          <li key={index}>{at(entry.created_at)} · {entry.description ?? entry.action}</li>)}</ul> : 'Belum ada.'}</dd></div>
      </dl>
      {!self && <div className="form-actions wide">
        {!detail.approved ? <>
          <label className="inline-select">Setujui sebagai <select id="approve-role" defaultValue="staff">
            {Object.entries(roleNames).map(([key, label]) => <option key={key} value={key}>{label}</option>)}</select></label>
          <button className="primary" disabled={busy} onClick={() => {
            const chosen = (document.getElementById('approve-role') as HTMLSelectElement).value;
            act(() => rpc('simbar_user_act', { target_id: detail.id, user_action: 'approve', new_role: chosen }), `Akun ${detail.name} berhasil disetujui.`);
          }}>Setujui</button>
          <button className="danger" disabled={busy} onClick={() => {
            if (confirm(`Tolak pendaftaran ${detail.name}? Akun dan loginnya dihapus.`)) {
              act(() => rpc('simbar_user_act', { target_id: detail.id, user_action: 'reject' }), `Pendaftaran ${detail.name} ditolak dan dihapus.`);
            }
          }}>Tolak</button>
        </> : <>
          <label className="inline-select">Ubah role <select id="change-role" defaultValue={detail.role}>
            {Object.entries(roleNames).map(([key, label]) => <option key={key} value={key}>{label}</option>)}</select></label>
          <button className="secondary" disabled={busy} onClick={() => {
            const chosen = (document.getElementById('change-role') as HTMLSelectElement).value;
            if (chosen !== detail.role) act(() => rpc('simbar_user_act', { target_id: detail.id, user_action: 'role', new_role: chosen }),
              `Role ${detail.name} diubah menjadi ${roleNames[chosen as Role]}.`);
          }}>Simpan role</button>
          <button className="danger" disabled={busy} onClick={() => {
            if (confirm(`Hapus akun ${detail.name}? Loginnya ikut dihapus.`)) act(() => rpc('simbar_user_act', { target_id: detail.id, user_action: 'delete' }), `Akun ${detail.name} berhasil dihapus.`);
          }}>Hapus akun</button>
        </>}
      </div>}
      {self && <p className="cell-note wide">Ini akun Anda sendiri. Ubah data diri dan password di menu Profil.</p>}
      {!self && detail.linked && <form className="approve-form wide" onSubmit={setPassword}>
        <label>Atur ulang password (minimal 8 karakter)<input name="password" type="text" autoComplete="off" required minLength={8} maxLength={72} /></label>
        <div className="form-actions"><button className="secondary" type="submit" disabled={busy}>Ganti password</button></div>
      </form>}
      <div className="form-actions wide"><button className="secondary" onClick={() => setDetail(null)}>Tutup</button></div>
    </div>}
    <div className="filters" role="group" aria-label="Filter pengguna">
      {([['pending', 'Menunggu'], ['approved', 'Aktif'], ['online', 'Online'], ['today', 'Login hari ini'], ['all', 'Semua']] as const).map(([value, label]) =>
        <button key={value} className="secondary" aria-pressed={filter === value} onClick={() => { setFilter(value); setPage(1); }}>{label}</button>)}
    </div>
    <form className="filters" role="search" onSubmit={(event) => { event.preventDefault(); setSearch(text(new FormData(event.currentTarget), 'q')); setPage(1); }}>
      <label className="sr-only" htmlFor="user-search">Cari pengguna</label>
      <input id="user-search" name="q" type="search" maxLength={100} placeholder="Nama, email, atau nomor HP" />
      <select aria-label="Role" value={role} onChange={(event) => { setRole(event.target.value); setPage(1); }}>
        <option value="">Semua role</option>{Object.entries(roleNames).map(([key, label]) => <option key={key} value={key}>{label}</option>)}
      </select>
      <button className="primary" type="submit">Cari</button>
    </form>
    {state.error ? <p className="notice" role="alert">{state.error}</p> : !state.data ? <p role="status">Memuat pengguna…</p>
      : !state.data.total ? <p role="status">Tidak ada pengguna.</p> : <>
        <div className="inventory-scroll" tabIndex={0} role="region" aria-label="Tabel pengguna">
          <table>
            <thead><tr><th scope="col">Nama</th><th scope="col">Role</th><th scope="col">Status</th><th scope="col">Login terakhir</th>
              <th scope="col"><span className="sr-only">Aksi</span></th></tr></thead>
            <tbody>{state.data.rows.map((row) => <tr key={row.id}>
              <th scope="row"><strong>{row.name}</strong><span>{row.email}</span></th>
              <td>{roleNames[row.role]}</td>
              <td><span className={row.approved ? 'badge completed' : 'badge pending'}>{row.approved ? 'Aktif' : 'Menunggu'}</span>
                {row.online && <span className="cell-note">● online</span>}{!row.linked && <span className="cell-note warn">belum punya login</span>}</td>
              <td>{at(row.last_login_at)}</td>
              <td><button className="secondary" onClick={() => open(row.id)}>Detail</button></td>
            </tr>)}</tbody>
          </table>
        </div>
        <Pages page={page} total={state.data.total} size={15} go={setPage} />
      </>}
  </>;
}

function HistoryFilters({ onApply, actions }: { onApply: (filters: Record<string, string>) => void; actions: string[] }) {
  const [users, setUsers] = useState<{ id: number; name: string }[]>([]);
  useEffect(() => {
    rpc<UserPage>('simbar_users_list', { user_filter: 'approved', role_filter: null, search_text: '', page_number: 0 })
      .then((data) => setUsers(data.rows.map(({ id, name }) => ({ id, name })).sort((a, b) => a.name.localeCompare(b.name)))).catch(() => {});
  }, []);
  return <form className="asset-filter" onSubmit={(event) => {
    event.preventDefault();
    onApply(Object.fromEntries([...new FormData(event.currentTarget).entries()].map(([key, value]) => [key, String(value)])));
  }}>
    <label>Pengguna<select name="user" defaultValue=""><option value="">Semua</option>
      {users.map((user) => <option key={user.id} value={user.id}>{user.name}</option>)}</select></label>
    <label>Aksi<select name="action" defaultValue=""><option value="">Semua</option>
      {actions.map((action) => <option key={action} value={action}>{action}</option>)}</select></label>
    <label>Dari<input name="dari" type="date" /></label>
    <label>Sampai<input name="sampai" type="date" /></label>
    <div className="form-actions wide"><button className="primary" type="submit">Terapkan</button></div>
  </form>;
}

function LoginHistory() {
  const [filters, setFilters] = useState<Record<string, string>>({});
  const [page, setPage] = useState(1);
  const [version, setVersion] = useState(0);
  const [state, setState] = useState<{ data?: { total: number; stats: Record<string, number>; rows: HistoryRow[] }; error?: string }>({});
  const [message, setMessage] = useState({ text: '', ok: true });
  useEffect(() => {
    let active = true;
    rpc<{ total: number; stats: Record<string, number>; rows: HistoryRow[] }>('simbar_login_history', { filters, page_number: page })
      .then((data) => active && setState({ data })).catch((error: Error) => active && setState({ error: error.message }));
    return () => { active = false; };
  }, [filters, page, version]);
  return <>
    <HistoryFilters actions={['login', 'logout']} onApply={(value) => { setFilters(value); setPage(1); }} />
    <Message value={message} />
    {state.error ? <p className="notice" role="alert">{state.error}</p> : !state.data ? <p role="status">Memuat riwayat…</p> : <>
      <p role="status">{number.format(state.data.stats.logins)} login · {number.format(state.data.stats.logouts)} logout · {number.format(state.data.stats.users)} pengguna</p>
      <div className="inventory-scroll" tabIndex={0} role="region" aria-label="Riwayat login">
        <table>
          <thead><tr><th scope="col">Waktu</th><th scope="col">Pengguna</th><th scope="col">Aksi</th><th scope="col">Perangkat</th><th scope="col">IP</th></tr></thead>
          <tbody>{state.data.rows.map((row) => <tr key={row.id}><td>{at(row.created_at)}</td><th scope="row">{row.name}</th>
            <td>{row.action === 'login' ? 'Masuk' : 'Keluar'}</td><td>{row.device} · {row.browser} · {row.platform}</td><td>{row.ip_address ?? '—'}</td></tr>)}</tbody>
        </table>
      </div>
      <Pages page={page} total={state.data.total} size={25} go={setPage} />
      <button className="danger" onClick={() => {
        if (confirm('Hapus SEMUA riwayat login? Tindakan ini tidak bisa dibatalkan.')) {
          rpc<number>('simbar_logs_clear', { kind: 'login_history' }).then((count) => {
            setMessage({ text: `Semua riwayat login dihapus (${count} catatan).`, ok: true });
            setVersion((value) => value + 1);
          }).catch((error: Error) => setMessage({ text: error.message, ok: false }));
        }
      }}>Hapus semua riwayat login</button>
    </>}
  </>;
}

function ActivityLogs() {
  const [filters, setFilters] = useState<Record<string, string>>({});
  const [page, setPage] = useState(1);
  const [version, setVersion] = useState(0);
  const [state, setState] = useState<{ data?: { total: number; actions: string[]; rows: LogRow[] }; error?: string }>({});
  const [message, setMessage] = useState({ text: '', ok: true });
  useEffect(() => {
    let active = true;
    rpc<{ total: number; actions: string[]; rows: LogRow[] }>('simbar_activity_logs', { filters, page_number: page })
      .then((data) => active && setState({ data })).catch((error: Error) => active && setState({ error: error.message }));
    return () => { active = false; };
  }, [filters, page, version]);
  return <>
    <HistoryFilters actions={state.data?.actions ?? []} onApply={(value) => { setFilters(value); setPage(1); }} />
    <Message value={message} />
    {state.error ? <p className="notice" role="alert">{state.error}</p> : !state.data ? <p role="status">Memuat log…</p> : <>
      <p role="status">{number.format(state.data.total)} aktivitas.</p>
      <div className="inventory-scroll" tabIndex={0} role="region" aria-label="Log aktivitas">
        <table>
          <thead><tr><th scope="col">Waktu</th><th scope="col">Pengguna</th><th scope="col">Aksi</th><th scope="col">Keterangan</th></tr></thead>
          <tbody>{state.data.rows.map((row) => <tr key={row.id}><td>{at(row.created_at)}</td><th scope="row">{row.name ?? '—'}</th>
            <td><code>{row.action}</code></td><td>{row.description ?? '—'}</td></tr>)}</tbody>
        </table>
      </div>
      <Pages page={page} total={state.data.total} size={25} go={setPage} />
      <button className="danger" onClick={() => {
        if (confirm('Hapus SEMUA log aktivitas? Tindakan ini tidak bisa dibatalkan.')) {
          rpc<number>('simbar_logs_clear', { kind: 'activity_logs' }).then((count) => {
            setMessage({ text: `Semua log aktivitas dihapus (${count} catatan).`, ok: true });
            setVersion((value) => value + 1);
          }).catch((error: Error) => setMessage({ text: error.message, ok: false }));
        }
      }}>Hapus semua log aktivitas</button>
    </>}
  </>;
}
