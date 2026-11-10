import { createHash } from 'node:crypto';

const headers = {
  'Cache-Control': 'private, no-store',
  'X-Content-Type-Options': 'nosniff',
};

function error(status, message) {
  return Response.json({ error: message }, { status, headers });
}

export default { fetch: handler };

async function handler(request) {
  if (request.method !== 'GET') return error(405, 'Metode tidak didukung.');

  const authorization = request.headers.get('authorization');
  if (!authorization?.startsWith('Bearer ')) return error(401, 'Silakan masuk ke SIMBAR.');

  const supabaseUrl = process.env.VITE_SUPABASE_URL;
  const supabaseKey = process.env.VITE_SUPABASE_PUBLISHABLE_KEY;
  if (!supabaseUrl || !supabaseKey) return error(503, 'Koneksi SIMBAR belum tersedia.');

  const query = new URL(request.url).searchParams;
  const kind = query.get('kind');
  const id = query.get('id');
  const list = query.get('list');
  const search = (query.get('q') ?? '').trim();
  const page = query.get('page') ?? '1';
  const photoRequested = kind !== null || id !== null;
  if (list !== null && (photoRequested || !['items', 'assets'].includes(list) ||
      search.length > 100 || !/^[1-9][0-9]{0,4}$/.test(page) || Number(page) > 10000)) {
    return error(400, 'Filter inventaris tidak valid.');
  }
  if (photoRequested && (!['asset', 'maintenance'].includes(kind) || !/^[1-9][0-9]{0,14}$/.test(id ?? ''))) {
    return error(400, 'Referensi foto tidak valid.');
  }

  try {
    const rpcName = list === null ? 'simbar_preview' : 'simbar_preview_inventory';
    let params = {};
    if (list !== null) params = { list_kind: list, search_text: search, page_number: Number(page) };
    if (photoRequested) params = { attachment_kind: kind, attachment_id: Number(id) };
    const rpc = await fetch(new URL('/rest/v1/rpc/' + rpcName, supabaseUrl), {
      method: 'POST',
      headers: { apikey: supabaseKey, authorization, 'Content-Type': 'application/json' },
      body: JSON.stringify(params),
      redirect: 'error',
      signal: AbortSignal.timeout(10000),
    });
    if (rpc.status === 401) return error(401, 'Sesi berakhir. Silakan masuk kembali.');
    if (rpc.status === 403) return error(403, 'Akun ini belum diaktifkan untuk SIMBAR.');
    if (!rpc.ok) return error(502, 'Data SIMBAR belum bisa dimuat.');
    const data = await rpc.json();
    if (!photoRequested) return Response.json(data, { headers });
    if (!data) return error(404, 'Foto tidak ditemukan.');
    return await readPhoto(data);
  } catch {
    return error(502, 'Layanan SIMBAR belum bisa dihubungi.');
  }
}

async function readPhoto({ publicId, format, size }) {
  const cloud = process.env.CLOUDINARY_CLOUD_NAME?.trim();
  const apiKey = process.env.CLOUDINARY_API_KEY?.trim();
  const secret = process.env.CLOUDINARY_API_SECRET?.trim();
  if (!cloud || !apiKey || !secret) return error(503, 'Koneksi foto belum tersedia.');
  if (!/^simbar-hostinger\/[0-9a-f]{64}$/.test(publicId) || !['jpg', 'png'].includes(format) ||
      !Number.isSafeInteger(size) || size <= 0 || size > 1024 * 1024) {
    return error(502, 'Data foto tidak valid.');
  }

  const timestamp = Math.floor(Date.now() / 1000);
  const params = new URLSearchParams({
    expires_at: String(timestamp + 60),
    format,
    public_id: publicId,
    timestamp: String(timestamp),
    type: 'authenticated',
  });
  const toSign = [...params].map(([name, value]) => name + '=' + value).join('&');
  params.set('signature', createHash('sha256').update(toSign + secret).digest('hex'));
  params.set('api_key', apiKey);
  const url = new URL('/v1_1/' + encodeURIComponent(cloud) + '/image/download', 'https://api.cloudinary.com');
  url.search = params.toString();
  const response = await fetch(url, { redirect: 'error', signal: AbortSignal.timeout(10000) });
  if (!response.ok) return error(502, 'Foto belum bisa diunduh.');

  const image = await response.arrayBuffer();
  if (image.byteLength !== size) return error(502, 'Ukuran foto tidak sesuai.');
  return new Response(image, {
    headers: { ...headers, 'Content-Type': format === 'png' ? 'image/png' : 'image/jpeg' },
  });
}
