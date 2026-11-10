import type { Session } from '@supabase/supabase-js';
import { useEffect, useRef, useState, type FormEvent } from 'react';
import { createRoot } from 'react-dom/client';
import { Inventory, type InventoryKind } from './Inventory';
import { Catalog, Movements } from './Stock';
import { client } from './supabase';
import './style.css';

const auth = client?.auth ?? null;

type Photo = { id: number; kind: 'asset' | 'maintenance'; title: string; detail: string };
type Summary = {
  profile: { name: string; role: 'admin' | 'admin_gudang' | 'staff' };
  stats: { items: number; stock: number; assets: number; photos: number };
  photos: Photo[];
};
const roles = { admin: 'Administrator', admin_gudang: 'Admin Gudang', staff: 'Staf' };
const number = new Intl.NumberFormat('id-ID');

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

function Brand() {
  return <div className="brand">
    <span className="mark" aria-hidden="true">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="1.6" strokeLinejoin="round">
        <path d="m3 7 9-4 9 4v10l-9 4-9-4V7Zm0 0 9 4 9-4M12 11v10M7.5 5l9 4v4" />
      </svg>
    </span>
    <div><strong>SIMBAR</strong><span>Puskesmas Karang Rejo</span></div>
  </div>;
}

function App() {
  const [session, setSession] = useState<Session | null>(null);
  const [ready, setReady] = useState(!auth);
  const [signOutError, setSignOutError] = useState('');

  useEffect(() => {
    const subscription = auth?.onAuthStateChange((_event, next) => {
      setSession(next);
      setReady(true);
    });
    return () => subscription?.data.subscription.unsubscribe();
  }, []);

  async function signOut() {
    setSignOutError('');
    const result = await auth?.signOut({ scope: 'local' }).catch(() => null);
    if (!result || result.error) setSignOutError('Gagal keluar. Coba lagi.');
  }

  return <div className="shell">
    <header className="site-header"><Brand /><div className="header-actions">
      <span className="tag">Preview</span>
      {session && <button className="secondary" onClick={signOut}>Keluar</button>}
    </div></header>
    <main>
      {signOutError && <p className="notice" role="alert">{signOutError}</p>}
      {!ready ? <p role="status">Memuat SIMBAR…</p> : session
        ? <Dashboard key={session.user.id} session={session} />
        : <Login />}
    </main>
    <footer>UPTD Puskesmas Karang Rejo <span aria-hidden="true">·</span> Kota Balikpapan</footer>
  </div>;
}

function Login() {
  const [busy, setBusy] = useState(false);
  const [error, setError] = useState('');

  async function submit(event: FormEvent<HTMLFormElement>) {
    event.preventDefault();
    if (!auth || busy) return;
    const data = new FormData(event.currentTarget);
    setBusy(true);
    setError('');
    try {
      const result = await auth.signInWithPassword({
        email: String(data.get('email')).trim().toLowerCase(),
        password: String(data.get('password')),
      });
      if (result.error) {
        setError(result.error.status === 429 ? 'Terlalu banyak percobaan. Tunggu sebentar.'
          : 'Tidak bisa masuk. Periksa email dan kata sandi SIMBAR.');
      }
    } catch {
      setError('Layanan login belum bisa dihubungi.');
    } finally {
      setBusy(false);
    }
  }

  return <section className="login-layout">
    <div className="login-intro">
      <p className="eyebrow">Inventaris &amp; pemeliharaan</p>
      <h1>Sistem Manajemen Barang</h1>
      <p>Ringkasan inventaris dan foto aset Puskesmas Karang Rejo dalam satu tempat.</p>
    </div>
    <form className="login-card" onSubmit={submit}>
      <h2>Masuk ke SIMBAR</h2>
      <p>Gunakan akun yang sudah diaktifkan untuk Preview.</p>
      <label htmlFor="email">Email</label>
      <input id="email" type="email" name="email" autoComplete="username" placeholder="nama@contoh.id" required />
      <label htmlFor="password">Kata sandi</label>
      <input id="password" type="password" name="password" autoComplete="current-password" required />
      {(error || !auth) && <p className="notice" role="alert">{error || 'Login belum dikonfigurasi.'}</p>}
      <button className="primary" type="submit" disabled={busy || !auth}>{busy ? 'Memeriksa…' : 'Masuk'}</button>
    </form>
  </section>;
}

