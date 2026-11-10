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
  if (isLink(reference)) return <img className="request-photo" src={reference} alt={alt} referrerPolicy="no-referrer" loading="lazy" />;
  if (state.error) return <span className="cell-note">{state.error}</span>;
  if (!state.src) return <span className="cell-note" role="status">Memuat foto…</span>;
  return <img className="request-photo" src={state.src} alt={alt} />;
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
