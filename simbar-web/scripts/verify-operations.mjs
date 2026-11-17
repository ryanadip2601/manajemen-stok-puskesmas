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
  const operations = ['ops-core.sql', 'ops-menus.sql', 'ops-stock.sql', 'ops-pickup.sql', 'ops-permintaan.sql', 'ops-usulan.sql', 'ops-pengajuan.sql', 'ops-media.sql', 'ops-aset.sql', 'ops-pemeliharaan.sql', 'ops-penyusutan.sql', 'ops-laporan.sql', 'ops-users.sql', 'ops-public.sql', 'ops-push.sql', 'ops-verifikasi.sql', 'ops-laporan-baru.sql', 'ops-rapikan.sql', 'ops-foto-barang.sql', 'ops-foto-aset.sql', 'ops-mutasi-aset.sql', 'ops-terima-batch.sql', 'ops-cetak.sql'];
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

  // Kalibrasi: needs a layak / tidak layak result, is recorded as selesai, never changes the aset condition,
  // and the latest one (valid one year) shows in the aset list with its own filter.
  const kalForm = { barang_aset_id: asetId, ruangan_id: ruang2, jenis_pemeliharaan: 'kalibrasi', deskripsi_masalah: 'Kalibrasi tahunan',
    teknisi: 'Lembaga Uji' };
  const days = async (n) => (await one(`SELECT (simbar.local_today() + $1::integer)::text AS d`, [n])).d;
  await db.query(`UPDATE simbar.barang_aset SET kondisi = 'rusak_ringan' WHERE id = $1`, [asetId]);
  await assert.rejects(db.query('SELECT public.simbar_pemeliharaan_save(NULL, $1)', [JSON.stringify({ ...kalForm,
    tanggal_pemeliharaan: await days(-400) })]), /Hasil kalibrasi harus dipilih/);
  const oldKal = (await one('SELECT public.simbar_pemeliharaan_save(NULL, $1) AS id', [JSON.stringify({ ...kalForm,
    tanggal_pemeliharaan: await days(-400), hasil_kalibrasi: 'layak' })])).id;
  const oldRow = await one(`SELECT p.status, p.hasil_kalibrasi, p.tanggal_selesai = p.tanggal_pemeliharaan AS same, a.kondisi
    FROM simbar.pemeliharaan_aset p JOIN simbar.barang_aset a ON a.id = p.barang_aset_id WHERE p.id = $1`, [oldKal]);
  assert.deepEqual(oldRow, { status: 'selesai', hasil_kalibrasi: 'layak', same: true, kondisi: 'rusak_ringan' });
  const kalRow = async (filter = {}) => (await one(`SELECT public.simbar_aset_list($1, 1) AS l`,
    [JSON.stringify({ search: 'Tensimeter Uji', ...filter })])).l;
  assert.equal((await kalRow()).rows[0].kalibrasi_status, 'lewat');
  assert.equal((await kalRow({ kalibrasi: 'perlu' })).total, 1);
  const newKal = (await one('SELECT public.simbar_pemeliharaan_save(NULL, $1) AS id', [JSON.stringify({ ...kalForm,
    tanggal_pemeliharaan: await days(-1), hasil_kalibrasi: 'tidak_layak' })])).id;
  const latest = (await kalRow()).rows[0];
  assert.deepEqual([latest.kalibrasi_status, latest.kalibrasi_hasil, latest.kalibrasi_tanggal], ['berlaku', 'tidak_layak', await days(-1)]);
  assert.equal((await kalRow({ kalibrasi: 'perlu' })).total, 0);
  assert.equal((await kalRow({ kalibrasi: 'berlaku' })).total, 1);
  await db.query(`SELECT public.simbar_pemeliharaan_status($1, 'rusak')`, [newKal]);
  assert.equal((await one('SELECT kondisi FROM simbar.barang_aset WHERE id = $1', [asetId])).kondisi, 'rusak_ringan',
    'kalibrasi never changes the aset condition');
  await db.query('SELECT public.simbar_pemeliharaan_save($1, $2)', [newKal, JSON.stringify({ ...kalForm, tanggal_pemeliharaan: await days(-1),
    hasil_kalibrasi: 'layak', status: 'selesai' })]);
  assert.equal((await one('SELECT kondisi FROM simbar.barang_aset WHERE id = $1', [asetId])).kondisi, 'rusak_ringan');
  assert.equal((await one('SELECT public.simbar_aset_get($1) AS a', [asetId])).a.pemeliharaan[0].hasil, 'layak');
  const perbaikan = (await one('SELECT public.simbar_pemeliharaan_save(NULL, $1) AS id', [JSON.stringify({ ...kalForm,
    jenis_pemeliharaan: 'perbaikan', tanggal_pemeliharaan: await days(0), hasil_kalibrasi: 'layak' })])).id;
  assert.equal((await one('SELECT hasil_kalibrasi FROM simbar.pemeliharaan_aset WHERE id = $1', [perbaikan])).hasil_kalibrasi, null,
    'only kalibrasi keeps a result');
  // A label scanned without signing in shows the asset card: label data, condition, kalibrasi and maintenance dates only.
  await as('');
  await assert.rejects(db.query('SELECT public.simbar_aset_get($1)', [asetId]), /not approved or linked/);
  const card = (await one('SELECT public.simbar_aset_public($1) AS a', [asetId])).a;
  assert.deepEqual([card.kode_aset, card.nama_aset, card.ruangan, card.kondisi, card.kalibrasi_status, card.kalibrasi_hasil],
    [nextCode, 'Tensimeter Uji', 'Ruang Uji 2', 'rusak_ringan', 'berlaku', 'layak']);
  for (const key of ['harga_perolehan', 'nilai_residu', 'sumber_dana', 'keterangan', 'created_by', 'created_by_name']) {
    assert.ok(!(key in card), key + ' must not be on the public card');
  }
  // The card only says whether a photo exists; the picture itself is resolved by id, never by reference.
  assert.deepEqual([card.foto, card.contoh], [true, null]);
  assert.equal((await one('SELECT public.simbar_aset_public_foto($1) AS f', [asetId])).f.size, 2000, 'the public photo is the asset photo');
  assert.equal((await one('SELECT public.simbar_aset_public_foto(0) AS f')).f, null);
  assert.equal(card.pemeliharaan.length, 3);
  assert.deepEqual(Object.keys(card.pemeliharaan[0]).sort(), ['hasil', 'id', 'jenis', 'status', 'tanggal'], 'no teknisi or deskripsi on the public card');
  await assert.rejects(db.query('SELECT public.simbar_aset_public(0)'), /Aset tidak ditemukan/);
  await as(ADMIN);
  for (const id of [oldKal, newKal, perbaikan]) await db.query('SELECT public.simbar_pemeliharaan_delete($1)', [id]);

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
  // A backup without stock movements has no such user; the rest of the suite still runs.
  const busyUser = (await one('SELECT user_id AS id FROM simbar.stock_in WHERE user_id <> $1 LIMIT 1', [admin.id]))?.id;
  if (busyUser) await assert.rejects(db.query(`SELECT public.simbar_user_act($1, 'delete')`, [busyUser]), /transaksi stok/);
  await db.query(`SELECT public.simbar_user_act($1, 'delete')`, [newbie.id]);
  assert.equal((await one('SELECT count(*)::int AS n FROM auth.users WHERE id = $1', [NEWBIE])).n, 0, 'deleting a user removes the login');
  assert.ok((await one(`SELECT public.simbar_user_detail($1) AS d`, [admin.id])).d.stats.total_activities > 0);
  assert.ok((await one(`SELECT public.simbar_login_history('{}', 1) AS h`)).h.stats.logins >= 1);
  assert.ok((await one(`SELECT public.simbar_activity_logs('{"action":"user_approved"}', 1) AS l`)).l.total >= 1);
  assert.ok((await one(`SELECT public.simbar_logs_clear('login_history') AS n`)).n >= 1);
  assert.equal((await one('SELECT count(*)::int AS n FROM simbar.login_histories')).n, 0);

  // Configurable menus: the administrator hands menus to a role, and the menu unlocks its functions.
  await as(STAFF);
  const staffId = (await one('SELECT id FROM simbar.users WHERE auth_user_id = $1', [STAFF])).id;
  const defaults = (await one('SELECT public.simbar_role_menus() AS m')).m;
  assert.deepEqual(defaults.staff, ['aset', 'items', 'pemeliharaan', 'permintaan', 'pickup', 'usulan']);
  assert.equal(defaults.admin_gudang.length, 13);
  await assert.rejects(db.query(`SELECT public.simbar_role_menus_save('staff', ARRAY['in'])`), /Akses ditolak/);
  await assert.rejects(db.query('SELECT public.simbar_stock_options()'), /Akses ditolak/);
  await assert.rejects(db.query('SELECT public.simbar_penyusutan_summary(2026)'), /Akses ditolak/);
  await as(ADMIN);
  await assert.rejects(db.query(`SELECT public.simbar_role_menus_save('staff', ARRAY['users'])`), /tidak dikenal/);
  await assert.rejects(db.query(`SELECT public.simbar_role_menus_save('admin', ARRAY['in'])`), /Peran tidak valid/);
  await db.query(`SELECT public.simbar_role_menus_save('staff', ARRAY['items', 'in', 'checklist'])`);
  await as(STAFF);
  assert.ok((await one('SELECT public.simbar_stock_options() AS o')).o, 'Barang Masuk unlocks the stock options');
  await db.query(`SELECT public.simbar_stock_list('in', NULL, NULL, NULL, 1)`);
  await assert.rejects(db.query(`SELECT public.simbar_stock_list('out', NULL, NULL, NULL, 1)`), /Akses ditolak/);
  const menuItem = (await one('SELECT id, stock FROM simbar.items ORDER BY id LIMIT 1'));
  await db.query(`SELECT public.simbar_stock_save('in', NULL, $1, 2, '2026-11-11', 'uji menu')`, [menuItem.id]);
  assert.equal(await stockOf(menuItem.id), menuItem.stock + 2, 'staff with Barang Masuk records stock in');
  await assert.rejects(db.query(`SELECT public.simbar_stock_save('out', NULL, $1, 1, '2026-11-11', NULL)`, [menuItem.id]), /Akses ditolak/);
  await db.query('SELECT public.simbar_pickup_list(false, $1, 1)', ['all']);
  await db.query('SELECT public.simbar_pickup_submit($1)', [JSON.stringify([{ item_id: menuItem.id, quantity: 1, notes: 'uji menu' }])]);
  const menuPickup = (await one(`SELECT id FROM simbar.stock_out_requests WHERE item_id = $1 AND status = 'pending' ORDER BY id DESC LIMIT 1`, [menuItem.id])).id;
  await db.query(`SELECT public.simbar_checklist_act($1, 'approve')`, [menuPickup]);
  assert.equal(await stockOf(menuItem.id), menuItem.stock + 1, 'checklist staff approve a pickup through the checklist');
  assert.equal(await one(`SELECT current_setting('simbar.via', true) AS v`).then((r) => r.v ?? ''), '', 'the nested-call mark is cleared');
  await assert.rejects(db.query(`SELECT public.simbar_item_save(NULL, 'UJI-MENU', 'Uji menu', 1, 1, 'APBD', 1, 0)`), /Akses ditolak/);
  await assert.rejects(db.query('SELECT public.simbar_permintaan_list(false, $1, $2, $3, 1)', ['all', 'all', '']), /Akses ditolak/);
  assert.ok((await one('SELECT simbar.warehouse_people() AS p')).p.map(Number).includes(Number(staffId)), 'checklist staff get warehouse notifications');
  await as(ADMIN);
  await db.query(`SELECT public.simbar_role_menus_save('staff', $1)`, [defaults.staff]);
  await as(STAFF);
  await assert.rejects(db.query('SELECT public.simbar_stock_options()'), /Akses ditolak/);
  await assert.rejects(db.query('SELECT public.simbar_pickup_list(false, $1, 1)', ['all']), /Akses ditolak/);
  assert.ok(!(await one('SELECT simbar.warehouse_people() AS p')).p.map(Number).includes(Number(staffId)));
  await as(ADMIN);

  // Pengajuan saya: one list of the caller's own requests across the three kinds, with buckets.
  await as(STAFF);
  const mineAll = (await one(`SELECT public.simbar_my_requests('all', 'all', '', 1) AS r`)).r;
  assert.deepEqual(mineAll.kinds, ['pickup', 'permintaan', 'usulan', 'pemeliharaan']);
  assert.ok(mineAll.total > 0 && mineAll.rows.length <= 15);
  const ownCount = (await one(`SELECT
      (SELECT count(*) FROM simbar.stock_out_requests WHERE user_id = $1) + (SELECT count(*) FROM simbar.permintaan_barang WHERE user_id = $1)
      + (SELECT count(*) FROM simbar.usulan_penerimaan_barang WHERE user_id = $1) + (SELECT count(*) FROM simbar.pemeliharaan_aset WHERE created_by = $1) AS n`, [staffId])).n;
  assert.equal(Number(mineAll.total), Number(ownCount), 'every own request of every kind is listed');
  const buckets = ['menunggu', 'revisi', 'disetujui', 'ditolak', 'dibatalkan'];
  assert.equal(buckets.reduce((sum, b) => sum + Number(mineAll.counts[b]), 0), Number(mineAll.counts.all), 'every status lands in one bucket');
  const onlyPickup = (await one(`SELECT public.simbar_my_requests('pickup', 'all', '', 1) AS r`)).r;
  assert.ok(onlyPickup.rows.every((row) => row.kind === 'pickup'));
  const waiting = (await one(`SELECT public.simbar_my_requests('all', 'menunggu', '', 1) AS r`)).r;
  assert.ok(waiting.rows.every((row) => row.bucket === 'menunggu'));
  await assert.rejects(db.query(`SELECT public.simbar_my_requests('lain', 'all', '', 1)`), /tidak valid/);
  await as(ADMIN);
  await db.query(`SELECT public.simbar_role_menus_save('staff', $1)`, [defaults.staff.filter((m) => m !== 'usulan')]);
  await as(STAFF);
  const withoutUsulan = (await one(`SELECT public.simbar_my_requests('all', 'all', '', 1) AS r`)).r;
  assert.deepEqual(withoutUsulan.kinds, ['pickup', 'permintaan', 'pemeliharaan']);
  assert.ok(withoutUsulan.rows.every((row) => row.kind !== 'usulan'), 'kinds without the right are hidden');
  await as(ADMIN);
  await db.query(`SELECT public.simbar_role_menus_save('staff', $1)`, [defaults.staff]);

  // Verifikasi: one warehouse queue across the four kinds, waiting items oldest first, rights respected.
  await as(ADMIN);
  const queue = (await one(`SELECT public.simbar_queue('all', 'menunggu', '', 1) AS q`)).q;
  assert.deepEqual(queue.kinds, ['pickup', 'permintaan', 'usulan', 'pemeliharaan']);
  assert.ok(queue.rows.every((row) => row.bucket === 'menunggu'));
  const dates = queue.rows.map((row) => row.tanggal);
  assert.deepEqual(dates, [...dates].sort(), 'waiting items come oldest first');
  const waitingAll = (await one('SELECT public.simbar_queue_waiting() AS n')).n;
  assert.equal(Number(waitingAll), Number(queue.waiting.all), 'menu count matches the queue');
  assert.equal(['pickup', 'permintaan', 'usulan', 'pemeliharaan'].reduce((sum, k) => sum + Number(queue.waiting[k]), 0), Number(queue.waiting.all));
  const everything = (await one(`SELECT public.simbar_queue('all', 'all', '', 1) AS q`)).q;
  assert.equal(['menunggu', 'revisi', 'selesai', 'ditolak', 'dibatalkan'].reduce((sum, b) => sum + Number(everything.counts[b]), 0), Number(everything.counts.all));
  const usulanOnly = (await one(`SELECT public.simbar_queue('usulan', 'all', '', 1) AS q`)).q;
  assert.ok(usulanOnly.rows.every((row) => row.kind === 'usulan'));
  await assert.rejects(db.query(`SELECT public.simbar_queue('all', 'lain', '', 1)`), /tidak valid/);
  await as(STAFF);
  const staffQueue = (await one(`SELECT public.simbar_queue('all', 'menunggu', '', 1) AS q`)).q;
  assert.deepEqual(staffQueue.kinds, [], 'staff see no queue by default');
  assert.equal(Number(staffQueue.total), 0);
  assert.equal(Number((await one('SELECT public.simbar_queue_waiting() AS n')).n), 0);
  await as(ADMIN);
  await db.query(`SELECT public.simbar_role_menus_save('staff', $1)`, [[...defaults.staff, 'checklist']]);
  await as(STAFF);
  const staffChecklist = (await one(`SELECT public.simbar_queue('all', 'all', '', 1) AS q`)).q;
  assert.deepEqual(staffChecklist.kinds, ['pickup'], 'a right adds its kind to the queue');
  assert.ok(staffChecklist.rows.every((row) => row.kind === 'pickup'));
  await as(ADMIN);
  await db.query(`SELECT public.simbar_role_menus_save('staff', $1)`, [defaults.staff]);

  // Laporan baru: persediaan saldo per period, mutasi and pengajuan combined, kartu stok with running saldo.
  await as(ADMIN);
  const today = (await one('SELECT simbar.local_today()::text AS d')).d;
  const nowStock = (await one('SELECT coalesce(sum(stock), 0)::bigint AS s FROM simbar.items')).s;
  const upToday = (await one(`SELECT public.simbar_laporan_persediaan('2000-01-01', $1::date, NULL, '') AS r`, [today])).r;
  assert.equal(Number(upToday.summary.saldo_akhir), Number(nowStock), 'saldo akhir up to today equals the stock now');
  for (const row of upToday.rows) {
    assert.equal(Number(row.saldo_awal) + Number(row.masuk) - Number(row.keluar), Number(row.saldo_akhir), 'saldo awal + masuk - keluar = saldo akhir');
  }
  const busiest = (await one(`SELECT item_id AS id, min(date)::text AS first FROM simbar.stock_out GROUP BY item_id ORDER BY count(*) DESC LIMIT 1`));
  const jan = (await one(`SELECT public.simbar_laporan_persediaan('2025-01-01', '2025-12-31', NULL, '') AS r`)).r;
  const feb = (await one(`SELECT public.simbar_laporan_persediaan('2026-01-01', $1::date, NULL, '') AS r`, [today])).r;
  const janRow = jan.rows.find((row) => Number(row.id) === Number(busiest.id));
  const febRow = feb.rows.find((row) => Number(row.id) === Number(busiest.id));
  if (janRow && febRow) assert.equal(Number(janRow.saldo_akhir), Number(febRow.saldo_awal), 'one period ends where the next begins');
  const kartuStok = (await one(`SELECT public.simbar_kartu_stok($1, '2026-01-01', $2::date) AS k`, [busiest.id, today])).k;
  if (febRow) {
    assert.equal(Number(kartuStok.saldo_awal), Number(febRow.saldo_awal));
    assert.equal(Number(kartuStok.saldo_akhir), Number(febRow.saldo_akhir), 'kartu stok ends at the persediaan saldo');
  }
  if (kartuStok.rows.length) assert.equal(Number(kartuStok.rows.at(-1).saldo), Number(kartuStok.saldo_akhir), 'the last line carries the closing saldo');
  const mutasi = (await one(`SELECT public.simbar_laporan_gabungan('mutasi', '{}', 1) AS r`)).r;
  const movements = (await one('SELECT (SELECT count(*) FROM simbar.stock_in) + (SELECT count(*) FROM simbar.stock_out) AS n')).n;
  assert.equal(Number(mutasi.total), Number(movements), 'mutasi lists every masuk and keluar');
  const onlyIn = (await one(`SELECT public.simbar_laporan_gabungan('mutasi', '{"jenis":"in"}', 1) AS r`)).r;
  assert.ok(onlyIn.rows.every((row) => row.jenis === 'in'));
  const pengajuan = (await one(`SELECT public.simbar_laporan_gabungan('pengajuan', '{}', 0) AS r`)).r;
  const requests = (await one(`SELECT (SELECT count(*) FROM simbar.stock_out_requests) + (SELECT count(*) FROM simbar.permintaan_barang)
      + (SELECT count(*) FROM simbar.usulan_penerimaan_barang) + (SELECT count(*) FROM simbar.pemeliharaan_aset) AS n`)).n;
  assert.equal(Number(pengajuan.total), Number(requests));
  assert.equal(pengajuan.rows.length, Number(requests), 'page 0 returns every row');
  await assert.rejects(db.query(`SELECT public.simbar_laporan_gabungan('mutasi', '{"jenis":"x"}', 1)`), /tidak valid/);
  await as(STAFF);
  await assert.rejects(db.query(`SELECT public.simbar_laporan_persediaan('2026-01-01', '2026-01-31', NULL, '')`), /Akses ditolak/);
  await assert.rejects(db.query(`SELECT public.simbar_kartu_stok($1, '2026-01-01', '2026-01-31')`, [busiest.id]), /Akses ditolak/);
  await as(ADMIN);

  // Rapikan: merge duplicate barang, remember variants, fix suspicious jumlah, number assets, list labels.
  await as(ADMIN);
  const unitRow = await one('SELECT unit_id, category_id FROM simbar.items ORDER BY id LIMIT 1');
  const otherUnit = (await one('SELECT id FROM simbar.units WHERE id <> $1 ORDER BY id LIMIT 1', [unitRow.unit_id])).id;
  const keepId = (await one(`SELECT public.simbar_item_save(NULL, 'RAP-001', 'Map Rapikan', $1, $2, 'APBD Balikpapan', 1000, 1, NULL, NULL, 5) AS id`,
    [unitRow.category_id, unitRow.unit_id])).id;
  const dropId = (await one(`SELECT public.simbar_item_save(NULL, 'RAP-002', 'Map rapikan ', $1, $2, 'APBD Balikpapan', 1000, 1, NULL, NULL, 3) AS id`,
    [unitRow.category_id, unitRow.unit_id])).id;
  const oddId = (await one(`SELECT public.simbar_item_save(NULL, 'RAP-003', 'Map Rapikan Box', $1, $2, 'APBD Balikpapan', 1000, 1, NULL, NULL, 2) AS id`,
    [unitRow.category_id, otherUnit])).id;
  await db.query(`SELECT public.simbar_stock_save('in', NULL, $1, 4, '2026-11-11', 'rapikan')`, [dropId]);
  await db.query(`SELECT public.simbar_item_pair_ignore($1, $2)`, [oddId, keepId]);
  await db.query(`SELECT public.simbar_item_pair_ignore($1, $2)`, [keepId, oddId]);
  assert.ok((await one('SELECT public.simbar_item_pairs_ignored() AS p')).p.some(([a, b]) => Number(a) === Number(keepId) && Number(b) === Number(oddId)));
  await assert.rejects(db.query('SELECT public.simbar_item_merge($1, $2)', [keepId, oddId]), /Satuan kedua barang berbeda/);
  await db.query('SELECT public.simbar_item_merge($1, $2)', [keepId, dropId]);
  assert.equal(await stockOf(keepId), 5 + 3 + 4, 'merged stock adds up');
  assert.equal(Number((await one('SELECT count(*) AS n FROM simbar.items WHERE id = $1', [dropId])).n), 0, 'the merged barang is gone');
  assert.equal(Number((await one(`SELECT count(*) AS n FROM simbar.stock_in WHERE item_id = $1 AND notes = 'rapikan'`, [keepId])).n), 1, 'its history moved');
  await as(STAFF);
  await assert.rejects(db.query('SELECT public.simbar_item_merge($1, $2)', [keepId, oddId]), /Akses ditolak/);
  await assert.rejects(db.query('SELECT public.simbar_aset_register_assign()'), /Akses ditolak/);
  await as(ADMIN);

  const asetRow = await one('SELECT id FROM simbar.barang_aset ORDER BY id LIMIT 1');
  await db.query('UPDATE simbar.barang_aset SET jumlah = 2019, nomor_register = NULL WHERE id = $1', [asetRow.id]);
  assert.ok((await one('SELECT public.simbar_aset_suspicious() AS s')).s.some((row) => Number(row.id) === Number(asetRow.id)), 'jumlah 2019 is flagged');
  const registerBefore = (await one('SELECT public.simbar_aset_register_status() AS s')).s;
  assert.ok(Number(registerBefore.ditahan) >= 1);
  const assigned = (await one('SELECT public.simbar_aset_register_assign() AS r')).r;
  assert.equal(Number(assigned.aset), Number(registerBefore.tanpa_register), 'every asset without register and with a sane jumlah gets one');
  assert.equal(Number((await one('SELECT public.simbar_aset_register_assign() AS r')).r.aset), 0, 'running again assigns nothing');
  assert.equal((await one('SELECT nomor_register FROM simbar.barang_aset WHERE id = $1', [asetRow.id])).nomor_register, null, 'suspicious jumlah is skipped');
  const overlap = await one(`WITH r AS (
      SELECT id, kategori_aset_id AS k, (regexp_match(nomor_register, '^([0-9]+)'))[1]::int AS a, (regexp_match(nomor_register, '([0-9]+)$'))[1]::int AS b
      FROM simbar.barang_aset WHERE nomor_register ~ '^[0-9]{4}(-[0-9]{4})?$')
    SELECT count(*) AS n FROM r x JOIN r y ON x.k IS NOT DISTINCT FROM y.k AND x.id <> y.id AND x.a <= y.b AND y.a <= x.b`);
  assert.equal(Number(overlap.n), 0, 'register numbers never overlap inside a kategori');
  const multi = await one(`SELECT jumlah, nomor_register FROM simbar.barang_aset WHERE jumlah BETWEEN 2 AND 100 AND nomor_register ~ '^[0-9]{4}-[0-9]{4}$' LIMIT 1`);
  if (multi) {
    const [a, b] = multi.nomor_register.split('-').map(Number);
    assert.equal(b - a + 1, Number(multi.jumlah), 'a range covers every unit');
  }
  await db.query('SELECT public.simbar_aset_fix_jumlah($1, 1, 2019)', [asetRow.id]);
  assert.equal(Number((await one('SELECT jumlah FROM simbar.barang_aset WHERE id = $1', [asetRow.id])).jumlah), 1);
  assert.equal(Number((await one('SELECT public.simbar_aset_register_assign() AS r')).r.aset), 1, 'after the fix it gets a number');
  // Smaller doubts: many units, expensive units, or equipment a room has one of. "Sudah benar" keeps them off the list.
  const doubt = async () => (await one('SELECT public.simbar_aset_suspicious() AS s')).s.find((row) => Number(row.id) === Number(asetRow.id));
  const asetBefore = await one('SELECT nama_aset, harga_perolehan FROM simbar.barang_aset WHERE id = $1', [asetRow.id]);
  await db.query(`UPDATE simbar.barang_aset SET jumlah = 25, jumlah_dicek = false, nama_aset = 'Kursi Uji', harga_perolehan = 100000 WHERE id = $1`, [asetRow.id]);
  assert.equal((await doubt()).alasan, 'banyak');
  await db.query(`UPDATE simbar.barang_aset SET jumlah = 3, harga_perolehan = 7000000 WHERE id = $1`, [asetRow.id]);
  assert.equal((await doubt()).alasan, 'mahal');
  await db.query(`UPDATE simbar.barang_aset SET nama_aset = 'Mesin Absen Uji', harga_perolehan = 100000 WHERE id = $1`, [asetRow.id]);
  assert.equal((await doubt()).alasan, 'tunggal');
  await db.query('SELECT public.simbar_aset_jumlah_ok($1)', [asetRow.id]);
  assert.equal(await doubt(), undefined, 'confirmed jumlah is not listed again');
  await db.query(`UPDATE simbar.barang_aset SET jumlah = 2019 WHERE id = $1`, [asetRow.id]);
  assert.equal((await doubt()).alasan, 'tahun', 'jumlah > 100 is always listed');
  await assert.rejects(db.query('SELECT public.simbar_aset_jumlah_ok($1)', [asetRow.id]), /harus diperbaiki dulu/);
  await db.query('SELECT public.simbar_aset_fix_jumlah($1, 1, 2019)', [asetRow.id]);
  assert.equal(await doubt(), undefined);
  await db.query('UPDATE simbar.barang_aset SET nama_aset = $2, harga_perolehan = $3 WHERE id = $1', [asetRow.id, asetBefore.nama_aset, asetBefore.harga_perolehan]);
  const labels = (await one(`SELECT public.simbar_aset_labels('{}') AS l`)).l;
  assert.ok(labels.length > 0 && labels.every((row) => Number(row.jumlah) <= 100));

  // Foto barang: catalog editors set a photo with its source; others cannot.
  await as(ADMIN);
  assert.equal((await one('SELECT public.simbar_can_edit_items() AS ok')).ok, true);
  await db.query(`SELECT public.simbar_item_set_photo($1, 'https://res.cloudinary.com/demo/image/upload/v1/simbar-items/x.jpg', 'Gambar sementara SIMBAR')`, [keepId]);
  const itemPhotoRow = await one('SELECT photo, photo_source FROM simbar.items WHERE id = $1', [keepId]);
  assert.equal(itemPhotoRow.photo_source, 'Gambar sementara SIMBAR');
  await assert.rejects(db.query(`SELECT public.simbar_item_set_photo($1, 'http://contoh.test/x.jpg')`, [keepId]), /https/);
  await db.query(`SELECT public.simbar_item_set_photo($1, 'https://res.cloudinary.com/demo/image/upload/v2/simbar-items/y.jpg')`, [keepId]);
  assert.equal((await one('SELECT photo_source FROM simbar.items WHERE id = $1', [keepId])).photo_source, 'Foto asli', 'an app upload counts as a real photo');
  assert.ok((await one('SELECT public.simbar_item_get($1) AS g', [keepId])).g.photo.endsWith('y.jpg'));
  await db.query('SELECT public.simbar_item_set_photo($1, NULL)', [keepId]);
  assert.equal((await one('SELECT photo_source FROM simbar.items WHERE id = $1', [keepId])).photo_source, null);
  await as(STAFF);
  assert.equal((await one('SELECT public.simbar_can_edit_items() AS ok')).ok, false);
  await assert.rejects(db.query(`SELECT public.simbar_item_set_photo($1, 'https://x.test/a.jpg')`, [keepId]), /Akses ditolak/);
  await as(ADMIN);

  // Foto aset: the warehouse photographs an asset; same-name assets without a photo show it as an example.
  await as(ADMIN);
  const twin = await one(`SELECT lower(nama_aset) AS nama, min(id) AS a, max(id) AS b FROM simbar.barang_aset
      GROUP BY lower(nama_aset) HAVING count(*) >= 2 AND count(*) FILTER (WHERE coalesce(foto, '') <> '') = 0 LIMIT 1`);
  const fotoHash = 'ab'.repeat(32);
  await db.query(`INSERT INTO simbar.media_objects (legacy_path, legacy_reference, cloudinary_asset_id, cloudinary_public_id, cloudinary_version, format, file_size, sha256, uploaded_by, uploaded_at)
      VALUES ($1, $2, $3, $4, 1, 'jpg', 1234, $5, $6, now())`,
    ['cloudinary/simbar-uploads/' + fotoHash, 'media:' + fotoHash, '7f'.repeat(16), 'simbar-uploads/' + fotoHash, '6e'.repeat(32), admin.id]);
  const saved = (await one('SELECT public.simbar_aset_set_foto($1, $2) AS r', [twin.a, 'media:' + fotoHash])).r;
  assert.equal(saved.foto, 'media:' + fotoHash);
  assert.ok((await one('SELECT foto_diambil_at FROM simbar.barang_aset WHERE id = $1', [twin.a])).foto_diambil_at, 'the photo time is kept');
  const twinOther = (await one('SELECT public.simbar_aset_get($1) AS g', [twin.b])).g;
  assert.equal(twinOther.contoh?.foto, 'media:' + fotoHash, 'a same-name asset shows the photo as an example');
  assert.equal((await one('SELECT public.simbar_aset_get($1) AS g', [twin.a])).g.contoh, null, 'an asset with its own photo needs no example');
  const progress = (await one('SELECT public.simbar_aset_foto_progress() AS p')).p;
  assert.equal(progress.ruangan.reduce((sum, r) => sum + Number(r.total), 0), Number(progress.total));
  await assert.rejects(db.query(`SELECT public.simbar_aset_set_foto($1, 'media:' || $2)`, [twin.b, 'ef'.repeat(32)]), /Foto tidak ditemukan/);
  await as(STAFF);
  await assert.rejects(db.query('SELECT public.simbar_aset_set_foto($1, NULL)', [twin.a]), /Akses ditolak/);
  await as(ADMIN);

  // Mutasi aset: pindah ruangan bolak-balik tercatat per aset; staff hanya bisa melihat.
  const mAset = await one(`SELECT a.id, a.ruangan_id AS room FROM simbar.barang_aset a ORDER BY a.id LIMIT 1`);
  const mRoom = (await one(`SELECT id FROM simbar.ruangan WHERE is_active AND id <> $1 ORDER BY id LIMIT 1`, [mAset.room])).id;
  await db.query(`UPDATE simbar.ruangan SET is_active = true WHERE id = $1`, [mAset.room]);
  const mBefore = (await one(`SELECT count(*)::int AS n FROM simbar.mutasi_aset WHERE barang_aset_id = $1`, [mAset.id])).n;
  await assert.rejects(db.query(`SELECT public.simbar_mutasi_aset_move($1, $2, (simbar.local_today() + 1), NULL)`, [[mAset.id], mRoom]), /Tanggal pindah/);
  await assert.rejects(db.query(`SELECT public.simbar_mutasi_aset_move($1, $2, simbar.local_today(), NULL)`, [[mAset.id], mAset.room]), /sudah berada/);
  const mGo = (await one(`SELECT public.simbar_mutasi_aset_move($1, $2, DATE '2026-01-05', 'Dipinjam') AS m`, [[mAset.id], mRoom])).m;
  assert.equal(mGo.moved, 1);
  await db.query(`SELECT public.simbar_mutasi_aset_move($1, $2, DATE '2026-01-09', 'Dikembalikan')`, [[mAset.id], mAset.room]);
  const mList = (await one(`SELECT public.simbar_mutasi_aset_list($1, 1) AS l`, [JSON.stringify({ aset: mAset.id })])).l;
  assert.equal(mList.total, mBefore + 2);
  assert.equal(mList.rows[0].alasan, 'Dikembalikan');
  assert.equal(mList.rows[0].dari, mList.rows[1].ke);
  assert.equal(mList.rows[0].ke, mList.rows[1].dari);
  assert.equal(String(mList.rows[1].tanggal).slice(0, 10), '2026-01-05');
  assert.ok(mList.rows[0].petugas);
  // "Pindahkan" in Daftar aset is recorded by the trigger too, without a reason and dated today.
  await db.query('SELECT public.simbar_aset_move($1, $2)', [[mAset.id], mRoom]);
  const mBulk = (await one(`SELECT public.simbar_mutasi_aset_list($1, 0) AS l`, [JSON.stringify({ aset: mAset.id, ruangan: mRoom })])).l;
  assert.equal(mBulk.total, mBefore + 3);
  assert.equal(mBulk.rows[0].alasan, null);
  assert.equal((await one(`SELECT ruangan_id AS r FROM simbar.barang_aset WHERE id = $1`, [mAset.id])).r, mRoom);
  await as(STAFF);
  await assert.rejects(db.query(`SELECT public.simbar_mutasi_aset_move($1, $2, simbar.local_today(), NULL)`, [[mAset.id], mAset.room]), /Akses ditolak/);
  assert.ok((await one(`SELECT public.simbar_mutasi_aset_list('{}', 1) AS l`)).l.total >= 3);
  await as(ADMIN);

  // Terima barang (batch): barcode lookup, linking, and an all-or-nothing batch of barang masuk.
  const bUnit = (await one('SELECT id FROM simbar.units ORDER BY id LIMIT 1')).id;
  const bCat = (await one('SELECT id FROM simbar.categories ORDER BY id LIMIT 1')).id;
  const bA = (await one(`SELECT public.simbar_item_save(NULL, 'BATCH-A', 'Batch A', $1, $2, 'APBD', 0, 0, NULL, '8990001112223', 0) AS id`, [bCat, bUnit])).id;
  const bB = (await one(`SELECT public.simbar_item_save(NULL, 'BATCH-B', 'Batch B', $1, $2, 'APBD', 0, 0) AS id`, [bCat, bUnit])).id;
  assert.equal((await one(`SELECT public.simbar_item_by_barcode(' 8990001112223 ') AS i`)).i.id, bA);
  assert.equal((await one(`SELECT public.simbar_item_by_barcode('8990009999999') AS i`)).i, null);
  await assert.rejects(db.query(`SELECT public.simbar_item_by_barcode('12')`), /Barcode tidak valid/);
  await assert.rejects(db.query(`SELECT public.simbar_item_link_barcode($1, '8990001112223')`, [bB]), /sudah dipakai barang "Batch A"/);
  await db.query(`SELECT public.simbar_item_link_barcode($1, '8990004445556')`, [bB]);
  await db.query(`SELECT public.simbar_item_link_barcode($1, '8990004445556')`, [bB]);
  await assert.rejects(db.query(`SELECT public.simbar_item_link_barcode($1, '8990007777777')`, [bB]), /sudah punya barcode lain/);
  assert.equal((await one(`SELECT public.simbar_item_by_barcode('8990004445556') AS i`)).i.name, 'Batch B');
  const bLines = (lines) => JSON.stringify(lines);
  await assert.rejects(db.query(`SELECT public.simbar_stock_in_batch('2026-11-10', 'x', $1)`, [bLines([])]), /Daftar barang kosong/);
  await assert.rejects(db.query(`SELECT public.simbar_stock_in_batch('2026-11-10', 'x', $1)`,
    [bLines([{ item: bA, quantity: 2 }, { item: bA, quantity: 1 }])]), /dua kali/);
  await assert.rejects(db.query(`SELECT public.simbar_stock_in_batch('2026-11-10', 'x', $1)`,
    [bLines([{ item: bA, quantity: 5 }, { item: bB, quantity: 0 }])]), /Jumlah minimal 1/);
  await assert.rejects(db.query(`SELECT public.simbar_stock_in_batch((simbar.local_today() + 1), 'x', $1)`, [bLines([{ item: bA, quantity: 1 }])]), /Tanggal/);
  assert.equal(await stockOf(bA), 0, 'A failed batch leaves no stock behind.');
  const bSaved = (await one(`SELECT public.simbar_stock_in_batch('2026-11-10', 'Dropping uji', $1) AS r`,
    [bLines([{ item: bA, quantity: 5 }, { item: bB, quantity: 12 }])])).r;
  assert.deepEqual(bSaved, { lines: 2, units: 17 });
  assert.equal(await stockOf(bA), 5);
  assert.equal(await stockOf(bB), 12);
  assert.equal((await one(`SELECT count(*)::int AS n FROM simbar.stock_in WHERE item_id IN ($1, $2) AND notes = 'Dropping uji'`, [bA, bB])).n, 2);
  await as(STAFF);
  await assert.rejects(db.query(`SELECT public.simbar_stock_in_batch('2026-11-10', 'x', $1)`, [bLines([{ item: bA, quantity: 1 }])]), /Akses ditolak/);
  await assert.rejects(db.query(`SELECT public.simbar_item_by_barcode('8990001112223')`), /Akses ditolak/);
  await as(ADMIN);

  // Pengaturan cetak (kop dan tanda tangan): one copy for every device; staff read, the warehouse writes.
  assert.equal((await one(`SELECT public.simbar_cetak_settings() AS s`)).s, null);
  await assert.rejects(db.query(`SELECT public.simbar_cetak_settings_save($1)`, [JSON.stringify({ kop: 'X', lain: 'y' })]), /tidak valid/);
  await assert.rejects(db.query(`SELECT public.simbar_cetak_settings_save($1)`, [JSON.stringify({ kop: 'x'.repeat(801) })]), /terlalu panjang/);
  await assert.rejects(db.query(`SELECT public.simbar_cetak_settings_save($1)`, [JSON.stringify(['kop'])]), /tidak valid/);
  await db.query(`SELECT public.simbar_cetak_settings_save($1)`, [JSON.stringify({ kop: 'KOP UJI', logo: 'ya', kiriNama: 'A' })]);
  await db.query(`SELECT public.simbar_cetak_settings_save($1)`, [JSON.stringify({ kop: 'KOP UJI 2', logo: 'tidak' })]);
  await as(STAFF);
  assert.deepEqual((await one(`SELECT public.simbar_cetak_settings() AS s`)).s, { kop: 'KOP UJI 2', logo: 'tidak' });
  await assert.rejects(db.query(`SELECT public.simbar_cetak_settings_save($1)`, [JSON.stringify({ kop: 'Z' })]), /Akses ditolak/);
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
