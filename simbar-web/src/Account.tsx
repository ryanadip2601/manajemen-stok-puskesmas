import { useEffect, useState, type FormEvent } from 'react';
import { client, rpc } from './supabase';

const auth = client?.auth ?? null;
const text = (data: FormData, name: string) => String(data.get(name) ?? '').trim();
const MIN_PASSWORD = 8;

function Notice({ message }: { message: { text: string; ok: boolean } }) {
  return message.text ? <p className={message.ok ? 'success' : 'notice'} role={message.ok ? 'status' : 'alert'}>{message.text}</p> : null;
}

function authError(error: { status?: number; message?: string; code?: string }, fallback: string) {
  if (error.status === 429 || error.code === 'over_request_rate_limit' || error.code === 'over_email_send_rate_limit') return 'Terlalu banyak percobaan. Tunggu beberapa menit.';
  if (error.code === 'user_already_exists' || /already registered/i.test(error.message ?? '')) return 'Email sudah terdaftar.';
  if (error.code === 'weak_password') return `Password minimal ${MIN_PASSWORD} karakter dan tidak mudah ditebak.`;
  return fallback;
}

export function AuthScreen() {
  const [mode, setMode] = useState<'login' | 'register' | 'forgot'>('login');
  const [busy, setBusy] = useState(false);
  const [message, setMessage] = useState({ text: '', ok: true });

  async function login(event: FormEvent<HTMLFormElement>) {
    event.preventDefault();
    if (!auth || busy) return;
    const data = new FormData(event.currentTarget);
    setBusy(true);
    setMessage({ text: '', ok: true });
    try {
      const result = await auth.signInWithPassword({ email: text(data, 'email').toLowerCase(), password: String(data.get('password')) });
      if (result.error) setMessage({ text: authError(result.error, 'Tidak bisa masuk. Periksa email dan password SIMBAR.'), ok: false });
    } catch {
      setMessage({ text: 'Layanan login belum bisa dihubungi.', ok: false });
    } finally {
      setBusy(false);
    }
  }

  async function register(event: FormEvent<HTMLFormElement>) {
    event.preventDefault();
    if (!auth || busy) return;
    const data = new FormData(event.currentTarget);
    const password = String(data.get('password'));
    if (password.length < MIN_PASSWORD) return setMessage({ text: `Password minimal ${MIN_PASSWORD} karakter.`, ok: false });
    if (password !== String(data.get('confirm'))) return setMessage({ text: 'Konfirmasi password tidak cocok.', ok: false });
    setBusy(true);
    setMessage({ text: '', ok: true });
    try {
      const result = await auth.signUp({ email: text(data, 'email').toLowerCase(), password,
        options: { data: { name: text(data, 'name'), phone: text(data, 'phone') } } });
      if (result.error) setMessage({ text: authError(result.error, 'Pendaftaran belum berhasil. Coba lagi.'), ok: false });
      else if (!result.data.session) {
        setMode('login');
        setMessage({ text: 'Pendaftaran berhasil. Cek email Anda untuk konfirmasi, lalu tunggu persetujuan administrator.', ok: true });
      }
    } catch {
      setMessage({ text: 'Layanan pendaftaran belum bisa dihubungi.', ok: false });
    } finally {
      setBusy(false);
    }
  }

  async function forgot(event: FormEvent<HTMLFormElement>) {
    event.preventDefault();
    if (!auth || busy) return;
    const data = new FormData(event.currentTarget);
    setBusy(true);
    try {
      const result = await auth.resetPasswordForEmail(text(data, 'email').toLowerCase(), { redirectTo: window.location.origin });
      setMessage(result.error ? { text: authError(result.error, 'Email reset belum bisa dikirim. Hubungi administrator untuk mengatur ulang password.'), ok: false }
        : { text: 'Jika email terdaftar, link reset password sudah dikirim. Cek inbox atau folder spam. Bila tidak ada email, hubungi administrator.', ok: true });
    } catch {
      setMessage({ text: 'Layanan reset password belum bisa dihubungi.', ok: false });
    } finally {
      setBusy(false);
    }
  }

  const switchTo = (next: typeof mode) => { setMode(next); setMessage({ text: '', ok: true }); };
  return <section className="login-layout">
    <div className="login-intro">
      <p className="eyebrow">Inventaris &amp; pemeliharaan</p>
      <h1>Sistem Manajemen Barang</h1>
      <p>Stok, permintaan, aset, dan pemeliharaan Puskesmas Karang Rejo dalam satu tempat.</p>
    </div>
    {mode === 'login' ? <form className="login-card" onSubmit={login}>
      <h2>Masuk ke SIMBAR</h2>
      <p>Gunakan email dan password akun SIMBAR Anda.</p>
      <label htmlFor="email">Email</label>
      <input id="email" type="email" name="email" autoComplete="username" placeholder="nama@contoh.id" required />
      <label htmlFor="password">Password</label>
      <input id="password" type="password" name="password" autoComplete="current-password" required />
      <Notice message={!auth ? { text: 'Login belum dikonfigurasi.', ok: false } : message} />
      <button className="primary" type="submit" disabled={busy || !auth}>{busy ? 'Memeriksa…' : 'Masuk'}</button>
      <div className="auth-links">
        <button type="button" className="link" onClick={() => switchTo('forgot')}>Lupa password?</button>
        <button type="button" className="link" onClick={() => switchTo('register')}>Daftar akun baru</button>
      </div>
    </form> : mode === 'register' ? <form className="login-card" onSubmit={register}>
      <h2>Daftar akun</h2>
      <p>Akun baru aktif setelah disetujui administrator.</p>
      <label htmlFor="reg-name">Nama lengkap</label>
      <input id="reg-name" name="name" autoComplete="name" required maxLength={255} />
      <label htmlFor="reg-email">Email</label>
      <input id="reg-email" type="email" name="email" autoComplete="email" required />
      <label htmlFor="reg-phone">Nomor HP</label>
      <input id="reg-phone" type="tel" name="phone" autoComplete="tel" required maxLength={20} />
      <label htmlFor="reg-password">Password (minimal {MIN_PASSWORD} karakter)</label>
      <input id="reg-password" type="password" name="password" autoComplete="new-password" required minLength={MIN_PASSWORD} />
      <label htmlFor="reg-confirm">Ulangi password</label>
      <input id="reg-confirm" type="password" name="confirm" autoComplete="new-password" required minLength={MIN_PASSWORD} />
      <Notice message={message} />
      <button className="primary" type="submit" disabled={busy || !auth}>{busy ? 'Mendaftar…' : 'Daftar'}</button>
      <div className="auth-links"><button type="button" className="link" onClick={() => switchTo('login')}>Sudah punya akun? Masuk</button></div>
    </form> : <form className="login-card" onSubmit={forgot}>
      <h2>Lupa password</h2>
      <p>Masukkan email akun Anda. Kami akan mengirim link untuk membuat password baru.</p>
      <label htmlFor="forgot-email">Email</label>
      <input id="forgot-email" type="email" name="email" autoComplete="email" required />
      <Notice message={message} />
      <button className="primary" type="submit" disabled={busy || !auth}>{busy ? 'Mengirim…' : 'Kirim link reset'}</button>
      <div className="auth-links"><button type="button" className="link" onClick={() => switchTo('login')}>Kembali ke halaman masuk</button></div>
    </form>}
  </section>;
}

