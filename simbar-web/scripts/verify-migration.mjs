import assert from 'node:assert/strict';
import { execFileSync } from 'node:child_process';
import { readFile } from 'node:fs/promises';
import { fileURLToPath } from 'node:url';
import { PGlite } from '@electric-sql/pglite';

const sqlitePath = process.argv[2];
assert.ok(sqlitePath, 'Provide the path to the SQLite backup');

const exporter = fileURLToPath(new URL('./export-sqlite.py', import.meta.url));
const data = execFileSync(process.env.SIMBAR_PYTHON ?? 'python', [exporter, sqlitePath], {
  encoding: 'utf8',
  maxBuffer: 32 * 1024 * 1024,
});
const schema = await readFile(new URL('../db/schema.sql', import.meta.url), 'utf8');

const db = new PGlite();
try {
  await db.exec('CREATE ROLE anon; CREATE ROLE authenticated; CREATE ROLE service_role;');
  await db.exec(schema);
  try {
    await db.exec(data);
  } catch (error) {
    throw new Error(`PostgreSQL import failed with SQLSTATE ${error.code ?? 'unknown'}`);
  }
  const result = await db.query(`
    SELECT
      (SELECT count(*)::int FROM simbar.items) AS items,
      (SELECT count(*)::int FROM simbar.barang_aset) AS assets,
      (SELECT count(*)::int FROM simbar.stock_in) AS stock_in,
      (SELECT count(*)::int FROM simbar.stock_out) AS stock_out,
      (SELECT sum(stock)::int FROM simbar.items) AS stock_balance,
      (SELECT sum(quantity)::int FROM simbar.stock_in) AS stock_in_quantity,
      (SELECT sum(quantity)::int FROM simbar.stock_out) AS stock_out_quantity,
      (SELECT count(*)::int FROM simbar.ruangan WHERE is_migration_placeholder) AS recovered_rooms,
      (SELECT count(*)::int FROM simbar.barang_aset a JOIN simbar.ruangan r ON r.id = a.ruangan_id
       WHERE r.is_migration_placeholder) AS recovered_assets
  `);
  assert.deepEqual(result.rows[0], {
    items: 137, assets: 775, stock_in: 61, stock_out: 443,
    stock_balance: 4799, stock_in_quantity: 4277, stock_out_quantity: 2639,
    recovered_rooms: 7, recovered_assets: 45,
  });
  for (const role of ['anon', 'authenticated', 'service_role']) {
    const access = await db.query("SELECT has_schema_privilege($1, 'simbar', 'USAGE') AS allowed", [role]);
    assert.equal(access.rows[0].allowed, false);
  }
  const credentials = await db.query(`
    SELECT column_name FROM information_schema.columns
    WHERE table_schema = 'simbar' AND table_name = 'users'
      AND column_name IN ('password', 'reset_code', 'remember_token')
  `);
  assert.equal(credentials.rows.length, 0);
  await assert.rejects(db.exec(data), /already contains data/);
  await db.exec('ROLLBACK');
  const identity = await db.query(`
    INSERT INTO simbar.ruangan(nama_ruangan) VALUES ('Verifikasi sequence') RETURNING id
  `);
  assert.ok(Number(identity.rows[0].id) > 135);
  console.log('PostgreSQL import verified, including private access, room links, counts, and identity sequence.');
} finally {
  await db.close();
}
