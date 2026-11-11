import type { Session } from '@supabase/supabase-js';
import { useEffect, useState } from 'react';
import { createRoot } from 'react-dom/client';
import { Inventory, type InventoryKind } from './Inventory';
import { Assets } from './Aset';
import { Pemeliharaan } from './Pemeliharaan';
import { Laporan } from './Laporan';
import { Overview } from './Overview';
import { Penyusutan } from './Penyusutan';
import { AdminPermintaan, MyPermintaan, RestockNotices } from './Permintaan';
import { Checklist, MyPickups } from './Pickup';
import { AdminUsulan, MyUsulan } from './Usulan';
import { Catalog, Movements } from './Stock';
import { AuthScreen, NewPassword, PendingAccount, ProfilePage } from './Account';
import { client, rpc } from './supabase';
import { Users } from './Users';
import { CursorTrail, remember, remembered, SeasonCanvas, SeasonMenu, seasonForDate, useSeason, type SeasonChoice } from './effects';
import { MusicMenu, useMusic, type Music } from './music';
import { HeaderClock } from './clock';
import { BRAND_FULL, LogoMark } from './logo';
import { DeviceApp, forgetThisDevice, pushSupported, registerServiceWorker, setBadge } from './push';

registerServiceWorker();
import './style.css';

const auth = client?.auth ?? null;

type Photo = { id: number; kind: 'asset' | 'maintenance'; title: string; detail: string };
type Summary = {
  profile: { name: string; role: 'admin' | 'admin_gudang' | 'staff' };
  stats: { items: number; stock: number; assets: number; photos: number };
  photos: Photo[];
};
const roles = { admin: 'Administrator', admin_gudang: 'Admin Gudang', staff: 'Staf' };

async function api(session: Session, signal: AbortSignal, query?: Record<string, string>) {
  const path = query ? '/api/summary?' + new URLSearchParams(query) : '/api/summary';
  const response = await fetch(path, {
    headers: { Authorization: 'Bearer ' + session.access_token },
    cache: 'no-store',
    signal,
  });
  if (!response.ok) {
    const body = await response.json().catch(() => null);
    throw new Error(body?.error ?? 'SIMBAR belum bisa dihubungi.');
  }
  return response;
}

// Light/dark theme and sidebar width are per-browser conveniences, like the old Laravel layout.
type Theme = 'light' | 'dark';
function useTheme() {
  const [theme, setTheme] = useState<Theme>(() => remembered('simbar-theme') === 'dark' ? 'dark' : 'light');
  useEffect(() => {
    document.documentElement.dataset.theme = theme;
    remember('simbar-theme', theme);
  }, [theme]);
  return [theme, () => setTheme(theme === 'dark' ? 'light' : 'dark')] as const;
}

type Section = 'summary' | InventoryKind | 'catalog' | 'in' | 'out' | 'pickup' | 'checklist' | 'permintaan' | 'verifikasi' | 'usulan' | 'usulan-admin' | 'aset' | 'pemeliharaan' | 'penyusutan' | 'laporan' | 'users' | 'profil';
type MenuItem = { id: Section; label: string; icon: string; color: string };
type MenuGroup = { title: string; items: MenuItem[] };

