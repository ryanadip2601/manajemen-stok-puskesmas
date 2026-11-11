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
import { Catalog, Mutasi } from './Stock';
import { AuthScreen, NewPassword, PendingAccount, ProfilePage } from './Account';
import { client, rpc } from './supabase';
import { Users } from './Users';
import { DEFAULT_ROLE_MENUS, MENU_GROUPS, RoleMenusPage, type RoleMenus } from './RoleMenus';
import { CursorTrail, remember, remembered, SeasonCanvas, SeasonMenu, useSeason, type SeasonChoice } from './effects';
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
type Role = 'admin' | 'admin_gudang' | 'staff';
const roles = { admin: 'Administrator', admin_gudang: 'Admin Gudang', staff: 'Staf' };
const roleShort = { admin: 'Admin', admin_gudang: 'Gudang', staff: 'Staf' };

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

type Section = 'summary' | InventoryKind | 'catalog' | 'mutasi' | 'in' | 'out' | 'pickup' | 'checklist' | 'permintaan' | 'verifikasi' | 'usulan' | 'usulan-admin' | 'aset' | 'pemeliharaan' | 'penyusutan' | 'laporan' | 'users' | 'menus' | 'profil';
type MenuItem = { id: Section; label: string; icon: string; color: string };
type MenuGroup = { title: string; items: MenuItem[] };

// The administrator sees every menu; Admin Gudang and Staf see the menus set in "Atur Menu" (Dashboard always).
function menuFor(role: Summary['profile']['role'], shared: RoleMenus): MenuGroup[] {
  return MENU_GROUPS.map((group) => ({
    title: group.title,
    items: group.items.filter((item) => !item.right && (role === 'admin' || item.share === 'always'
      || (item.share === 'parent' && (item.rights ?? []).some((id) => shared[role].includes(id)))
      || (item.share === 'yes' && (shared[role].includes(item.id) || (item.id === 'items' && shared[role].includes('catalog'))))))
      .map(({ id, label, icon, color }) => ({ id, label, icon, color })),
  })).filter((group) => group.items.length);
}
const roleIcons = { admin: 'fa-crown', admin_gudang: 'fa-warehouse', staff: 'fa-user-tie' };

const SECTIONS: Section[] = ['summary', 'items', 'catalog', 'mutasi', 'in', 'out', 'pickup', 'checklist', 'permintaan', 'verifikasi', 'usulan',
  'usulan-admin', 'aset', 'pemeliharaan', 'penyusutan', 'laporan', 'users', 'menus', 'profil'];
