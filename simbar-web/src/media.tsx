import { useEffect, useState } from 'react';
import { client } from './supabase';

const MAX_SIDE = 1600;

async function token() {
  const session = (await client?.auth.getSession())?.data.session;
  if (!session) throw new Error('Sesi berakhir. Silakan masuk kembali.');
  return session.access_token;
}

// Shrinks the picture in the browser (max 1600 px, JPEG) so uploads stay small and EXIF data is dropped.
async function toJpeg(file: File): Promise<Blob> {
  if (!file.type.startsWith('image/')) throw new Error('File harus berupa gambar.');
  if (file.size > 20 * 1024 * 1024) throw new Error('Ukuran gambar maksimal 20 MB.');
  const bitmap = await createImageBitmap(file).catch(() => {
    throw new Error('Gambar tidak bisa dibaca. Coba format JPG atau PNG.');
  });
  const scale = Math.min(1, MAX_SIDE / Math.max(bitmap.width, bitmap.height));
  const canvas = document.createElement('canvas');
  canvas.width = Math.round(bitmap.width * scale);
  canvas.height = Math.round(bitmap.height * scale);
  const context = canvas.getContext('2d')!;
  context.fillStyle = '#fff';
  context.fillRect(0, 0, canvas.width, canvas.height);
  context.drawImage(bitmap, 0, 0, canvas.width, canvas.height);
  bitmap.close();
  for (const quality of [0.85, 0.7, 0.55]) {
    const blob = await new Promise<Blob | null>((resolve) => canvas.toBlob(resolve, 'image/jpeg', quality));
    if (blob && blob.size <= 3.5 * 1024 * 1024) return blob;
  }
  throw new Error('Gambar terlalu besar.');
}

export async function uploadPhoto(file: File): Promise<string> {
  const body = await toJpeg(file);
  const response = await fetch('/api/media', {
    method: 'POST',
    headers: { Authorization: 'Bearer ' + await token(), 'Content-Type': 'image/jpeg' },
    body,
  });
  const result = await response.json().catch(() => null);
  if (!response.ok || typeof result?.reference !== 'string') throw new Error(result?.error ?? 'Foto belum bisa diunggah.');
  return result.reference;
}

export const isLink = (value: string) => /^https?:\/\//.test(value);

// Shows a stored photo: links load directly, uploaded and legacy photos come through /api/media.
export function StoredPhoto({ reference, alt }: { reference: string; alt: string }) {
  const [state, setState] = useState<{ src?: string; error?: string }>({});
  useEffect(() => {
    if (isLink(reference)) return;
    let objectUrl: string | undefined;
    const controller = new AbortController();
    setState({});
    token().then((value) => fetch('/api/media?ref=' + encodeURIComponent(reference), {
      headers: { Authorization: 'Bearer ' + value }, cache: 'no-store', signal: controller.signal,
    })).then(async (response) => {
      if (!response.ok) throw new Error((await response.json().catch(() => null))?.error ?? 'Foto belum bisa dimuat.');
      objectUrl = URL.createObjectURL(await response.blob());
      if (!controller.signal.aborted) setState({ src: objectUrl });
    }).catch((error: Error) => {
      if (!controller.signal.aborted) setState({ error: error.message });
    });
    return () => {
      controller.abort();
      if (objectUrl) URL.revokeObjectURL(objectUrl);
    };
  }, [reference]);
  if (isLink(reference)) return <img className="request-photo zoomable" src={reference} alt={alt} referrerPolicy="no-referrer" loading="lazy" {...zoomProps({ reference, alt })} />;
  if (state.error) return <span className="cell-note">{state.error}</span>;
  if (!state.src) return <span className="cell-note" role="status">Memuat foto…</span>;
  return <img className="request-photo zoomable" src={state.src} alt={alt} {...zoomProps({ reference, alt })} />;
}

