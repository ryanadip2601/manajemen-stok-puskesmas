// Import a SIMBAR SQLite snapshot into Supabase PostgreSQL.
//
//   npm run import:database -- <backup.sqlite>                  rehearse in a local PGlite database only
//   npm run import:database -- <backup.sqlite> --apply          import into an empty Supabase schema
//   npm run import:database -- <backup.sqlite> --apply --replace  replace staging data (final sync before go-live)
//
// --apply needs SUPABASE_DB_URL (the session pooler URL with the database password) in .env.local.
// --replace keeps each profile's Supabase Auth link by matching email, and keeps imported media.
import assert from 'node:assert/strict';
import { execFileSync } from 'node:child_process';
import { readFile } from 'node:fs/promises';
import { fileURLToPath } from 'node:url';
import { PGlite } from '@electric-sql/pglite';

const args = process.argv.slice(2);
const sqlitePath = args.find((arg) => !arg.startsWith('--'));
const apply = args.includes('--apply');
const replace = args.includes('--replace');
assert.ok(sqlitePath, 'Provide the path to the SQLite backup.');
assert.ok(!replace || apply, '--replace only works together with --apply.');

// Every file is idempotent except schema.sql, which only runs when the schema is missing.
const migrations = ['media-objects.sql', 'preview-access.sql', 'preview-inventory.sql', 'ops-core.sql', 'ops-stock.sql', 'ops-pickup.sql', 'ops-permintaan.sql', 'ops-usulan.sql', 'ops-media.sql', 'ops-aset.sql', 'ops-pemeliharaan.sql', 'ops-penyusutan.sql', 'ops-laporan.sql'];
const TABLES = [
  'categories', 'kategori_aset', 'ruangan', 'units', 'users', 'barang_aset', 'items', 'login_histories', 'logs',
  'sbbk_documents', 'pemeliharaan_aset', 'penyusutan_aset', 'permintaan_barang', 'permintaan_restok', 'stock_in',
  'stock_out', 'stock_out_requests', 'usulan_penerimaan_barang', 'usulan_permintaan_barang',
];
const read = (name) => readFile(new URL('../db/' + name, import.meta.url), 'utf8');

const exporter = fileURLToPath(new URL('./export-sqlite.py', import.meta.url));
const data = execFileSync(process.env.SIMBAR_PYTHON ?? 'python', ['-I', exporter, sqlitePath], {
  encoding: 'utf8',
  maxBuffer: 64 * 1024 * 1024,
});
assert.ok(data.startsWith('BEGIN;\n') && data.trimEnd().endsWith('COMMIT;'), 'Unexpected exporter output.');
// The exporter wraps itself in a transaction; --replace needs the inserts inside its own.
const inserts = data.slice('BEGIN;\n'.length, data.trimEnd().length - 'COMMIT;'.length);
const schema = await read('schema.sql');
const functions = await Promise.all(migrations.map(read));

const countsSql = 'SELECT ' + TABLES.map((table) => `(SELECT count(*)::int FROM simbar."${table}") AS "${table}"`).join(', ');
const totalsSql = `SELECT (SELECT coalesce(sum(stock), 0)::int FROM simbar.items) AS stock,
  (SELECT coalesce(sum(quantity), 0)::int FROM simbar.stock_in) AS stock_in,
  (SELECT coalesce(sum(quantity), 0)::int FROM simbar.stock_out) AS stock_out`;

async function rehearse() {
  const db = new PGlite();
  try {
    await db.exec(`CREATE ROLE anon; CREATE ROLE authenticated; CREATE ROLE service_role;
      CREATE SCHEMA auth; CREATE FUNCTION auth.uid() RETURNS uuid LANGUAGE sql AS 'SELECT null::uuid';`);
    await db.exec(schema);
    await db.exec(data);
    for (const sql of functions) await db.exec(sql);
    const counts = (await db.query(countsSql)).rows[0];
    const totals = (await db.query(totalsSql)).rows[0];
    for (const role of ['anon', 'authenticated', 'service_role']) {
      const access = await db.query("SELECT has_schema_privilege($1, 'simbar', 'USAGE') AS allowed", [role]);
      assert.equal(access.rows[0].allowed, false, `${role} must not reach the simbar schema.`);
    }
    return { counts, totals };
  } finally {
    await db.close();
  }
}

async function applyToSupabase(expected) {
  const connectionString = process.env.SUPABASE_DB_URL;
  assert.ok(connectionString, 'Set SUPABASE_DB_URL in .env.local before using --apply.');
  const { default: pg } = await import('pg');
  const client = new pg.Client({ connectionString, ssl: { rejectUnauthorized: false } });
  await client.connect();
  try {
    const exists = (await client.query("SELECT to_regnamespace('simbar') IS NOT NULL AS exists")).rows[0].exists;
    await client.query('BEGIN');
    if (!exists) await client.query(schema);
    let links = 0;
    if (replace && exists) {
      await client.query(`CREATE TEMP TABLE import_auth_links ON COMMIT DROP AS
        SELECT lower(email) AS email, auth_user_id FROM simbar.users WHERE auth_user_id IS NOT NULL`);
      await client.query('TRUNCATE ' + TABLES.map((table) => `simbar."${table}"`).join(', ') + ' RESTART IDENTITY CASCADE');
    }
    await client.query(inserts);
    if (replace && exists) {
      links = (await client.query(`UPDATE simbar.users u SET auth_user_id = l.auth_user_id
        FROM import_auth_links l WHERE lower(u.email) = l.email`)).rowCount;
    }
    for (const sql of functions) await client.query(sql.replace(/^\s*BEGIN;|COMMIT;\s*$/g, ''));
    assert.deepEqual((await client.query(countsSql)).rows[0], expected.counts, 'Supabase row counts differ from the rehearsal.');
    assert.deepEqual((await client.query(totalsSql)).rows[0], expected.totals, 'Supabase stock totals differ from the rehearsal.');
    await client.query('COMMIT');
    return links;
  } catch (error) {
    await client.query('ROLLBACK').catch(() => {});
    throw error;
  } finally {
    await client.end();
  }
}

try {
  const expected = await rehearse();
  console.log('Rehearsal passed:', JSON.stringify({ ...expected.totals, items: expected.counts.items, assets: expected.counts.barang_aset }));
  if (!apply) {
    console.log('Nothing was written to Supabase. Add --apply to import.');
  } else {
    const links = await applyToSupabase(expected);
    console.log(`Supabase import committed${replace ? `; ${links} Supabase Auth links restored` : ''}.`);
  }
} catch (error) {
  console.error('SIMBAR import failed:', error.code ? `SQLSTATE ${error.code} ${error.message}` : error.message);
  process.exitCode = 1;
}