function linkedSection(search: string) {
  const wanted = new URLSearchParams(search).get('menu');
  if (wanted === 'catalog') return 'items'; // Kelola Barang now lives inside Data Barang
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
  const music = useMusic(!!(ready && session && !recovery && status?.approved));

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
  // Administrators can preview the menus of the roles below them; everyone else always sees their own role.
  const [viewAs, setViewAs] = useState<Role>('admin');
  const role: Role | undefined = profile?.role === 'admin' ? viewAs : profile?.role;
  const previewing = profile?.role === 'admin' && viewAs !== 'admin';
  const warehouse = role === 'admin' || role === 'admin_gudang';
  const [roleMenus, setRoleMenus] = useState<RoleMenus>(DEFAULT_ROLE_MENUS);
  useEffect(() => {
    rpc<RoleMenus>('simbar_role_menus').then((saved) => { if (saved?.staff && saved?.admin_gudang) setRoleMenus(saved); }).catch(() => {});
  }, [session, reload]);
  const groups = role ? menuFor(role, roleMenus) : [];
  // Sidebar groups fold open and closed (remembered per browser); the search box narrows the list.
  const [menuQuery, setMenuQuery] = useState('');
  const [folded, setFolded] = useState<string[]>(() => {
    try { const saved = JSON.parse(remembered('simbar-menu-folded') ?? '[]'); return Array.isArray(saved) ? saved : []; } catch { return []; }
  });
  useEffect(() => { remember('simbar-menu-folded', JSON.stringify(folded)); }, [folded]);
  const needle = menuQuery.trim().toLowerCase();
  const shownGroups = needle
    ? groups.map((group) => ({ ...group, items: group.items.filter((item) => (item.label + ' ' + group.title).toLowerCase().includes(needle)) }))
      .filter((group) => group.items.length)
    : groups;
  const isOpen = (group: MenuGroup) => !!needle || !folded.includes(group.title) || group.items.some((item) => item.id === shownSection);
  const toggleGroup = (title: string) => setFolded(folded.includes(title) ? folded.filter((t) => t !== title) : [...folded, title]);
  const shownSection: Section = section === 'in' || section === 'out' ? 'mutasi' : section === 'catalog' ? 'items' : section;
  const current = section === 'profil' ? { label: 'Profil', group: 'Akun saya' }
    : groups.flatMap((group) => group.items.map((item) => ({ ...item, group: group.title }))).find((item) => item.id === shownSection)
      ?? { label: 'Dashboard', group: 'Menu Utama' };
  // A link to a page this role cannot open falls back to the dashboard.
  useEffect(() => {
    if (groups.length && section !== 'profil' && !groups.some((group) => group.items.some((item) => item.id === shownSection))) setSection('summary');
  }, [groups.length, section]);

  function go(next: Section) {
    setSection(next);
    setDrawer(false);
    window.scrollTo(0, 0);
  }
  function switchRole(next: Role) {
    setViewAs(next);
    go('summary');
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
      {profile?.role === 'admin' && <div className="role-switch" role="group" aria-label="Lihat tampilan sebagai">
        <p className="side-group-title">Lihat sebagai</p>
        <div className="role-switch-row">
          {(['admin', 'admin_gudang', 'staff'] as Role[]).map((option) =>
            <button key={option} className={'role-option role-' + option} aria-pressed={viewAs === option}
              title={roles[option]} aria-label={roles[option]} onClick={() => switchRole(option)}>
              <i className={'fas ' + roleIcons[option]} aria-hidden="true" /><span>{roleShort[option]}</span>
            </button>)}
        </div>
      </div>}
      <div className="side-search">
        <i className="fas fa-magnifying-glass" aria-hidden="true" />
        <input type="search" value={menuQuery} placeholder="Cari menu…" aria-label="Cari menu"
          onChange={(event) => setMenuQuery(event.target.value)}
          onKeyDown={(event) => {
            if (event.key === 'Escape') setMenuQuery('');
            if (event.key === 'Enter' && shownGroups[0]) { go(shownGroups[0].items[0].id); setMenuQuery(''); }
          }} />
      </div>
      <nav className="side-nav" aria-label="Menu SIMBAR">
        {needle && !shownGroups.length && <p className="side-empty">Menu tidak ditemukan.</p>}
        {shownGroups.map((group) => <div className={'side-group' + (isOpen(group) ? '' : ' folded')} key={group.title}>
          <p className="side-group-title"><span className="side-group-name">{group.title}</span>
            {!needle && <button className="side-fold" aria-expanded={isOpen(group)}
              aria-label={(isOpen(group) ? 'Tutup ' : 'Buka ') + group.title} onClick={() => toggleGroup(group.title)}>
              {!isOpen(group) && <span className="side-count">{group.items.length}</span>}
              <i className="fas fa-chevron-down" aria-hidden="true" /></button>}</p>
          {isOpen(group) && group.items.map((item) =>
            <button key={item.id} className={'side-link c-' + item.color} aria-current={shownSection === item.id ? 'page' : undefined}
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
        {previewing && <div className="preview-banner" role="status">
          <i className="fas fa-eye" aria-hidden="true" />
          <p>Anda melihat tampilan <strong>{roles[viewAs]}</strong>. Tindakan di halaman ini tetap memakai hak Administrator.</p>
          <button className="secondary" onClick={() => switchRole('admin')}>Kembali ke Administrator</button>
        </div>}
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
      : section === 'menus' ? <RoleMenusPage saved={roleMenus} onSaved={setRoleMenus} />
      : section === 'profil' ? <><ProfilePage /><DeviceApp /></>
      : section === 'penyusutan' ? <Penyusutan />
      : section === 'pemeliharaan' ? <Pemeliharaan warehouse={warehouse} />
      : section === 'pickup' ? <MyPickups />
      : section === 'permintaan' ? <MyPermintaan />
      : section === 'verifikasi' ? <AdminPermintaan />
      : section === 'usulan' ? <MyUsulan canExport={warehouse} />
      : section === 'usulan-admin' ? <AdminUsulan />
      : section === 'checklist' ? <Checklist />
      : section === 'mutasi' || section === 'in' || section === 'out'
        ? <Mutasi key={section} initial={section === 'mutasi' ? undefined : section}
            allowed={(['in', 'out'] as const).filter((kind) => role === 'admin' || (!!role && roleMenus[role].includes(kind)))} />
      : section === 'items' ? (role === 'admin' || (!!role && roleMenus[role].includes('catalog'))
        ? <Catalog list={(edit, version, add) => <Inventory kind="items" session={session} request={api} onEdit={edit} version={version} actions={add} />} />
        : <Inventory key={section} kind={section} session={session} request={api} />)
      : <><DeviceApp compact /><Overview isAdmin={role === 'admin'} go={(target) => go(target as Section)} /></>}
    </div>
        </>}
      </main>
      <footer className="app-footer">UPTD Puskesmas Karang Rejo <span aria-hidden="true">·</span> Kota Balikpapan</footer>
    </div>
  </div>;
}

createRoot(document.getElementById('root')!).render(<App />);