// Shown after following a reset link from the e-mail (Supabase signs the browser in for this purpose).
export function NewPassword({ done }: { done: () => void }) {
  const [busy, setBusy] = useState(false);
  const [message, setMessage] = useState({ text: '', ok: true });
  async function submit(event: FormEvent<HTMLFormElement>) {
    event.preventDefault();
    if (!auth) return;
    const data = new FormData(event.currentTarget);
    const password = String(data.get('password'));
    if (password.length < MIN_PASSWORD) return setMessage({ text: `Password minimal ${MIN_PASSWORD} karakter.`, ok: false });
    if (password !== String(data.get('confirm'))) return setMessage({ text: 'Konfirmasi password tidak cocok.', ok: false });
    setBusy(true);
    const result = await auth.updateUser({ password }).catch(() => null);
    setBusy(false);
    if (!result || result.error) return setMessage({ text: result ? authError(result.error!, 'Password belum bisa diubah. Minta link baru.') : 'Layanan belum bisa dihubungi.', ok: false });
    await rpc('simbar_log_self', { log_action: 'password_reset_success' }).catch(() => {});
    done();
  }
  return <section className="login-layout single">
    <form className="login-card" onSubmit={submit}>
      <h2>Buat password baru</h2>
      <label htmlFor="new-password">Password baru (minimal {MIN_PASSWORD} karakter)</label>
      <input id="new-password" type="password" name="password" autoComplete="new-password" required minLength={MIN_PASSWORD} />
      <label htmlFor="new-confirm">Ulangi password baru</label>
      <input id="new-confirm" type="password" name="confirm" autoComplete="new-password" required minLength={MIN_PASSWORD} />
      <Notice message={message} />
      <button className="primary" type="submit" disabled={busy}>{busy ? 'Menyimpan…' : 'Simpan password'}</button>
    </form>
  </section>;
}