function menuFor(role: Summary['profile']['role']): MenuGroup[] {
  const warehouse = role === 'admin' || role === 'admin_gudang';
  const groups: MenuGroup[] = [{ title: 'Menu Utama', items: [
    { id: 'summary', label: 'Dashboard', icon: 'fa-house', color: 'blue' },
    { id: 'items', label: 'Data Barang', icon: 'fa-box', color: 'emerald' },
  ] }];
  if (warehouse) groups.push({ title: 'Transaksi Gudang', items: [
    { id: 'catalog', label: 'Kelola Barang', icon: 'fa-boxes-stacked', color: 'teal' },
    { id: 'in', label: 'Barang Masuk', icon: 'fa-arrow-down', color: 'green' },
    { id: 'out', label: 'Barang Keluar', icon: 'fa-arrow-up', color: 'red' },
    { id: 'checklist', label: 'Checklist Pengambilan', icon: 'fa-clipboard-check', color: 'pink' },
  ] }, { title: 'Verifikasi', items: [
    { id: 'verifikasi', label: 'Verifikasi Permintaan', icon: 'fa-hand-holding-medical', color: 'orange' },
    { id: 'usulan-admin', label: 'Verifikasi Usulan', icon: 'fa-file-import', color: 'teal' },
  ] });
  groups.push({ title: 'Manajemen Aset', items: [
    { id: 'aset', label: 'Barang Aset', icon: 'fa-boxes-packing', color: 'cyan' },
    { id: 'pemeliharaan', label: 'Pemeliharaan', icon: 'fa-screwdriver-wrench', color: 'purple' },
    ...(warehouse ? [{ id: 'penyusutan', label: 'Penyusutan', icon: 'fa-calculator', color: 'amber' } as MenuItem] : []),
  ] }, { title: 'Pengajuan Saya', items: [
    { id: 'pickup', label: 'Pengambilan Barang', icon: 'fa-hand-holding', color: 'orange' },
    { id: 'permintaan', label: 'Permintaan Barang', icon: 'fa-clipboard-list', color: 'amber' },
    { id: 'usulan', label: 'Usulan Penerimaan', icon: 'fa-file-signature', color: 'blue' },
  ] });
  if (role === 'admin') groups.push({ title: 'Administrasi', items: [
    { id: 'laporan', label: 'Laporan', icon: 'fa-chart-line', color: 'indigo' },
    { id: 'users', label: 'Pengguna', icon: 'fa-users-gear', color: 'rose' },
  ] });
  return groups;
}
const roleIcons = { admin: 'fa-crown', admin_gudang: 'fa-warehouse', staff: 'fa-user-tie' };

const SECTIONS: Section[] = ['summary', 'items', 'catalog', 'in', 'out', 'pickup', 'checklist', 'permintaan', 'verifikasi', 'usulan',
  'usulan-admin', 'aset', 'pemeliharaan', 'penyusutan', 'laporan', 'users', 'profil'];
function linkedSection(search: string) {
  const wanted = new URLSearchParams(search).get('menu');
  return SECTIONS.find((section) => section === wanted) ?? null;
}

type Status ={ linked: boolean; approved: boolean; id?: number; name?: string; role?: string };

