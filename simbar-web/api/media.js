import { createHash, randomBytes } from 'node:crypto';
import { MAX_PHOTO_BYTES, error, headers, readPhoto, rpc, uploadPhoto } from './_shared.js';

export default { fetch: handler };

// GET  /api/media?ref=<reference>  returns a photo the caller may see.
// POST /api/media (image/jpeg body) uploads a photo and returns its "media:" reference.
async function handler(request) {
  try {
    if (request.method === 'GET') return await read(request);
    if (request.method === 'POST') return await upload(request);
    return error(405, 'Metode tidak didukung.');
  } catch {
    return error(502, 'Layanan foto SIMBAR belum bisa dihubungi.');
  }
}

async function read(request) {
  const reference = new URL(request.url).searchParams.get('ref') ?? '';
  if (!/^(media:[0-9a-f]{64}|uploads\/[A-Za-z0-9_\-/.]{1,250})$/.test(reference) || reference.includes('..')) {
    return error(400, 'Referensi foto tidak valid.');
  }
  const { data, response } = await rpc(request, 'simbar_media_lookup', { reference });
  if (response) return response;
  if (!data) return error(404, 'Foto tidak ditemukan.');
  return readPhoto(data);
}

async function upload(request) {
  if (request.headers.get('content-type') !== 'image/jpeg') return error(415, 'Foto harus berformat JPEG.');
  const declared = Number(request.headers.get('content-length') ?? 0);
  if (declared > MAX_PHOTO_BYTES) return error(413, 'Ukuran foto maksimal 4 MB.');

  // Check the session before anything reaches Cloudinary.
  const who = await rpc(request, 'simbar_whoami', {});
  if (who.response) return who.response;

  const bytes = Buffer.from(await request.arrayBuffer());
  if (!bytes.length || bytes.length > MAX_PHOTO_BYTES) return error(413, 'Ukuran foto maksimal 4 MB.');
  if (bytes[0] !== 0xff || bytes[1] !== 0xd8 || bytes[2] !== 0xff) return error(415, 'Foto harus berformat JPEG.');

  const id = randomBytes(32).toString('hex');
  const meta = await uploadPhoto(bytes, id);
  const registered = await rpc(request, 'simbar_media_register', {
    public_id: meta.public_id, asset_id: meta.asset_id, asset_version: meta.version,
    file_size: bytes.length, file_sha256: createHash('sha256').update(bytes).digest('hex'),
  });
  if (registered.response) return registered.response;
  return Response.json({ reference: registered.data }, { status: 201, headers });
}
