// Exercise the operational SIMBAR functions against a SQLite backup in a throwaway PGlite database.
//   npm run verify:operations -- <backup.sqlite>
import assert from 'node:assert/strict';
import { execFileSync } from 'node:child_process';
import { readFile } from 'node:fs/promises';
import { fileURLToPath } from 'node:url';
import { PGlite } from '@electric-sql/pglite';

const sqlitePath = process.argv[2];
assert.ok(sqlitePath, 'Provide the path to the SQLite backup');
const exporter = fileURLToPath(new URL('./export-sqlite.py', import.meta.url));
const data = execFileSync(process.env.SIMBAR_PYTHON ?? 'python', ['-I', exporter, sqlitePath], {
  encoding: 'utf8',
  maxBuffer: 64 * 1024 * 1024,
});
const read = (name) => readFile(new URL('../db/' + name, import.meta.url), 'utf8');

const db = new PGlite();
const ADMIN = '00000000-0000-4000-8000-000000000001';
const STAFF = '00000000-0000-4000-8000-000000000002';
const as = (uid) => db.exec(`SELECT set_config('test.uid', '${uid}', false)`);
const one = async (sql, params) => (await db.query(sql, params)).rows[0];
const stockOf = async (id) => (await one('SELECT stock FROM simbar.items WHERE id = $1', [id])).stock;