function App() {
  const [session, setSession] = useState<Session | null>(null);
  const [ready, setReady] = useState(!auth);
  const [recovery, setRecovery] = useState(false);
  const [status, setStatus] = useState<Status | null>(null);
  const [notice, setNotice] = useState('');
  const [theme, toggleTheme] = useTheme();
  const ambience = useSeason();
  const music = useMusic(ambience.season ?? seasonForDate(), !!(ready && session && !recovery && status?.approved));

  useEffect(() => {
    const subscription = auth?.onAuthStateChange((event, next) => {
      if (event === 'PASSWORD_RECOVERY') setRecovery(true);
      // A real sign-in (not a restored session) is recorded once the profile is known to be active.
      if (event === 'SIGNED_IN' && next) window.sessionStorage.setItem('simbar-record-login', next.user.id);
      setSession(next);
      setReady(true);
    });
    return () => subscription?.data.subscription.unsubscribe();
  }, []);

  const userId = session?.user.id;
  useEffect(() => {
    if (!userId) return setStatus(null);
    let active = true;
    setStatus(null);
    rpc<Status>('simbar_my_status').then((value) => {
      if (!active) return;
      setStatus(value);
      if (value.approved && window.sessionStorage.getItem('simbar-record-login') === userId) {
        window.sessionStorage.removeItem('simbar-record-login');
        rpc('simbar_session_event', { event: 'login', agent: navigator.userAgent }).catch(() => {});
      }
    }).catch((error: Error) => active && setNotice(error.message));
    return () => { active = false; };
  }, [userId]);

  useEffect(() => {
    if (!status?.approved) return;
    const timer = window.setInterval(() => rpc('simbar_session_event', { event: 'ping' }).catch(() => {}), 120_000);
    return () => window.clearInterval(timer);
  }, [status?.approved]);

  async function signOut() {
    setNotice('');
    // Signing out stops the music but remembers whether it was on, so it comes back after the next sign-in.
    music.pause();
    if (status?.approved) {
      await forgetThisDevice();
      await rpc('simbar_session_event', { event: 'logout', agent: navigator.userAgent }).catch(() => {});
    }
    const result = await auth?.signOut({ scope: 'local' }).catch(() => null);
    if (!result || result.error) setNotice('Gagal keluar. Coba lagi.');
  }

  const controls: Ambience = { theme, toggleTheme, season: ambience.choice, setSeason: ambience.setChoice, music };
  // The canvas and cursor stay mounted in the same place while the screen below them changes.
  return <>
    <SeasonCanvas season={ambience.season} />
    <CursorTrail />
    {ready && session && !recovery && status?.approved
      ? <Dashboard key={session.user.id} session={session} selfId={status.id ?? null} notice={notice} signOut={signOut} controls={controls} />
      : ready && !session ? <PublicHome notice={notice} controls={controls} />
      : <div className="auth-shell">
    {session && <button className="logout auth-signout" onClick={signOut}><i className="fas fa-right-from-bracket" aria-hidden="true" /><span>Keluar</span></button>}
    <main className="auth-main">
      {notice && <p className="notice" role="alert">{notice}</p>}
      {!ready ? <p className="auth-status" role="status">Memuat SIMBAR…</p>
        : recovery && session ? <NewPassword done={() => { setRecovery(false); setNotice(''); window.history.replaceState(null, '', '/'); }} />
        : !status ? <p className="auth-status" role="status">Memeriksa akun…</p>
        : <PendingAccount linked={status.linked} name={status.name} />}
    </main>
    <footer className="auth-footer">UPTD Puskesmas Karang Rejo <span aria-hidden="true">·</span> Kota Balikpapan</footer>
  </div>}
  </>;
}

type Ambience = {
  theme: Theme; toggleTheme: () => void; season: SeasonChoice; setSeason: (choice: SeasonChoice) => void; music: Music;
};

// Season, music and light/dark buttons, shared by the public page and the signed-in header.
function AmbientControls({ controls }: { controls: Ambience }) {
  const { theme, toggleTheme, season, setSeason, music } = controls;
  return <>
    <SeasonMenu choice={season} onChange={setSeason} />
    <MusicMenu music={music} />
    <button className="icon-button theme-toggle" onClick={toggleTheme} aria-label={theme === 'dark' ? 'Pakai mode terang' : 'Pakai mode gelap'}
      title={theme === 'dark' ? 'Mode terang' : 'Mode gelap'}>
      <i className={theme === 'dark' ? 'fas fa-sun' : 'fas fa-moon'} aria-hidden="true" />
    </button>
  </>;
}

