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
  const operations = ['ops-core.sql', 'ops-stock.sql', 'ops-pickup.sql', 'ops-permintaan.sql'];
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

  // Permintaan barang: restok becomes "tersedia" after barang masuk; barang baru creates a barang on approval.
  const low = await one(`INSERT INTO simbar.items (code, name, category_id, unit_id, stock, minimum_stock, perolehan, price)
    SELECT 'UJI-RESTOK', 'Barang Restok Uji', c.id, u.id, 0, 3, 'BLUD', 0
    FROM simbar.categories c, simbar.units u ORDER BY c.id, u.id LIMIT 1 RETURNING id, category_id, unit_id`);
  await as(STAFF);
  const restok = (await one(`SELECT public.simbar_permintaan_save(NULL, 'restok', $1, NULL, NULL, 10, 'tinggi', 'habis') AS code`, [low.id])).code;
  assert.match(restok, /^PBR-\d{6}-\d{4}$/);
  await assert.rejects(db.query(`SELECT public.simbar_permintaan_save(NULL, 'restok', $1, NULL, NULL, 1, 'sedang')`, [low.id]), /masih aktif/);
  const plenty = await one('SELECT id FROM simbar.items WHERE stock > minimum_stock ORDER BY id LIMIT 1');
  await assert.rejects(db.query(`SELECT public.simbar_permintaan_save(NULL, 'restok', $1, NULL, NULL, 1, 'sedang')`, [plenty.id]), /stok yang cukup/);
  const baru = (await one(`SELECT public.simbar_permintaan_save(NULL, 'barang_baru', NULL, 'Termometer Uji', NULL, 4, 'sedang', NULL,
    'https://example.com/termometer.png') AS code`)).code;
  assert.notEqual(baru, restok);
  await assert.rejects(db.query('SELECT public.simbar_permintaan_list(false, $1, $2, $3, 1)', ['all', 'all', '']), /Akses ditolak/);
  const myRequests = (await one(`SELECT public.simbar_permintaan_list(true, 'pending', 'all', 'Uji', 1) AS l`)).l;
  assert.equal(myRequests.total, 2);
  const restokId = myRequests.rows.find((row) => row.code === restok).id;
  const baruId = myRequests.rows.find((row) => row.code === baru).id;

  await as(ADMIN);
  await assert.rejects(db.query(`SELECT public.simbar_permintaan_act($1, 'reject', NULL)`, [restokId]), /wajib diisi/);
  await db.query(`SELECT public.simbar_permintaan_act($1, 'revision', 'Jumlah terlalu banyak')`, [baruId]);
  await as(STAFF);
  await db.query(`SELECT public.simbar_permintaan_save($1, 'barang_baru', NULL, 'Termometer Uji', NULL, 2, 'sedang')`, [baruId]);
  assert.equal((await one(`SELECT public.simbar_permintaan_get($1) AS g`, [baruId])).g.status, 'pending');
  await as(ADMIN);
  await db.query(`SELECT public.simbar_permintaan_act($1, 'proses')`, [baruId]);
  const createdCode = (await one(`SELECT public.simbar_permintaan_act($1, 'approve', 'OK', $2) AS c`, [baruId,
    JSON.stringify({ code: 'UJI-BARU-01', category_id: low.category_id, unit_id: low.unit_id, price: 25000, perolehan: 'BLUD', minimum_stock: 1 })])).c;
  assert.equal(createdCode, 'UJI-BARU-01');
  assert.equal((await one(`SELECT stock FROM simbar.items WHERE code = 'UJI-BARU-01'`)).stock, 2);
  await db.query(`SELECT public.simbar_permintaan_act($1, 'approve', NULL)`, [restokId]);
  await assert.rejects(db.query(`SELECT public.simbar_permintaan_act($1, 'reject', 'x')`, [restokId]), /sudah diverifikasi/);
  await db.query(`SELECT public.simbar_stock_save('in', NULL, $1, 10, '2026-11-10', NULL)`, [low.id]);
  await as(STAFF);
  const alerts = (await one('SELECT public.simbar_permintaan_notifications() AS n')).n;
  assert.deepEqual(alerts.map((alert) => alert.code), [restok]);
  assert.equal((await one('SELECT public.simbar_permintaan_mark_read() AS n')).n, 1);
  assert.equal((await one('SELECT public.simbar_permintaan_notifications() AS n')).n.length, 0);
  await assert.rejects(db.query('SELECT public.simbar_permintaan_cancel($1)', [restokId]), /tidak dapat dibatalkan/);

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
