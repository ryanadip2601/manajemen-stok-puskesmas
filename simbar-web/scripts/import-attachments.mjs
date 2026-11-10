// Upload legacy SIMBAR attachments to Cloudinary and record them in simbar.media_objects.
//
//   npm run import:attachments -- <folder-with-public/uploads>          list what is missing
//   npm run import:attachments -- <folder-with-public/uploads> --apply  upload, verify, and record
//
// Needs SUPABASE_DB_URL plus CLOUDINARY_CLOUD_NAME, CLOUDINARY_API_KEY and CLOUDINARY_API_SECRET in .env.local.
// Each file is downloaded back from Cloudinary and compared by SHA-256 before it is recorded.
import assert from 'node:assert/strict';
import { createHash, randomBytes } from 'node:crypto';
import { readFile } from 'node:fs/promises';
import path from 'node:path';
import pg from 'pg';

const args = process.argv.slice(2);
const root = args.find((arg) => !arg.startsWith('--'));
const apply = args.includes('--apply');
assert.ok(root, 'Provide the Laravel folder that contains public/uploads.');

const cloud = process.env.CLOUDINARY_CLOUD_NAME?.trim();
const apiKey = process.env.CLOUDINARY_API_KEY?.trim();
const secret = process.env.CLOUDINARY_API_SECRET?.trim();
const sha256 = (buffer) => createHash('sha256').update(buffer).digest('hex');

function formatOf(buffer) {
  if (buffer.subarray(0, 3).equals(Buffer.from([0xff, 0xd8, 0xff]))) return 'jpg';
  if (buffer.subarray(0, 8).equals(Buffer.from([0x89, 0x50, 0x4e, 0x47, 0x0d, 0x0a, 0x1a, 0x0a]))) return 'png';
  return null;
}

function sign(params) {
  const payload = Object.keys(params).sort().map((name) => name + '=' + params[name]).join('&');
  return createHash('sha256').update(payload + secret).digest('hex');
}

async function request(url, options, label) {
  const response = await fetch(url, { ...options, redirect: 'error', signal: AbortSignal.timeout(30000) }).catch(() => null);
  if (!response?.ok) throw new Error(`${label} failed (${response?.status ?? 'connection'}).`);
  return response;
}

async function upload(file) {
  const timestamp = String(Math.floor(Date.now() / 1000));
  const params = {
    asset_folder: 'SIMBAR',
    overwrite: 'false',
    public_id: 'simbar-hostinger/' + randomBytes(32).toString('hex'),
    timestamp,
    type: 'authenticated',
  };
  const form = new FormData();
  for (const [name, value] of Object.entries({ ...params, api_key: apiKey, signature: sign(params) })) form.set(name, value);
  form.set('file', new Blob([file.buffer], { type: file.format === 'png' ? 'image/png' : 'image/jpeg' }), 'attachment.' + file.format);
  const meta = await (await request(`https://api.cloudinary.com/v1_1/${cloud}/image/upload`, { method: 'POST', body: form }, 'Upload')).json();
  if (meta.public_id !== params.public_id || meta.type !== 'authenticated' || meta.format !== file.format ||
      !/^[a-f0-9]{32}$/.test(meta.asset_id) || !Number.isSafeInteger(meta.version)) {
    throw new Error('Cloudinary returned unexpected metadata.');
  }

  const download = { expires_at: String(Number(timestamp) + 600), format: file.format, public_id: meta.public_id, timestamp, type: 'authenticated' };
  const url = new URL(`https://api.cloudinary.com/v1_1/${cloud}/image/download`);
  url.search = new URLSearchParams({ ...download, api_key: apiKey, signature: sign(download) }).toString();
  const copy = Buffer.from(await (await request(url, {}, 'Verification download')).arrayBuffer());
  if (copy.length !== file.buffer.length || sha256(copy) !== file.sha256) throw new Error('Cloudinary copy differs from the original.');
  return meta;
}

const client = new pg.Client({ connectionString: process.env.SUPABASE_DB_URL, ssl: { rejectUnauthorized: false } });
try {
  assert.ok(process.env.SUPABASE_DB_URL, 'Set SUPABASE_DB_URL in .env.local.');
  if (apply) assert.ok(cloud && apiKey && secret, 'Set the three CLOUDINARY_* variables in .env.local before --apply.');
  await client.connect();
  const { rows } = await client.query(`
    SELECT DISTINCT ref FROM (
      SELECT foto AS ref FROM simbar.barang_aset
      UNION ALL SELECT lampiran_foto FROM simbar.pemeliharaan_aset
    ) refs
    WHERE ref IS NOT NULL AND ref <> ''
      AND NOT EXISTS (SELECT 1 FROM simbar.media_objects m WHERE m.legacy_reference = refs.ref)
    ORDER BY ref`);

  const files = [];
  const problems = [];
  for (const { ref } of rows) {
    const legacyPath = path.posix.join('public', ref.replace(/^\/+/, ''));
    const buffer = await readFile(path.join(root, ...legacyPath.split('/'))).catch(() => null);
    const format = buffer && formatOf(buffer);
    if (!buffer) problems.push(`${ref}: file not found`);
    else if (!format) problems.push(`${ref}: not a JPEG or PNG`);
    else files.push({ ref, legacyPath, buffer, format, sha256: sha256(buffer) });
  }
  console.log(`${rows.length} references without media; ${files.length} ready, ${problems.length} unusable.`);
  for (const problem of problems) console.log('  - ' + problem);
  if (!apply) {
    console.log('Nothing was uploaded. Add --apply to upload.');
  } else {
    for (const file of files) {
      const meta = await upload(file);
      await client.query(`INSERT INTO simbar.media_objects (legacy_path, legacy_reference, cloudinary_asset_id,
          cloudinary_public_id, cloudinary_version, format, file_size, sha256)
        VALUES ($1, $2, $3, $4, $5, $6, $7, $8)`,
      [file.legacyPath, file.ref, meta.asset_id, meta.public_id, meta.version, file.format, file.buffer.length, file.sha256]);
      console.log('  ✓ ' + file.ref);
    }
    console.log(`${files.length} attachments uploaded, verified, and recorded.`);
  }
} catch (error) {
  console.error('SIMBAR attachment import failed:', error.message);
  process.exitCode = 1;
} finally {
  await client.end().catch(() => {});
}
