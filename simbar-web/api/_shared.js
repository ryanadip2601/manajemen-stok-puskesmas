import { createHash } from 'node:crypto';

export const headers = {
  'Cache-Control': 'private, no-store',
  'X-Content-Type-Options': 'nosniff',
};

export const MAX_PHOTO_BYTES = 4 * 1024 * 1024; // below Vercel's 4.5 MB request/response limit

export function error(status, message) {
  return Response.json({ error: message }, { status, headers });
}

// Calls a SIMBAR database function as the signed-in user; the function itself enforces the role.
export async function rpc(request, name, params) {
  const supabaseUrl = process.env.VITE_SUPABASE_URL;
  const supabaseKey = process.env.VITE_SUPABASE_PUBLISHABLE_KEY;
  const authorization = request.headers.get('authorization');
  if (!supabaseUrl || !supabaseKey) return { response: error(503, 'Koneksi SIMBAR belum tersedia.') };
  if (!authorization?.startsWith('Bearer ')) return { response: error(401, 'Silakan masuk ke SIMBAR.') };
  const result = await fetch(new URL('/rest/v1/rpc/' + name, supabaseUrl), {
    method: 'POST',
    headers: { apikey: supabaseKey, authorization, 'Content-Type': 'application/json' },
    body: JSON.stringify(params),
    redirect: 'error',
    signal: AbortSignal.timeout(10000),
  });
  if (result.status === 401) return { response: error(401, 'Sesi berakhir. Silakan masuk kembali.') };
  if (result.status === 403) return { response: error(403, 'Akun ini belum diaktifkan untuk SIMBAR.') };
  if (!result.ok) return { response: error(502, 'Data SIMBAR belum bisa dimuat.') };
  return { data: await result.json() };
}

function cloudinary() {
  const cloud = process.env.CLOUDINARY_CLOUD_NAME?.trim();
  const apiKey = process.env.CLOUDINARY_API_KEY?.trim();
  const secret = process.env.CLOUDINARY_API_SECRET?.trim();
  return cloud && apiKey && secret ? { cloud, apiKey, secret } : null;
}

function sign(params, secret) {
  const payload = Object.keys(params).sort().map((name) => name + '=' + params[name]).join('&');
  return createHash('sha256').update(payload + secret).digest('hex');
}