// What everyone sees before signing in: the public charts, with the sign-in form one tap away in the header.
function PublicHome({ notice, controls }: { notice: string; controls: Ambience }) {
  const [login, setLogin] = useState(false);
  useEffect(() => {
    if (!login) return;
    const escape = (event: KeyboardEvent) => { if (event.key === 'Escape') setLogin(false); };
    document.addEventListener('keydown', escape);
    return () => document.removeEventListener('keydown', escape);
  }, [login]);
  return <div className="public-shell">
    <header className="topbar public-topbar">
      <div className="public-brand">
        <span className="logo-tile small" aria-hidden="true"><LogoMark /></span>
        <div className="brand-text"><strong className="gradient-text">SIMBAR</strong>
          <span className="brand-full">{BRAND_FULL}</span><span>Puskesmas Karang Rejo</span></div>
      </div>
      <HeaderClock />
      <AmbientControls controls={controls} />
      <button className="primary login-button" onClick={() => setLogin(true)}>
        <i className="fas fa-right-to-bracket" aria-hidden="true" /><span>Masuk</span>
      </button>
    </header>
    <main className="content public-content">
      {notice && <p className="notice" role="alert">{notice}</p>}
      <section className="public-hero">
        <p className="eyebrow">Inventaris &amp; pemeliharaan</p>
        <h1>Dashboard SIMBAR</h1>
        <p>Ringkasan stok dan aset UPTD Puskesmas Karang Rejo. Masuk untuk mengelola barang, permintaan, dan aset.</p>
      </section>
      <div className="page"><Overview publicView /></div>
    </main>
    <footer className="app-footer">UPTD Puskesmas Karang Rejo <span aria-hidden="true">·</span> Kota Balikpapan</footer>
    {login && <div className="login-overlay" role="dialog" aria-modal="true" aria-label="Masuk ke SIMBAR"
      onClick={(event) => { if (event.target === event.currentTarget) setLogin(false); }}>
      <div className="login-dialog">
        <button className="icon-button login-close" onClick={() => setLogin(false)} aria-label="Tutup"><i className="fas fa-xmark" aria-hidden="true" /></button>
        <AuthScreen />
      </div>
    </div>}
  </div>;
}