try {
  await db.exec(`CREATE ROLE anon; CREATE ROLE authenticated; CREATE ROLE service_role; CREATE SCHEMA auth;
    CREATE FUNCTION auth.uid() RETURNS uuid LANGUAGE sql STABLE AS $$ SELECT nullif(current_setting('test.uid', true), '')::uuid $$;`);
  await db.exec(await read('schema.sql'));
  await db.exec(data);
  const operations = ['ops-core.sql', 'ops-stock.sql', 'ops-pickup.sql'];
  for (const name of ['media-objects.sql', ...operations]) await db.exec(await read(name));
  for (const name of operations) await db.exec(await read(name)); // re-runnable

  const admin = await one(`UPDATE simbar.users SET auth_user_id = $1, is_approved = true
    WHERE id = (SELECT id FROM simbar.users WHERE role IN ('admin', 'admin_gudang') ORDER BY id LIMIT 1) RETURNING id`, [ADMIN]);
  await db.query(`INSERT INTO simbar.users (name, email, role, is_approved, auth_user_id)
    VALUES ('Staf Uji', 'staf-uji@example.invalid', 'staff', true, $1)`, [STAFF]);

  await as('');
  await assert.rejects(db.query('SELECT public.simbar_stock_options()'), /not approved or linked/);
  await as(STAFF);
  await assert.rejects(db.query('SELECT public.simbar_stock_options()'), /Akses ditolak/);

  await as(ADMIN);
  const options = (await one('SELECT public.simbar_stock_options() AS o')).o;
  assert.ok(options.items.length > 0 && options.units.length > 0 && options.categories.length > 0);

  const category = (await one(`SELECT public.simbar_category_save(NULL, 'Kategori Uji', NULL) AS id`)).id;
  await assert.rejects(db.query(`SELECT public.simbar_category_save(NULL, 'kategori uji', NULL)`), /sudah ada/);
  const item = (await one(`SELECT public.simbar_item_save(NULL, 'UJI-001', 'Barang Uji', $1, $2, 'APBD', 1500, 2, NULL, NULL, 5) AS id`,
    [category, options.units[0].id])).id;
  await assert.rejects(db.query(`SELECT public.simbar_item_save(NULL, 'UJI-001', 'Duplikat', $1, $2, 'APBD', 1, 0)`,
    [category, options.units[0].id]), /Kode barang sudah ada/);
  assert.equal(await stockOf(item), 5);

  const stockIn = (await one(`SELECT public.simbar_stock_save('in', NULL, $1, 10, '2026-11-10', 'uji') AS id`, [item])).id;
  assert.equal(await stockOf(item), 15);
  const stockOut = (await one(`SELECT public.simbar_stock_save('out', NULL, $1, 12, '2026-11-10', NULL) AS id`, [item])).id;
  assert.equal(await stockOf(item), 3);
  await assert.rejects(db.query(`SELECT public.simbar_stock_save('out', NULL, $1, 4, '2026-11-10', NULL)`, [item]), /Stok tidak mencukupi/);
  await db.query(`SELECT public.simbar_stock_save('out', $1, NULL, 14, '2026-11-10', NULL)`, [stockOut]);
  assert.equal(await stockOf(item), 1);
  await assert.rejects(db.query(`SELECT public.simbar_stock_save('in', $1, NULL, 1, '2026-11-10', NULL)`, [stockIn]), /Stok tidak mencukupi/);
  await assert.rejects(db.query(`SELECT public.simbar_stock_delete('in', $1)`, [stockIn]), /sudah terpakai/);
  await db.query(`SELECT public.simbar_stock_delete('out', $1)`, [stockOut]);
  assert.equal(await stockOf(item), 15);

  const list = (await one(`SELECT public.simbar_stock_list('in', $1, NULL, NULL, 1) AS l`, [item])).l;
  assert.equal(list.total, 1);
  assert.equal(list.rows[0].recorded_by.length > 0, true);
  await assert.rejects(db.query(`SELECT public.simbar_item_delete($1)`, [item]), /riwayat/);
  await assert.rejects(db.query(`SELECT public.simbar_category_delete($1)`, [category]), /masih dipakai/);
  await db.query(`SELECT public.simbar_stock_delete('in', $1)`, [stockIn]);
  await db.query(`SELECT public.simbar_item_delete($1)`, [item]);
  await db.query(`SELECT public.simbar_category_delete($1)`, [category]);

  // Pengambilan barang: staff submits, warehouse approves (stock drops) or rejects.
  const stocked = (await one('SELECT id, stock FROM simbar.items WHERE stock >= 3 ORDER BY id LIMIT 1'));
  const other = (await one('SELECT id FROM simbar.items WHERE stock >= 1 AND id <> $1 ORDER BY id LIMIT 1', [stocked.id]));
  await as(STAFF);
  assert.ok((await one('SELECT public.simbar_pickup_options() AS o')).o.every((row) => row.stock > 0));
  await assert.rejects(db.query('SELECT public.simbar_pickup_list(false, $1, 1)', ['all']), /Akses ditolak/);
  await assert.rejects(db.query('SELECT public.simbar_pickup_submit($1)',
    [JSON.stringify([{ item_id: stocked.id, quantity: 1 }, { item_id: stocked.id, quantity: 1 }])]), /satu kali/);
  await assert.rejects(db.query('SELECT public.simbar_pickup_submit($1)',
    [JSON.stringify([{ item_id: stocked.id, quantity: stocked.stock + 1 }])]), /melebihi stok/);
  assert.equal((await one('SELECT public.simbar_pickup_submit($1) AS n',
    [JSON.stringify([{ item_id: stocked.id, quantity: 2, notes: 'uji' }, { item_id: other.id, quantity: 1 }])])).n, 2);
  const mine = (await one(`SELECT public.simbar_pickup_list(true, 'pending', 1) AS l`)).l;
  assert.equal(mine.total, 2);
  const [second, first] = mine.rows.map((row) => row.id).sort((a, b) => b - a);
  await db.query('SELECT public.simbar_pickup_cancel($1)', [second]);
  await assert.rejects(db.query('SELECT public.simbar_pickup_cancel($1)', [second]), /Menunggu/);

  await as(ADMIN);
  await assert.rejects(db.query('SELECT public.simbar_pickup_cancel($1)', [first]), /tidak ditemukan/);
  const checklist = (await one(`SELECT public.simbar_pickup_list(false, 'pending', 1) AS l`)).l;
  assert.ok(checklist.counts.pending >= 1 && checklist.rows.some((row) => row.id === first));
  await db.query(`SELECT public.simbar_checklist_act($1, 'approve', NULL)`, [first]);
  assert.equal(await stockOf(stocked.id), stocked.stock - 2);
  await assert.rejects(db.query(`SELECT public.simbar_checklist_act($1, 'reject', 'x')`, [first]), /sudah diproses/);
  const done = await one('SELECT status, pickup_date IS NOT NULL AS picked FROM simbar.stock_out_requests WHERE id = $1', [first]);
  assert.deepEqual(done, { status: 'completed', picked: true });
  await as(STAFF);
  const rejectMe = (await one('SELECT public.simbar_pickup_submit($1) AS n', [JSON.stringify([{ item_id: other.id, quantity: 1 }])]));
  assert.equal(rejectMe.n, 1);
  const pending = (await one(`SELECT public.simbar_pickup_list(true, 'pending', 1) AS l`)).l.rows[0].id;
  await as(ADMIN);
  await db.query(`SELECT public.simbar_checklist_act($1, 'reject', 'Stok untuk program')`, [pending]);
  assert.equal((await one('SELECT reject_reason FROM simbar.stock_out_requests WHERE id = $1', [pending])).reject_reason, 'Stok untuk program');

  const logs = await one(`SELECT count(*)::int AS n FROM simbar.logs WHERE user_id = $1 AND created_at > now() - interval '1 day'`, [admin.id]);
  assert.ok(logs.n >= 8, 'Every change is logged.');
  const balance = await one(`SELECT
      (SELECT coalesce(sum(stock), 0) FROM simbar.items)::int AS stock,
      (SELECT count(*) FROM simbar.items WHERE stock < 0)::int AS negative`);
  assert.equal(balance.negative, 0);
  console.log(`Operational stock functions verified on the backup (${balance.stock} units in stock, roles enforced).`);
} finally {
  await db.close();
}
