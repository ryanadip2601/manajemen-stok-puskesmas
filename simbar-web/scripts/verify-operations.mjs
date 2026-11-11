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
    CREATE FUNCTION auth.uid() RETURNS uuid LANGUAGE sql STABLE AS $$ SELECT nullif(current_setting('test.uid', true), '')::uuid $$;
    CREATE TABLE auth.users (id uuid PRIMARY KEY, email text, raw_user_meta_data jsonb, encrypted_password text, updated_at timestamptz);
    CREATE SCHEMA extensions; CREATE FUNCTION extensions.gen_salt(text, integer) RETURNS text LANGUAGE sql AS $$ SELECT 'salt' $$;
    CREATE FUNCTION extensions.crypt(text, text) RETURNS text LANGUAGE sql AS $$ SELECT 'hash:' || $1 $$;
    CREATE SCHEMA net; CREATE TABLE net.calls (url text, body jsonb, headers jsonb);
    CREATE FUNCTION net.http_post(url text, body jsonb, params jsonb DEFAULT '{}', headers jsonb DEFAULT '{}', timeout_milliseconds integer DEFAULT 5000)
      RETURNS bigint LANGUAGE sql AS $$ INSERT INTO net.calls VALUES (url, body, headers); SELECT 1::bigint $$;`);
  await db.exec(await read('schema.sql'));
  await db.exec(data);
  const operations = ['ops-core.sql', 'ops-stock.sql', 'ops-pickup.sql', 'ops-permintaan.sql', 'ops-usulan.sql', 'ops-media.sql', 'ops-aset.sql', 'ops-pemeliharaan.sql', 'ops-penyusutan.sql', 'ops-laporan.sql', 'ops-users.sql', 'ops-public.sql', 'ops-push.sql'];
  for (const name of ['media-objects.sql', ...operations]) await db.exec(await read(name));
  for (const name of operations) await db.exec(await read(name)); // re-runnable

  const admin = await one(`UPDATE simbar.users SET auth_user_id = $1, is_approved = true, role = 'admin'
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

  // Push notifications: both people get a device; the stubbed net.http_post records what would be sent.
  await db.exec(`INSERT INTO simbar.push_settings VALUES ('notify_url', 'https://simbar.example/api/notify'), ('notify_secret', 'uji-rahasia')`);
  const device = (name) => [`https://push.example/${name}`, 'B'.repeat(87), 'authkey-uji-123', 'uji'];
  await as(ADMIN);
  await db.query('SELECT public.simbar_push_subscribe($1, $2, $3, $4)', device('admin'));
  await as(STAFF);
  await db.query('SELECT public.simbar_push_subscribe($1, $2, $3, $4)', device('staf'));
  const pushes = async () => (await db.query('DELETE FROM net.calls RETURNING body, headers')).rows;

  // Pengambilan barang: staff submits, warehouse approves (stock drops) or rejects.
  const stocked = (await one('SELECT id, stock FROM simbar.items WHERE stock >= 3 ORDER BY id LIMIT 1'));
  const other = (await one('SELECT id FROM simbar.items WHERE stock >= 1 AND id <> $1 ORDER BY id LIMIT 1', [stocked.id]));
  await as(STAFF);
  await pushes();
  assert.ok((await one('SELECT public.simbar_pickup_options() AS o')).o.every((row) => row.stock > 0));
  await assert.rejects(db.query('SELECT public.simbar_pickup_list(false, $1, 1)', ['all']), /Akses ditolak/);
  await assert.rejects(db.query('SELECT public.simbar_pickup_submit($1)',
    [JSON.stringify([{ item_id: stocked.id, quantity: 1 }, { item_id: stocked.id, quantity: 1 }])]), /satu kali/);
  await assert.rejects(db.query('SELECT public.simbar_pickup_submit($1)',
    [JSON.stringify([{ item_id: stocked.id, quantity: stocked.stock + 1 }])]), /melebihi stok/);
  assert.equal((await one('SELECT public.simbar_pickup_submit($1) AS n',
    [JSON.stringify([{ item_id: stocked.id, quantity: 2, notes: 'uji' }, { item_id: other.id, quantity: 1 }])])).n, 2);
  // Two items in one submission: one merged notification, only to the warehouse device, with the secret.
  const sent = await pushes();
  assert.equal(sent.length, 1, 'one notification per recipient per transaction');
  assert.equal(sent[0].headers.Authorization, 'Bearer uji-rahasia');
  assert.deepEqual(sent[0].body.messages.map((m) => m.subscription.endpoint), ['https://push.example/admin']);
  assert.equal(sent[0].body.messages[0].payload.title, 'Pengambilan barang baru');
  assert.equal(sent[0].body.messages[0].payload.body, 'Staf Uji mengajukan pengambilan 2 barang.');
  assert.equal(sent[0].body.messages[0].payload.url, '/?menu=checklist');
  assert.ok(sent[0].body.messages[0].payload.badge >= 2);
  const mine = (await one(`SELECT public.simbar_pickup_list(true, 'pending', 1) AS l`)).l;
  assert.equal(mine.total, 2);
  const [second, first] = mine.rows.map((row) => row.id).sort((a, b) => b - a);
  await db.query('SELECT public.simbar_pickup_cancel($1)', [second]);
  assert.equal((await pushes()).length, 0, 'cancelling your own request notifies nobody');
  await assert.rejects(db.query('SELECT public.simbar_pickup_cancel($1)', [second]), /Menunggu/);

  await as(ADMIN);
  await assert.rejects(db.query('SELECT public.simbar_pickup_cancel($1)', [first]), /tidak ditemukan/);
  const checklist = (await one(`SELECT public.simbar_pickup_list(false, 'pending', 1) AS l`)).l;
  assert.ok(checklist.counts.pending >= 1 && checklist.rows.some((row) => row.id === first));
  await db.query(`SELECT public.simbar_checklist_act($1, 'approve', NULL)`, [first]);
  // The requester hears about the approval; the admin who approved does not.
  const approval = await pushes();
  assert.equal(approval.length, 1);
  assert.deepEqual(approval[0].body.messages.map((m) => m.subscription.endpoint), ['https://push.example/staf']);
  assert.equal(approval[0].body.messages[0].payload.title, 'Pengambilan barang disetujui');
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

  // Penyusutan: recompute every period Laravel stored and compare (assets edited since then are reported separately).
  const parity = await one(`
    SELECT count(*)::int AS stored,
      count(*) FILTER (WHERE (h->>'nilai_perolehan')::numeric = p.nilai_perolehan AND (h->>'umur_manfaat')::int = p.umur_manfaat)::int AS comparable,
      count(*) FILTER (WHERE (h->>'nilai_perolehan')::numeric = p.nilai_perolehan AND (h->>'umur_manfaat')::int = p.umur_manfaat
        AND (abs((h->>'akumulasi_penyusutan')::numeric - p.akumulasi_penyusutan_akhir) > 0.01
          OR abs((h->>'nilai_buku')::numeric - p.nilai_buku_akhir) > 0.01
          OR abs((h->>'penyusutan_per_tahun')::numeric - p.penyusutan_per_tahun) > 0.01))::int AS mismatched
      ,count(*) FILTER (WHERE NOT ((h->>'nilai_perolehan')::numeric = p.nilai_perolehan AND (h->>'umur_manfaat')::int = p.umur_manfaat)
        AND NOT (a.updated_at > p.updated_at OR k.updated_at > p.updated_at))::int AS unexplained
    FROM simbar.penyusutan_aset p JOIN simbar.barang_aset a ON a.id = p.barang_aset_id JOIN simbar.kategori_aset k ON k.id = a.kategori_aset_id
    CROSS JOIN LATERAL (SELECT simbar.hitung_penyusutan(p.barang_aset_id, p.tanggal_hitung) AS h) x`);
  assert.equal(parity.unexplained, 0, 'Periods differ without a later asset/kategori edit: ' + JSON.stringify(parity));
  assert.equal(parity.mismatched, 0, 'Depreciation differs from Laravel: ' + JSON.stringify(parity));
  await as(STAFF);
  await assert.rejects(db.query('SELECT public.simbar_penyusutan_summary(2026)'), /Akses ditolak/);
  await as(ADMIN);
  const before = (await one('SELECT public.simbar_penyusutan_summary(2026) AS s')).s.stored;
  await assert.rejects(db.query('SELECT public.simbar_penyusutan_proses(1999)'), /tidak valid/);
  const run = (await one('SELECT public.simbar_penyusutan_proses(2026) AS r')).r;
  assert.ok(run.aset_diproses > 0 && run.aset_diproses + run.aset_skip === run.total_aset, JSON.stringify(run));
  const after = (await one('SELECT public.simbar_penyusutan_summary(2026) AS s')).s.stored;
  // Like Laravel, rows of assets that are no longer active stay; every processed asset has exactly one row.
  assert.ok(Number(after.total_aset) >= run.aset_diproses);
  assert.equal((await one(`SELECT count(*)::int AS n FROM (SELECT barang_aset_id FROM simbar.penyusutan_aset
    WHERE tahun = 2026 AND bulan IS NULL GROUP BY 1 HAVING count(*) > 1) d`)).n, 0, 'no duplicate periods');
  await db.query('SELECT public.simbar_penyusutan_proses(2026)');
  assert.equal(Number((await one('SELECT public.simbar_penyusutan_summary(2026) AS s')).s.stored.total_aset), Number(after.total_aset),
    're-running the year does not add rows');
  const detail = (await one(`SELECT public.simbar_penyusutan_detail('{}', '2026-12-31') AS d`)).d;
  assert.equal(detail.length, run.total_aset);
  const kartu = (await one('SELECT public.simbar_penyusutan_kartu($1) AS k', [detail.find((row) => row.dapat_disusutkan).id])).k;
  assert.ok(kartu.riwayat.some((entry) => entry.tahun === 2026));
  assert.equal((await one('SELECT jsonb_array_length(public.simbar_penyusutan_export(2026)) AS n')).n, Number(after.total_aset));
  console.log(`Penyusutan matches Laravel for ${parity.comparable}/${parity.stored} stored periods (${parity.stored - parity.comparable} recalculated because the asset or kategori was edited later); stored 2026 before re-run: ${before.total_aset}.`);

  // Laporan (admin only) and dashboard.
  const masuk = (await one(`SELECT public.simbar_laporan('barang_masuk', '{}', 0) AS l`)).l;
  const masukSum = await one('SELECT count(*)::int AS n, coalesce(sum(quantity), 0)::int AS q FROM simbar.stock_in');
  assert.deepEqual([masuk.total, Number(masuk.stats.total_qty), masuk.rows.length], [masukSum.n, masukSum.q, masukSum.n]);
  const keluar2026 = (await one(`SELECT public.simbar_laporan('barang_keluar', '{"dari":"2026-01-01","sampai":"2026-12-31"}', 1) AS l`)).l;
  assert.equal(keluar2026.total, (await one(`SELECT count(*)::int AS n FROM simbar.stock_out WHERE date BETWEEN '2026-01-01' AND '2026-12-31'`)).n);
  assert.ok(keluar2026.rows.length <= 15);
  for (const kind of ['pengambilan', 'permintaan_bhp', 'usulan_aset']) {
    assert.ok((await one(`SELECT public.simbar_laporan($1, '{}', 1) AS l`, [kind])).l.total >= 0);
  }
  const periode = (await one(`SELECT public.simbar_laporan_periode('2026-01-01', '2026-03-31', NULL) AS p`)).p;
  assert.equal(Number(periode.summary.total_stock), (await one('SELECT sum(stock)::int AS s FROM simbar.items')).s);
  const tahunan = (await one('SELECT public.simbar_laporan_tahunan(2026) AS t')).t;
  assert.equal(tahunan.months.length, 12);
  assert.equal(tahunan.months.reduce((sum, month) => sum + Number(month.stock_in), 0),
    (await one(`SELECT coalesce(sum(quantity), 0)::int AS s FROM simbar.stock_in WHERE extract(year FROM date) = 2026`)).s);
  const board = (await one('SELECT public.simbar_dashboard(90) AS d')).d;
  assert.ok(board.monthly.length === 6 && board.queues && board.funnel && board.aging);
  await as(STAFF);
  await assert.rejects(db.query(`SELECT public.simbar_laporan('barang_masuk', '{}', 1)`), /Akses ditolak/);
  const staffBoard = (await one('SELECT public.simbar_dashboard(30) AS d')).d;
  assert.ok(!('queues' in staffBoard) && staffBoard.stats.total_items > 0);
  await as(ADMIN);
  await db.query(`UPDATE simbar.users SET role = 'admin_gudang' WHERE id = $1`, [admin.id]);
  await assert.rejects(db.query(`SELECT public.simbar_laporan('barang_masuk', '{}', 1)`), /Akses ditolak/, 'laporan is admin only');
  await db.query(`UPDATE simbar.users SET role = 'admin' WHERE id = $1`, [admin.id]);

  // Accounts: self registration stays pending; legacy emails are never auto-linked; admin manages access.
  const NEWBIE = '00000000-0000-4000-8000-000000000003';
  await db.query(`INSERT INTO auth.users (id, email, raw_user_meta_data) VALUES ($1, 'Baru.Uji@Example.invalid', '{"name":"Pendaftar Uji","phone":"0812"}')`, [NEWBIE]);
  const newbie = await one(`SELECT id, role, is_approved, email FROM simbar.users WHERE auth_user_id = $1`, [NEWBIE]);
  assert.deepEqual([newbie.role, newbie.is_approved, newbie.email], ['staff', false, 'baru.uji@example.invalid']);
  const legacyEmail = (await one('SELECT email FROM simbar.users WHERE auth_user_id IS NULL AND id > 0 ORDER BY id LIMIT 1')).email;
  await db.query(`INSERT INTO auth.users (id, email) VALUES ('00000000-0000-4000-8000-000000000004', $1)`, [legacyEmail.toUpperCase()]);
  assert.equal((await one('SELECT count(*)::int AS n FROM simbar.users WHERE auth_user_id = $1', ['00000000-0000-4000-8000-000000000004'])).n, 0,
    'registering with a legacy email must not take over that profile');
  await as(NEWBIE);
  assert.deepEqual((await one('SELECT public.simbar_my_status() AS s')).s, { linked: true, approved: false, id: Number(newbie.id), name: 'Pendaftar Uji', role: 'staff' });
  await assert.rejects(db.query(`SELECT public.simbar_dashboard(30)`), /not approved or linked/);

  // The public (signed-out) dashboard: charts and item names only, never people or queues.
  await as('');
  const open = (await one('SELECT public.simbar_public_dashboard() AS d')).d;
  assert.equal(open.stats.total_stock, staffBoard.stats.total_stock);
  assert.deepEqual(Object.keys(open).sort(), ['aging', 'categories', 'low_stock_items', 'monthly', 'pareto', 'range_days', 'stats']);
  // Unit accounts such as "Laboratorium" share words with item and category names, so those do not count.
  const names = (await db.query(`SELECT u.name FROM simbar.users u WHERE length(u.name) > 4
    AND NOT EXISTS (SELECT 1 FROM simbar.items i WHERE i.name ILIKE '%' || u.name || '%')
    AND NOT EXISTS (SELECT 1 FROM simbar.categories c WHERE c.name ILIKE '%' || u.name || '%')`)).rows.map((row) => row.name);
  const text = JSON.stringify(open);
  const leaked = names.filter((name) => text.includes(name));
  assert.deepEqual(leaked, [], 'the public dashboard must not contain any user name');
  await as(STAFF);
  await assert.rejects(db.query(`SELECT public.simbar_users_list('all', NULL, '', 1)`), /Akses ditolak/);
  await db.query(`SELECT public.simbar_session_event('login', 'Mozilla/5.0 (Linux; Android 14) Chrome/130 Mobile')`);
  const history = await one(`SELECT device, browser, platform FROM simbar.login_histories ORDER BY id DESC LIMIT 1`);
  assert.deepEqual(history, { device: 'Mobile', browser: 'Chrome', platform: 'Android' });
  await db.query(`SELECT public.simbar_profile_update('Staf Uji Baru', '0813')`);
  assert.equal((await one('SELECT public.simbar_profile_get() AS p')).p.name, 'Staf Uji Baru');
  await as(ADMIN);
  const pendingUsers = (await one(`SELECT public.simbar_users_list('pending', NULL, 'Pendaftar', 1) AS l`)).l;
  assert.equal(pendingUsers.total, 1);
  await db.query(`SELECT public.simbar_user_act($1, 'approve', 'admin_gudang')`, [newbie.id]);
  await as(NEWBIE);
  assert.equal((await one('SELECT public.simbar_my_status() AS s')).s.role, 'admin_gudang');
  await as(ADMIN);
  await assert.rejects(db.query(`SELECT public.simbar_user_act($1, 'role', 'staff')`, [admin.id]), /akun sendiri/);
  await assert.rejects(db.query(`SELECT public.simbar_user_act($1, 'reject')`, [newbie.id]), /sudah disetujui/);
  await db.query(`SELECT public.simbar_user_set_password($1, 'passwordbaru123')`, [newbie.id]);
  assert.equal((await one('SELECT encrypted_password FROM auth.users WHERE id = $1', [NEWBIE])).encrypted_password, 'hash:passwordbaru123');
  await assert.rejects(db.query(`SELECT public.simbar_user_set_password($1, 'short')`, [newbie.id]), /minimal 8/);
  const busyUser = (await one('SELECT user_id AS id FROM simbar.stock_in WHERE user_id <> $1 LIMIT 1', [admin.id])).id;
  await assert.rejects(db.query(`SELECT public.simbar_user_act($1, 'delete')`, [busyUser]), /transaksi stok/);
  await db.query(`SELECT public.simbar_user_act($1, 'delete')`, [newbie.id]);
  assert.equal((await one('SELECT count(*)::int AS n FROM auth.users WHERE id = $1', [NEWBIE])).n, 0, 'deleting a user removes the login');
  assert.ok((await one(`SELECT public.simbar_user_detail($1) AS d`, [admin.id])).d.stats.total_activities > 0);
  assert.ok((await one(`SELECT public.simbar_login_history('{}', 1) AS h`)).h.stats.logins >= 1);
  assert.ok((await one(`SELECT public.simbar_activity_logs('{"action":"user_approved"}', 1) AS l`)).l.total >= 1);
  assert.ok((await one(`SELECT public.simbar_logs_clear('login_history') AS n`)).n >= 1);
  assert.equal((await one('SELECT count(*)::int AS n FROM simbar.login_histories')).n, 0);

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