function Dashboard({ session, selfId, notice, signOut, controls }: {
  session: Session; selfId: number | null; notice: string; signOut: () => void; controls: Ambience;
}) {
  const [state, setState] = useState<{ summary?: Summary; error?: string }>({});
  // A tapped notification opens the app at /?menu=<section>.
  const [section, setSection] = useState<Section>(() => linkedSection(window.location.search) ?? 'summary');
  const [reload, setReload] = useState(0);
  const [mini, setMini] = useState(() => remembered('simbar-sidebar') === 'mini');
  const [drawer, setDrawer] = useState(false);

  useEffect(() => remember('simbar-sidebar', mini ? 'mini' : 'full'), [mini]);

  useEffect(() => {
    if (window.location.search) window.history.replaceState(null, '', '/');
    if (pushSupported()) rpc<number>('simbar_my_badge').then(setBadge).catch(() => {});
    const opened = (event: MessageEvent) => {
      if (event.data?.type !== 'simbar-open') return;
      const target = linkedSection(new URL(event.data.url).search);
      if (target) go(target);
    };
    navigator.serviceWorker?.addEventListener('message', opened);
    return () => navigator.serviceWorker?.removeEventListener('message', opened);
  }, []);

  useEffect(() => {
    const controller = new AbortController();
    api(session, controller.signal).then((response) => response.json()).then((summary: Summary) => {
      if (!controller.signal.aborted) setState({ summary });
    }).catch((error: Error) => {
      if (!controller.signal.aborted) setState({ error: error.message });
    });
    return () => controller.abort();
  }, [session, reload]);

  const profile = state.summary?.profile;
  const warehouse = profile?.role === 'admin' || profile?.role === 'admin_gudang';
  const groups = profile ? menuFor(profile.role) : [];
  const current = section === 'profil' ? { label: 'Profil', group: 'Akun saya' }
    : groups.flatMap((group) => group.items.map((item) => ({ ...item, group: group.title }))).find((item) => item.id === section)
      ?? { label: 'Dashboard', group: 'Menu Utama' };
  // A link to a page this role cannot open falls back to the dashboard.
  useEffect(() => {
    if (groups.length && section !== 'profil' && !groups.some((group) => group.items.some((item) => item.id === section))) setSection('summary');
  }, [groups.length, section]);

  function go(next: Section) {
    setSection(next);
    setDrawer(false);
    window.scrollTo(0, 0);
  }
  function toggleMenu() {
    if (window.matchMedia('(max-width: 900px)').matches) setDrawer(!drawer);
    else setMini(!mini);
  }

  return <div className={'app' + (mini ? ' mini' : '') + (drawer ? ' drawer-open' : '')}>
    <aside className="sidebar">
      <div className="side-brand">
        <span className="logo-tile" aria-hidden="true"><LogoMark /></span>
        <div className="side-brand-text"><strong className="gradient-text">SIMBAR</strong>
          <span className="brand-full">{BRAND_FULL}</span><span>Puskesmas Karang Rejo</span></div>
      </div>
      <nav className="side-nav" aria-label="Menu SIMBAR">
        {groups.map((group) => <div className="side-group" key={group.title}>
          <p className="side-group-title">{group.title}</p>
          {group.items.map((item) =>
            <button key={item.id} className={'side-link c-' + item.color} aria-current={section === item.id ? 'page' : undefined}
              title={mini ? item.label : undefined} onClick={() => go(item.id)}>
              <span className="side-icon" aria-hidden="true"><i className={'fas ' + item.icon} /></span>
              <span className="side-label">{item.label}</span>
            </button>)}
        </div>)}
      </nav>
      {profile && <button className={'side-user' + (section === 'profil' ? ' active' : '')} onClick={() => go('profil')}
        aria-label="Profil" title={mini ? 'Profil' : undefined}>
        <span className={'avatar role-' + profile.role} aria-hidden="true"><i className="fas fa-user" /></span>
        <span className="side-user-text" aria-hidden="true"><strong>{profile.name}</strong>
          <span><i className={'fas ' + roleIcons[profile.role]} /> {roles[profile.role]}</span></span>
        <i className="fas fa-gear side-user-cog" aria-hidden="true" />
      </button>}
    </aside>
    <div className="drawer-backdrop" onClick={() => setDrawer(false)} aria-hidden="true" />
    <div className="main-area">
      <header className="topbar">
        <button className="icon-button" onClick={toggleMenu} aria-label="Buka atau tutup menu"><i className="fas fa-bars" aria-hidden="true" /></button>
        <div className="topbar-title"><h1 key={section}>{current.label}</h1>
          <p><span className="crumb">{current.group}</span><HeaderClock /></p></div>
        <AmbientControls controls={controls} />
        <button className="logout" onClick={signOut}><i className="fas fa-right-from-bracket" aria-hidden="true" /><span>Keluar</span></button>
      </header>
      <main className="content">
        {notice && <p className="notice" role="alert">{notice}</p>}
        {state.error ? <section className="panel">
          <h2>Ringkasan SIMBAR</h2><p className="notice" role="alert">{state.error}</p>
          <button className="secondary" onClick={() => { setState({}); setReload(reload + 1); }}>Coba lagi</button>
        </section> : !state.summary ? <p role="status">Memuat ringkasan SIMBAR…</p> : <>
    <RestockNotices />
    <div className="page" key={section}>
    {section === 'aset' ? <Assets warehouse={warehouse} />
      : section === 'laporan' ? <Laporan />
      : section === 'users' ? <Users selfId={selfId} />
      : section === 'profil' ? <><ProfilePage /><DeviceApp /></>
      : section === 'penyusutan' ? <Penyusutan />
      : section === 'pemeliharaan' ? <Pemeliharaan warehouse={warehouse} />
      : section === 'pickup' ? <MyPickups />
      : section === 'permintaan' ? <MyPermintaan />
      : section === 'verifikasi' ? <AdminPermintaan />
      : section === 'usulan' ? <MyUsulan canExport={warehouse} />
      : section === 'usulan-admin' ? <AdminUsulan />
      : section === 'checklist' ? <Checklist />
      : section === 'catalog' ? <Catalog />
      : section === 'in' || section === 'out' ? <Movements key={section} kind={section} />
      : section === 'items' ? <Inventory key={section} kind={section} session={session} request={api} />
      : <><DeviceApp compact /><Overview isAdmin={profile?.role === 'admin'} go={(target) => go(target as Section)} /></>}
    </div>
        </>}
      </main>
      <footer className="app-footer">UPTD Puskesmas Karang Rejo <span aria-hidden="true">·</span> Kota Balikpapan</footer>
    </div>
  </div>;
}

createRoot(document.getElementById('root')!).render(<App />);
