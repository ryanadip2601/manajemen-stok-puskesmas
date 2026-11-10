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
  const operations = ['ops-core.sql', 'ops-stock.sql', 'ops-pickup.sql', 'ops-permintaan.sql', 'ops-usulan.sql', 'ops-media.sql', 'ops-aset.sql', 'ops-pemeliharaan.sql'];
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

  // Usulan penerimaan: revision round trip, approval creates the barang aset with the next kode.
  const room = await one('SELECT id FROM simbar.ruangan WHERE is_active ORDER BY id LIMIT 1');
  const kategori = await one('SELECT id, upper(left(nama_kategori, 3)) AS prefix FROM simbar.kategori_aset WHERE is_active ORDER BY id LIMIT 1');
  const form = { ruangan_id: room.id, nama_barang: 'Kursi Uji', merk: 'Uji', jumlah: 3, kondisi_barang: 'bekas_rusak_ringan',
    kelengkapan: 'lengkap', estimasi_harga: 150000, sumber_barang: 'Hibah', prioritas: 'sedang', photos: ['https://example.com/kursi.jpg'] };
  await as(STAFF);
  await assert.rejects(db.query('SELECT public.simbar_usulan_save(NULL, $1)', [JSON.stringify({ ...form, photos: ['javascript:alert(1)'] })]), /link foto/);
  const usulanCode = (await one('SELECT public.simbar_usulan_save(NULL, $1) AS c', [JSON.stringify(form)])).c;
  assert.match(usulanCode, /^USL-\d{6}-\d{4}$/);
  await assert.rejects(db.query('SELECT public.simbar_usulan_save(NULL, $1)', [JSON.stringify(form)]), /usulan pending/);
  await assert.rejects(db.query(`SELECT public.simbar_usulan_list(false, 'all', NULL, '', 1)`), /Akses ditolak/);
  const usulanId = (await one(`SELECT public.simbar_usulan_list(true, 'pending', NULL, 'Kursi Uji', 1) AS l`)).l.rows[0].id;
  await as(ADMIN);
  await db.query(`SELECT public.simbar_usulan_act($1, 'revision', 'Lengkapi merk')`, [usulanId]);
  await as(STAFF);
  await db.query('SELECT public.simbar_usulan_save($1, $2)', [usulanId, JSON.stringify({ ...form, merk: 'Merk Uji' })]);
  await as(ADMIN);
  await assert.rejects(db.query(`SELECT public.simbar_usulan_act($1, 'approve', NULL, NULL)`, [usulanId]), /Kategori aset wajib/);
  const asetCode = (await one(`SELECT public.simbar_usulan_act($1, 'approve', 'OK', $2) AS c`, [usulanId, kategori.id])).c;
  assert.ok(asetCode.startsWith(kategori.prefix + '-'), asetCode);
  const aset = await one('SELECT a.kondisi, a.jumlah, a.merk, a.foto, a.sumber_dana FROM simbar.barang_aset a WHERE kode_aset = $1', [asetCode]);
  assert.deepEqual(aset, { kondisi: 'rusak_ringan', jumlah: 3, merk: 'Merk Uji', foto: 'https://example.com/kursi.jpg', sumber_dana: 'Hibah' });
  assert.equal((await one(`SELECT public.simbar_usulan_get($1) AS g`, [usulanId])).g.aset.code, asetCode);
  await assert.rejects(db.query(`SELECT public.simbar_usulan_act($1, 'reject', 'x')`, [usulanId]), /sudah diverifikasi/);
  await as(STAFF);
  await assert.rejects(db.query('SELECT public.simbar_usulan_cancel($1)', [usulanId]), /tidak dapat dibatalkan/);

  // Uploaded photos: only the uploader may attach a fresh "media:" reference; visibility follows the record.
  const hex = (n) => n.toString(16).padStart(64, '0');
  await as(STAFF);
  const staffPhoto = (await one(`SELECT public.simbar_media_register($1, $2, 1, 1000, $3) AS r`,
    ['simbar-uploads/' + hex(1), 'a'.repeat(32), 'b'.repeat(64)])).r;
  assert.equal(staffPhoto, 'media:' + hex(1));
  await assert.rejects(db.query(`SELECT public.simbar_media_register('simbar-hostinger/' || $1, $2, 1, 1000, $3)`,
    [hex(2), 'a'.repeat(32), 'b'.repeat(64)]), /tidak valid/);
  assert.ok((await one('SELECT public.simbar_media_lookup($1) AS m', [staffPhoto])).m.publicId.endsWith(hex(1)));
  await as(ADMIN);
  assert.equal((await one('SELECT public.simbar_media_lookup($1) AS m', [staffPhoto])).m, null);
  const adminPhoto = (await one(`SELECT public.simbar_media_register($1, $2, 1, 2000, $3) AS r`,
    ['simbar-uploads/' + hex(3), 'c'.repeat(32), 'd'.repeat(64)])).r;

  // Barang aset, ruangan and kategori aset.
  const kategoriAset = (await one(`SELECT public.simbar_kategori_aset_save(NULL, $1) AS id`,
    [JSON.stringify({ nama_kategori: 'Zuji Alat', golongan: 'peralatan_mesin', umur_manfaat: 5 })])).id;
  const ruang = (await one(`SELECT public.simbar_ruangan_save(NULL, 'Ruang Uji', 'Lantai 1', NULL, true) AS id`)).id;
  const ruang2 = (await one(`SELECT public.simbar_ruangan_save(NULL, 'Ruang Uji 2', NULL, NULL, true) AS id`)).id;
  await assert.rejects(db.query(`SELECT public.simbar_ruangan_save(NULL, 'Ruang Uji', NULL, NULL, true)`), /sudah ada/);
  const nextCode = (await one('SELECT public.simbar_aset_next_code($1) AS c', [kategoriAset])).c;
  assert.match(nextCode, /^ZUJ-\d{4}-0001$/);
  const asetForm = { kode_aset: nextCode, nama_aset: 'Tensimeter Uji', kategori_aset_id: kategoriAset, ruangan_id: ruang,
    kondisi: 'rusak_berat', tanggal_masuk: '2025-03-01', jumlah: 1, harga_perolehan: 500000, foto: staffPhoto };
  await assert.rejects(db.query('SELECT public.simbar_aset_save(NULL, $1)', [JSON.stringify(asetForm)]), /Unggah ulang/);
  const asetId = (await one('SELECT public.simbar_aset_save(NULL, $1) AS id', [JSON.stringify({ ...asetForm, foto: adminPhoto })])).id;
  const savedAset = await one('SELECT tahun_perolehan, butuh_perbaikan, foto FROM simbar.barang_aset WHERE id = $1', [asetId]);
  assert.deepEqual(savedAset, { tahun_perolehan: 2025, butuh_perbaikan: true, foto: adminPhoto });
  await assert.rejects(db.query('SELECT public.simbar_aset_save(NULL, $1)', [JSON.stringify({ ...asetForm, foto: null })]), /Kode aset sudah ada/);
  await db.query('SELECT public.simbar_aset_save($1, $2)', [asetId, JSON.stringify({ ...asetForm, kondisi: 'baik', foto: undefined })]);
  assert.equal((await one('SELECT foto FROM simbar.barang_aset WHERE id = $1', [asetId])).foto, adminPhoto, 'omitted foto keeps the photo');
  const moved = (await one('SELECT public.simbar_aset_move($1, $2) AS m', [[asetId], ruang2])).m;
  assert.deepEqual([moved.moved, moved.skipped], [1, 0]);
  await assert.rejects(db.query('SELECT public.simbar_ruangan_delete($1)', [ruang2]), /masih memiliki aset/);
  await assert.rejects(db.query('SELECT public.simbar_kategori_aset_delete($1)', [kategoriAset]), /masih memiliki aset/);
  const asetList = (await one(`SELECT public.simbar_aset_list($1, 1) AS l`, [JSON.stringify({ search: 'Tensimeter Uji' })])).l;
  assert.equal(asetList.total, 1);

  // Pemeliharaan: staff reports (aset flagged for repair), warehouse finishes (aset back to baik).
  await as(STAFF);
  assert.ok((await one('SELECT public.simbar_aset_get($1) AS a', [asetId])).a.kode_aset === nextCode);
  await assert.rejects(db.query('SELECT public.simbar_aset_save($1, $2)', [asetId, JSON.stringify(asetForm)]), /Akses ditolak/);
  const repair = (await one('SELECT public.simbar_pemeliharaan_save(NULL, $1) AS id', [JSON.stringify({ barang_aset_id: asetId,
    ruangan_id: ruang2, tanggal_pemeliharaan: '2026-11-10', jenis_pemeliharaan: 'perbaikan', deskripsi_masalah: 'Selang bocor',
    teknisi: 'Teknisi Uji', lampiran_foto: staffPhoto })])).id;
  assert.equal((await one('SELECT butuh_perbaikan FROM simbar.barang_aset WHERE id = $1', [asetId])).butuh_perbaikan, true);
  assert.equal((await one(`SELECT public.simbar_pemeliharaan_list('{}', 1) AS l`)).l.counts.total, 1, 'staff see only their reports');
  await assert.rejects(db.query(`SELECT public.simbar_pemeliharaan_status($1, 'selesai')`, [repair]), /Akses ditolak/);
  await as(ADMIN);
  assert.equal((await one('SELECT public.simbar_media_lookup($1) AS m', [staffPhoto])).m.publicId.endsWith(hex(1)), true,
    'warehouse sees photos attached to maintenance');
  await db.query(`SELECT public.simbar_pemeliharaan_status($1, 'rusak')`, [repair]);
  assert.equal((await one('SELECT kondisi FROM simbar.barang_aset WHERE id = $1', [asetId])).kondisi, 'rusak_berat');
  await db.query(`SELECT public.simbar_pemeliharaan_status($1, 'selesai', 'Selang diganti')`, [repair]);
  const fixed = await one(`SELECT a.kondisi, a.butuh_perbaikan, p.tanggal_selesai IS NOT NULL AS done, p.tindakan_perbaikan
    FROM simbar.pemeliharaan_aset p JOIN simbar.barang_aset a ON a.id = p.barang_aset_id WHERE p.id = $1`, [repair]);
  assert.deepEqual(fixed, { kondisi: 'baik', butuh_perbaikan: false, done: true, tindakan_perbaikan: 'Selang diganti' });
  await db.query('SELECT public.simbar_pemeliharaan_delete($1)', [repair]);
  assert.equal((await one('SELECT public.simbar_aset_delete($1) AS n', [[asetId]])).n, 1);
  await db.query('SELECT public.simbar_ruangan_delete($1)', [ruang]);
  await db.query('SELECT public.simbar_kategori_aset_delete($1)', [kategoriAset]);

  // Permintaan accepts an uploaded photo of its owner.
  await as(STAFF);
  const ownPhoto = (await one(`SELECT public.simbar_media_register($1, $2, 1, 1000, $3) AS r`,
    ['simbar-uploads/' + hex(4), 'e'.repeat(32), 'f'.repeat(64)])).r;
  const withPhoto = (await one(`SELECT public.simbar_permintaan_save(NULL, 'barang_baru', NULL, 'Barang Foto Uji', NULL, 1, 'rendah', NULL, $1) AS c`, [ownPhoto])).c;
  const photoRow = await one('SELECT tipe_foto_barang, foto_barang FROM simbar.permintaan_barang WHERE kode_permintaan = $1', [withPhoto]);
  assert.deepEqual(photoRow, { tipe_foto_barang: 'upload', foto_barang: ownPhoto });
  await as(ADMIN);

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