// One photo field: keeps the current reference, uploads a replacement, or clears it.
export function PhotoField({ label, value, onChange, allowLink = false }: {
  label: string; value: string | null; onChange: (value: string | null) => void; allowLink?: boolean;
}) {
  const [busy, setBusy] = useState(false);
  const [problem, setProblem] = useState('');
  const [link, setLink] = useState('');

  async function pick(file: File | undefined) {
    if (!file) return;
    setBusy(true);
    setProblem('');
    try {
      onChange(await uploadPhoto(file));
    } catch (error) {
      setProblem((error as Error).message);
    } finally {
      setBusy(false);
    }
  }

  return <fieldset className="wide choice photo-field">
    <legend>{label}</legend>
    {value && <div className="photo-row"><StoredPhoto reference={value} alt={label} />
      <button className="secondary" type="button" onClick={() => onChange(null)}>Hapus foto</button></div>}
    <label className="file-button secondary">
      {busy ? 'Mengunggah…' : value ? 'Ganti dengan foto lain' : 'Ambil / pilih foto'}
      <input type="file" accept="image/*" capture="environment" disabled={busy}
        onChange={(event) => { pick(event.target.files?.[0]); event.target.value = ''; }} />
    </label>
    {allowLink && <div className="filters">
      <input type="url" maxLength={2000} value={link} onChange={(event) => setLink(event.target.value)}
        placeholder="atau tempel link gambar https://…" aria-label={label + ' (link)'} />
      <button className="secondary" type="button" disabled={!/^https?:\/\/\S+$/.test(link.trim())}
        onClick={() => { onChange(link.trim()); setLink(''); }}>Pakai link</button>
    </div>}
    {problem && <p className="notice" role="alert">{problem}</p>}
  </fieldset>;
}

// ---------- Foto barang (public product photos) ----------
// Cloudinary photos are resized on the CDN; other links are shown as they are.
export function photoThumb(url: string, size: number) {
  return url.includes('res.cloudinary.com/') && url.includes('/image/upload/')
    ? url.replace('/image/upload/', `/image/upload/c_pad,b_white,w_${size},h_${size},f_auto,q_auto/`) : url;
}

export function ItemThumb({ url, name, size = 44, zoom = true }: { url?: string | null; name: string; size?: number; zoom?: boolean }) {
  const [broken, setBroken] = useState(false);
  if (!url || broken) return <span className="item-thumb empty" style={{ width: size, height: size }} aria-hidden="true"><i className="fas fa-box" /></span>;
  return <img className={'item-thumb' + (zoom ? ' zoomable' : '')} src={photoThumb(url, size * 2)} alt={'Foto ' + name} width={size} height={size}
    {...(zoom ? zoomProps({ url, alt: 'Foto ' + name, caption: name }) : {})}
    loading="lazy" referrerPolicy="no-referrer" onError={() => setBroken(true)} />;
}

// Uploads a real barang photo (shrunk to JPEG in the browser) and returns its public URL.
export async function uploadItemPhoto(file: File): Promise<string> {
  const body = await toJpeg(file);
  const response = await fetch('/api/media?purpose=item', {
    method: 'POST',
    headers: { Authorization: 'Bearer ' + await token(), 'Content-Type': 'image/jpeg' },
    body,
  });
  const result = await response.json().catch(() => null);
  if (!response.ok || typeof result?.url !== 'string') throw new Error(result?.error ?? 'Foto belum bisa diunggah.');
  return result.url;
}

export const isRealPhoto = (source?: string | null) => source === 'Foto asli';
export const photoSourceLabel = (source?: string | null) => !source ? 'Belum ada foto'
  : source === 'Foto asli' ? 'Foto asli' : source.startsWith('Wikimedia') ? 'Foto sementara (Wikimedia Commons)'
  : source.startsWith('Google') ? 'Foto sementara (Google/toko online)' : source.startsWith('Gambar sementara') ? 'Gambar sementara' : 'Foto sementara (' + source + ')';

// ---------- Private photos as small squares (asset lists and detail) ----------
// Each size of each photo is fetched once per session and reused, so lists do not flicker or refetch.
const thumbCache = new Map<string, Promise<string>>();
function privateThumbUrl(reference: string, size: number) {
  const key = reference + '@' + size;
  let pending = thumbCache.get(key);
  if (!pending) {
    pending = token().then((value) => fetch(`/api/media?ref=${encodeURIComponent(reference)}&w=${size}`, { headers: { Authorization: 'Bearer ' + value } }))
      .then(async (response) => {
        if (!response.ok) throw new Error((await response.json().catch(() => null))?.error ?? 'Foto belum bisa dimuat.');
        return URL.createObjectURL(await response.blob());
      });
    pending.catch(() => thumbCache.delete(key));
    thumbCache.set(key, pending);
  }
  return pending;
}