function Dashboard({ session }: { session: Session }) {
  const [state, setState] = useState<{ summary?: Summary; error?: string }>({});
  const [section, setSection] = useState<'summary' | InventoryKind | 'catalog' | 'in' | 'out'>('summary');
  const [kind, setKind] = useState<Photo['kind']>('asset');
  const [photo, setPhoto] = useState<Photo | null>(null);
  const [reload, setReload] = useState(0);

  useEffect(() => {
    const controller = new AbortController();
    api(session, controller.signal).then((response) => response.json()).then((summary: Summary) => {
      if (!controller.signal.aborted) setState({ summary });
    }).catch((error: Error) => {
      if (!controller.signal.aborted) setState({ error: error.message });
    });
    return () => controller.abort();
  }, [session, reload]);

  if (state.error) return <section className="panel">
    <h1>Ringkasan SIMBAR</h1><p className="notice" role="alert">{state.error}</p>
    <button className="secondary" onClick={() => { setState({}); setReload(reload + 1); }}>Coba lagi</button>
  </section>;
  if (!state.summary) return <p role="status">Memuat ringkasan SIMBAR…</p>;

  const { profile, stats, photos } = state.summary;
  const selectedPhotos = photos.filter((entry) => entry.kind === kind);
  const warehouse = profile.role === 'admin' || profile.role === 'admin_gudang';
  const menu: [typeof section, string][] = [['summary', 'Ringkasan'], ['items', 'Daftar barang'], ['assets', 'Daftar aset']];
  if (warehouse) menu.push(['catalog', 'Kelola barang'], ['in', 'Barang masuk'], ['out', 'Barang keluar']);
  const metrics = [
    ['Jenis barang', stats.items], ['Total stok', stats.stock], ['Aset tercatat', stats.assets], ['Foto terhubung', stats.photos],
  ] as const;

  return <>
    <section className="dashboard-intro">
      <p className="eyebrow">Inventaris &amp; pemeliharaan</p>
      <h1>Ringkasan SIMBAR</h1>
      <p>{profile.name} <span aria-hidden="true">·</span> {roles[profile.role]}</p>
    </section>
    <nav className="filters" aria-label="Menu SIMBAR">
      {menu.map(([value, label]) =>
        <button key={value} className="secondary" aria-pressed={section === value} onClick={() => setSection(value)}>{label}</button>
      )}
    </nav>
    {section === 'catalog' ? <Catalog />
      : section === 'in' || section === 'out' ? <Movements key={section} kind={section} />
      : section !== 'summary' ? <Inventory key={section} kind={section} session={session} request={api} /> : <>
    <dl className="metrics">{metrics.map(([label, count]) =>
      <div className="metric" key={label}><dt>{label}</dt><dd>{number.format(count)}</dd></div>)}</dl>
    <section className="panel">
      <h2>Lampiran inventaris</h2>
      <p>Pilih foto aset atau pemeliharaan dari data cadangan.</p>
      <div className="filters" role="group" aria-label="Jenis lampiran">
        <button className="secondary" aria-pressed={kind === 'asset'} onClick={() => { setKind('asset'); setPhoto(null); }}>Foto aset</button>
        <button className="secondary" aria-pressed={kind === 'maintenance'} onClick={() => { setKind('maintenance'); setPhoto(null); }}>Foto pemeliharaan</button>
      </div>
      {photo && <PhotoViewer key={photo.kind + ':' + photo.id} session={session} photo={photo} close={() => setPhoto(null)} />}
      {selectedPhotos.length ? <ul className="photo-list">
        {selectedPhotos.map((entry) => <li key={entry.id}>
          <div><strong>{entry.title}</strong><span>{entry.detail}</span></div>
          <button className="secondary" onClick={() => setPhoto(entry)} aria-label={'Lihat foto ' + entry.title}>Lihat foto</button>
        </li>)}
      </ul> : <p>Belum ada lampiran pada bagian ini.</p>}
    </section>
    </>}
  </>;
}

function PhotoViewer({ session, photo, close }: { session: Session; photo: Photo; close: () => void }) {
  const [state, setState] = useState<{ src?: string; error?: string }>({});
  const viewer = useRef<HTMLElement>(null);

  useEffect(() => viewer.current?.focus(), []);

  useEffect(() => {
    const controller = new AbortController();
    let objectUrl: string | undefined;
    setState({});
    api(session, controller.signal, { kind: photo.kind, id: String(photo.id) }).then((response) => response.blob()).then((blob) => {
      if (controller.signal.aborted) return;
      objectUrl = URL.createObjectURL(blob);
      setState({ src: objectUrl });
    }).catch((error: Error) => {
      if (!controller.signal.aborted) setState({ error: error.message });
    });
    return () => {
      controller.abort();
      if (objectUrl) URL.revokeObjectURL(objectUrl);
    };
  }, [session, photo]);

  return <figure className="viewer" ref={viewer} tabIndex={-1}>
    <figcaption><strong>{photo.title}</strong><button className="secondary" onClick={close}>Tutup</button></figcaption>
    {state.src ? <img src={state.src} alt={'Lampiran ' + photo.title} />
      : state.error ? <p className="notice" role="alert">{state.error}</p>
        : <p role="status">Memuat foto…</p>}
  </figure>;
}

createRoot(document.getElementById('root')!).render(<App />);