// A small square version of a private photo (signed delivery URL with a transformation), cached by the browser for a day.
const THUMB_SIZES = new Set([96, 160, 320, 640]);
export async function readPhotoThumb({ publicId, format }, width) {
  const config = cloudinary();
  if (!config) return error(503, 'Koneksi foto belum tersedia.');
  if (!/^simbar-(hostinger|uploads)\/[0-9a-f]{64}$/.test(publicId) || !['jpg', 'png'].includes(format) || !THUMB_SIZES.has(width)) {
    return error(400, 'Data foto tidak valid.');
  }
  const transformation = `c_fill,g_auto,w_${width},h_${width},q_auto,f_jpg`;
  const signature = createHash('sha1').update(`${transformation}/${publicId}.${format}${config.secret}`).digest('base64')
    .replace(/\+/g, '-').replace(/\//g, '_').slice(0, 8);
  const url = `https://res.cloudinary.com/${encodeURIComponent(config.cloud)}/image/authenticated/s--${signature}--/${transformation}/${publicId}.${format}`;
  const response = await fetch(url, { redirect: 'error', signal: AbortSignal.timeout(10000) });
  if (!response.ok) return error(502, 'Foto belum bisa diunduh.');
  const image = await response.arrayBuffer();
  if (!image.byteLength || image.byteLength > MAX_PHOTO_BYTES) return error(502, 'Ukuran foto tidak sesuai.');
  return new Response(image, { headers: { ...headers, 'Content-Type': 'image/jpeg', 'Cache-Control': 'private, max-age=86400' } });
}

// Streams a private Cloudinary original back to the browser after the database approved the request.
export async function readPhoto({ publicId, format, size }) {
  const config = cloudinary();
  if (!config) return error(503, 'Koneksi foto belum tersedia.');
  if (!/^simbar-(hostinger|uploads)\/[0-9a-f]{64}$/.test(publicId) || !['jpg', 'png'].includes(format) ||
      !Number.isSafeInteger(size) || size <= 0 || size > MAX_PHOTO_BYTES) {
    return error(502, 'Data foto tidak valid.');
  }
  const timestamp = String(Math.floor(Date.now() / 1000));
  const params = { expires_at: String(Number(timestamp) + 60), format, public_id: publicId, timestamp, type: 'authenticated' };
  const url = new URL('/v1_1/' + encodeURIComponent(config.cloud) + '/image/download', 'https://api.cloudinary.com');
  url.search = new URLSearchParams({ ...params, signature: sign(params, config.secret), api_key: config.apiKey }).toString();
  const response = await fetch(url, { redirect: 'error', signal: AbortSignal.timeout(10000) });
  if (!response.ok) return error(502, 'Foto belum bisa diunduh.');
  const image = await response.arrayBuffer();
  if (image.byteLength !== size) return error(502, 'Ukuran foto tidak sesuai.');
  return new Response(image, {
    headers: { ...headers, 'Content-Type': format === 'png' ? 'image/png' : 'image/jpeg' },
  });
}

// Uploads a JPEG as a private (authenticated) Cloudinary asset under simbar-uploads/<random hex>.
// Barang photos are product pictures, not private records: public images in simbar-items/, served straight from the CDN.
export async function uploadItemPhoto(bytes, id) {
  const config = cloudinary();
  if (!config) throw new Error('Koneksi foto belum tersedia.');
  const params = { asset_folder: 'SIMBAR/barang', overwrite: 'false', public_id: 'simbar-items/' + id, timestamp: String(Math.floor(Date.now() / 1000)) };
  const form = new FormData();
  for (const [name, value] of Object.entries({ ...params, api_key: config.apiKey, signature: sign(params, config.secret) })) form.set(name, value);
  form.set('file', new Blob([bytes], { type: 'image/jpeg' }), 'foto.jpg');
  const response = await fetch(`https://api.cloudinary.com/v1_1/${encodeURIComponent(config.cloud)}/image/upload`, {
    method: 'POST', body: form, redirect: 'error', signal: AbortSignal.timeout(25000),
  });
  const meta = response.ok ? await response.json().catch(() => null) : null;
  if (meta?.public_id !== params.public_id || meta?.type !== 'upload' || meta?.format !== 'jpg'
      || typeof meta?.secure_url !== 'string' || !meta.secure_url.startsWith('https://res.cloudinary.com/')) {
    throw new Error('Foto belum bisa disimpan ke Cloudinary.');
  }
  return meta.secure_url;
}

export async function uploadPhoto(bytes, id) {
  const config = cloudinary();
  if (!config) throw new Error('Koneksi foto belum tersedia.');
  const params = {
    asset_folder: 'SIMBAR',
    overwrite: 'false',
    public_id: 'simbar-uploads/' + id,
    timestamp: String(Math.floor(Date.now() / 1000)),
    type: 'authenticated',
  };
  const form = new FormData();
  for (const [name, value] of Object.entries({ ...params, api_key: config.apiKey, signature: sign(params, config.secret) })) {
    form.set(name, value);
  }
  form.set('file', new Blob([bytes], { type: 'image/jpeg' }), 'foto.jpg');
  const response = await fetch(`https://api.cloudinary.com/v1_1/${encodeURIComponent(config.cloud)}/image/upload`, {
    method: 'POST', body: form, redirect: 'error', signal: AbortSignal.timeout(25000),
  });
  const meta = response.ok ? await response.json().catch(() => null) : null;
  if (meta?.public_id !== params.public_id || meta?.type !== 'authenticated' || meta?.format !== 'jpg' ||
      !/^[a-f0-9]{32}$/.test(meta?.asset_id) || !Number.isSafeInteger(meta?.version)) {
    throw new Error('Foto belum bisa disimpan ke Cloudinary.');
  }
  return meta;
}