export function PrivateThumb({ reference, alt, size, display = size, className = '', caption }: {
  reference: string; alt: string; size: 96 | 160 | 320 | 640; display?: number; className?: string; caption?: string;
}) {
  const [src, setSrc] = useState<string | null>(null);
  const [failed, setFailed] = useState(false);
  useEffect(() => {
    let live = true;
    setSrc(null);
    setFailed(false);
    privateThumbUrl(reference, size).then((url) => { if (live) setSrc(url); }).catch(() => { if (live) setFailed(true); });
    return () => { live = false; };
  }, [reference, size]);
  if (failed) return <span className={'item-thumb empty ' + className} style={{ width: display, height: display }} title="Foto belum bisa dimuat"><i className="fas fa-image" /></span>;
  if (!src) return <span className={'item-thumb empty loading ' + className} style={{ width: display, height: display }} aria-hidden="true" />;
  return <img className={'item-thumb zoomable ' + className} src={src} alt={alt} width={display} height={display}
    {...zoomProps({ reference, alt, caption: caption ?? alt })} />;
}

// ---------- Pop-up photo viewer: click any photo to see it large ----------
type PhotoRequest = { url?: string | null; reference?: string | null; alt: string; caption?: string };
export function openPhoto(request: PhotoRequest) {
  window.dispatchEvent(new CustomEvent<PhotoRequest>('simbar-photo', { detail: request }));
}
// Props that make an <img> open the viewer on click or Enter.
export function zoomProps(request: PhotoRequest) {
  return {
    role: 'button', tabIndex: 0, title: 'Klik untuk memperbesar', 'data-zoom': '',
    onClick: (event: React.MouseEvent) => { event.stopPropagation(); event.preventDefault(); openPhoto(request); },
    onKeyDown: (event: React.KeyboardEvent) => { if (event.key === 'Enter' || event.key === ' ') { event.preventDefault(); openPhoto(request); } },
  } as const;
}

export function PhotoViewer() {
  const [request, setRequest] = useState<PhotoRequest | null>(null);
  const [src, setSrc] = useState<string | null>(null);
  const [problem, setProblem] = useState('');
  useEffect(() => {
    const show = (event: Event) => setRequest((event as CustomEvent<PhotoRequest>).detail);
    window.addEventListener('simbar-photo', show);
    return () => window.removeEventListener('simbar-photo', show);
  }, []);
  useEffect(() => {
    if (!request) return;
    let live = true;
    let objectUrl: string | undefined;
    setSrc(null);
    setProblem('');
    if (request.url) setSrc(photoThumb(request.url, 1200).replace('c_pad,b_white,w_1200,h_1200', 'c_limit,w_1600,h_1600'));
    else if (request.reference) {
      if (isLink(request.reference)) setSrc(request.reference);
      else token().then((value) => fetch('/api/media?ref=' + encodeURIComponent(request.reference!), { headers: { Authorization: 'Bearer ' + value } }))
        .then(async (response) => {
          if (!response.ok) throw new Error((await response.json().catch(() => null))?.error ?? 'Foto belum bisa dimuat.');
          objectUrl = URL.createObjectURL(await response.blob());
          if (live) setSrc(objectUrl);
        }).catch((error: Error) => { if (live) setProblem(error.message); });
    }
    const key = (event: KeyboardEvent) => { if (event.key === 'Escape') setRequest(null); };
    window.addEventListener('keydown', key);
    return () => { live = false; window.removeEventListener('keydown', key); if (objectUrl) URL.revokeObjectURL(objectUrl); };
  }, [request]);
  if (!request) return null;
  return <div className="photo-viewer" role="dialog" aria-modal="true" aria-label={request.alt} onClick={() => setRequest(null)}>
    <button className="photo-viewer-close" aria-label="Tutup foto" onClick={() => setRequest(null)}><i className="fas fa-xmark" aria-hidden="true" /></button>
    <figure onClick={(event) => event.stopPropagation()}>
      {problem ? <p className="notice" role="alert">{problem}</p>
        : src ? <img src={src} alt={request.alt} referrerPolicy="no-referrer" />
        : <p className="photo-viewer-loading" role="status">Memuat foto…</p>}
      {(request.caption || request.alt) && <figcaption>{request.caption ?? request.alt}</figcaption>}
    </figure>
  </div>;
}