export function PendingAccount({ name, linked }: { name?: string; linked: boolean }) {
  return <section className="panel">
    <h1>{linked ? 'Akun menunggu persetujuan' : 'Akun belum terhubung'}</h1>
    <p>{linked ? `Halo${name ? ' ' + name : ''}, pendaftaran Anda sudah diterima. Administrator akan memeriksa dan mengaktifkan akun Anda.`
      : 'Login ini belum terhubung ke profil SIMBAR. Hubungi administrator.'}</p>
  </section>;
}

type Profile = { id: number; name: string; email: string; phone: string | null; role: string; created_at: string | null; last_login_at: string | null };
const roleNames: Record<string, string> = { admin: 'Administrator', admin_gudang: 'Admin Gudang', staff: 'Staf' };

export function ProfilePage() {
  const [profile, setProfile] = useState<Profile | null>(null);
  const [message, setMessage] = useState({ text: '', ok: true });
  const [busy, setBusy] = useState(false);
  const [version, setVersion] = useState(0);
  useEffect(() => {
    rpc<Profile>('simbar_profile_get').then(setProfile).catch((error: Error) => setMessage({ text: error.message, ok: false }));
  }, [version]);

  async function saveProfile(event: FormEvent<HTMLFormElement>) {
    event.preventDefault();
    const data = new FormData(event.currentTarget);
    setBusy(true);
    try {
      await rpc('simbar_profile_update', { profile_name: text(data, 'name'), profile_phone: text(data, 'phone') });
      setMessage({ text: 'Profil berhasil diperbarui.', ok: true });
      setVersion((value) => value + 1);
    } catch (error) {
      setMessage({ text: (error as Error).message, ok: false });
    } finally {
      setBusy(false);
    }
  }

  async function changePassword(event: FormEvent<HTMLFormElement>) {
    event.preventDefault();
    if (!auth || !profile) return;
    const form = event.currentTarget;
    const data = new FormData(form);
    const password = String(data.get('password'));
    if (password.length < MIN_PASSWORD) return setMessage({ text: `Password baru minimal ${MIN_PASSWORD} karakter.`, ok: false });
    if (password !== String(data.get('confirm'))) return setMessage({ text: 'Konfirmasi password tidak cocok.', ok: false });
    setBusy(true);
    try {
      const check = await auth.signInWithPassword({ email: profile.email, password: String(data.get('current')) });
      if (check.error) return setMessage({ text: 'Password saat ini salah.', ok: false });
      const result = await auth.updateUser({ password });
      if (result.error) return setMessage({ text: authError(result.error, 'Password belum bisa diubah.'), ok: false });
      await rpc('simbar_log_self', { log_action: 'password_changed' }).catch(() => {});
      form.reset();
      setMessage({ text: 'Password berhasil diubah.', ok: true });
    } catch {
      setMessage({ text: 'Layanan belum bisa dihubungi.', ok: false });
    } finally {
      setBusy(false);
    }
  }

  if (!profile) return message.text ? <Notice message={message} /> : <p role="status">Memuat profil…</p>;
  return <section className="panel">
    <h2>Profil saya</h2>
    <p>{profile.email} · {roleNames[profile.role]}</p>
    <Notice message={message} />
    <form className="record-form" onSubmit={saveProfile}>
      <h3>Data diri</h3>
      <label>Nama<input name="name" required maxLength={255} defaultValue={profile.name} /></label>
      <label>Nomor HP<input name="phone" type="tel" maxLength={20} defaultValue={profile.phone ?? ''} /></label>
      <div className="form-actions wide"><button className="primary" type="submit" disabled={busy}>Simpan profil</button></div>
    </form>
    <form className="record-form" onSubmit={changePassword}>
      <h3>Ubah password</h3>
      <label className="wide">Password saat ini<input name="current" type="password" autoComplete="current-password" required /></label>
      <label>Password baru<input name="password" type="password" autoComplete="new-password" required minLength={MIN_PASSWORD} /></label>
      <label>Ulangi password baru<input name="confirm" type="password" autoComplete="new-password" required minLength={MIN_PASSWORD} /></label>
      <div className="form-actions wide"><button className="primary" type="submit" disabled={busy}>Ubah password</button></div>
    </form>
  </section>;
}
