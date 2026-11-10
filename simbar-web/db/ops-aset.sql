-- Barang aset, ruangan and kategori aset. Safe to re-run. Requires ops-core.sql and ops-media.sql.
-- Ports BarangAsetController, RuanganController and KategoriAsetController. Every role may browse assets
-- (staff.aset routes); only admin and admin_gudang change them.

BEGIN;

CREATE OR REPLACE FUNCTION public.simbar_aset_options()
RETURNS jsonb
LANGUAGE plpgsql STABLE SECURITY DEFINER SET search_path = ''
AS $function$
BEGIN
    PERFORM simbar.require_profile(ARRAY['admin', 'admin_gudang', 'staff']);
    RETURN jsonb_build_object(
        'rooms', (SELECT coalesce(jsonb_agg(jsonb_build_object('id', r.id, 'name', r.nama_ruangan, 'lokasi', r.lokasi,
                'keterangan', r.keterangan, 'active', r.is_active, 'placeholder', r.is_migration_placeholder,
                'assets', (SELECT count(*) FROM simbar.barang_aset a WHERE a.ruangan_id = r.id))
            ORDER BY NOT r.is_active, lower(r.nama_ruangan)), '[]') FROM simbar.ruangan r),
        'categories', (SELECT coalesce(jsonb_agg(jsonb_build_object('id', k.id, 'name', k.nama_kategori, 'golongan', k.golongan,
                'kodeGolongan', k.kode_golongan, 'umurManfaat', k.umur_manfaat, 'deskripsi', k.deskripsi, 'active', k.is_active,
                'assets', (SELECT count(*) FROM simbar.barang_aset a WHERE a.kategori_aset_id = k.id))
            ORDER BY NOT k.is_active, lower(k.nama_kategori)), '[]') FROM simbar.kategori_aset k),
        'years', (SELECT coalesce(jsonb_agg(DISTINCT tahun_perolehan ORDER BY tahun_perolehan DESC), '[]')
            FROM simbar.barang_aset WHERE tahun_perolehan IS NOT NULL)
    );
END;
$function$;

-- filters: {search, kategori, ruangan, kondisi, status, tahun, butuh_perbaikan}. page_number 0 returns every match.
CREATE OR REPLACE FUNCTION public.simbar_aset_list(filters jsonb DEFAULT '{}', page_number integer DEFAULT 1)
RETURNS jsonb
LANGUAGE plpgsql STABLE SECURITY DEFINER SET search_path = ''
AS $function$
DECLARE
    search_text text := btrim(coalesce(filters->>'search', ''));
    pattern text;
    result jsonb;
BEGIN
    PERFORM simbar.require_profile(ARRAY['admin', 'admin_gudang', 'staff']);
    IF char_length(search_text) > 100 OR page_number IS NULL OR page_number NOT BETWEEN 0 AND 10000 THEN
        RAISE invalid_parameter_value USING MESSAGE = 'Filter aset tidak valid.';
    END IF;
    pattern := '%' || replace(replace(replace(search_text, '!', '!!'), '%', '!%'), '_', '!_') || '%';

    WITH matched AS MATERIALIZED (
        SELECT a.id, a.kode_aset AS code, a.nama_aset AS name, a.merk, a.jumlah, a.kondisi, a.status_aset AS status,
               a.butuh_perbaikan AS repair, a.tahun_perolehan AS year, a.harga_perolehan AS price, a.nomor_register AS register,
               a.sumber_dana AS dana, k.nama_kategori AS category, k.golongan, r.nama_ruangan AS room, a.foto IS NOT NULL AS has_photo
        FROM simbar.barang_aset a
        JOIN simbar.kategori_aset k ON k.id = a.kategori_aset_id
        JOIN simbar.ruangan r ON r.id = a.ruangan_id
        WHERE (a.nama_aset ILIKE pattern ESCAPE '!' OR a.kode_aset ILIKE pattern ESCAPE '!' OR a.merk ILIKE pattern ESCAPE '!'
               OR a.nomor_register ILIKE pattern ESCAPE '!')
          AND (nullif(filters->>'kategori', '') IS NULL OR a.kategori_aset_id = (filters->>'kategori')::bigint)
          AND (nullif(filters->>'ruangan', '') IS NULL OR a.ruangan_id = (filters->>'ruangan')::bigint)
          AND (nullif(filters->>'kondisi', '') IS NULL OR a.kondisi = filters->>'kondisi')
          AND (nullif(filters->>'status', '') IS NULL OR a.status_aset = filters->>'status')
          AND (nullif(filters->>'tahun', '') IS NULL OR a.tahun_perolehan = (filters->>'tahun')::integer)
          AND (coalesce((filters->>'butuh_perbaikan')::boolean, false) = false OR a.butuh_perbaikan)
    )
    SELECT jsonb_build_object(
        'summary', (SELECT jsonb_build_object('total', count(*), 'baik', count(*) FILTER (WHERE kondisi = 'baik'),
                'rusak_ringan', count(*) FILTER (WHERE kondisi = 'rusak_ringan'), 'rusak_berat', count(*) FILTER (WHERE kondisi = 'rusak_berat'),
                'butuh_perbaikan', count(*) FILTER (WHERE butuh_perbaikan), 'nilai', coalesce(sum(harga_perolehan), 0))
            FROM simbar.barang_aset),
        'total', (SELECT count(*) FROM matched),
        'rows', coalesce((SELECT jsonb_agg(to_jsonb(m) ORDER BY lower(m.name), m.id)
            FROM (SELECT * FROM matched ORDER BY lower(name), id
                  LIMIT CASE WHEN page_number = 0 THEN NULL ELSE 15 END
                  OFFSET CASE WHEN page_number = 0 THEN 0 ELSE (page_number - 1) * 15 END) m), '[]')
    ) INTO result;
    RETURN result;
END;
$function$;

CREATE OR REPLACE FUNCTION public.simbar_aset_get(aset_id bigint)
RETURNS jsonb
LANGUAGE plpgsql STABLE SECURITY DEFINER SET search_path = ''
AS $function$
DECLARE
    actor simbar.users%ROWTYPE := simbar.require_profile(ARRAY['admin', 'admin_gudang', 'staff']);
    result jsonb;
BEGIN
    SELECT to_jsonb(a) || jsonb_build_object('kategori', k.nama_kategori, 'golongan', k.golongan, 'umur_manfaat_kategori', k.umur_manfaat,
            'ruangan', r.nama_ruangan, 'created_by_name', u.name,
            'pemeliharaan', (SELECT coalesce(jsonb_agg(jsonb_build_object('id', p.id, 'tanggal', p.tanggal_pemeliharaan,
                    'jenis', p.jenis_pemeliharaan, 'status', p.status, 'teknisi', p.teknisi, 'deskripsi', p.deskripsi_masalah)
                ORDER BY p.tanggal_pemeliharaan DESC, p.id DESC), '[]')
                FROM (SELECT * FROM simbar.pemeliharaan_aset WHERE barang_aset_id = a.id
                      AND (actor.role <> 'staff' OR created_by = actor.id)
                      ORDER BY tanggal_pemeliharaan DESC, id DESC LIMIT 10) p))
    INTO result
    FROM simbar.barang_aset a
    JOIN simbar.kategori_aset k ON k.id = a.kategori_aset_id
    JOIN simbar.ruangan r ON r.id = a.ruangan_id
    LEFT JOIN simbar.users u ON u.id = a.created_by
    WHERE a.id = aset_id;
    IF result IS NULL THEN
        RAISE no_data_found USING MESSAGE = 'Aset tidak ditemukan.';
    END IF;
    RETURN result;
END;
$function$;

CREATE OR REPLACE FUNCTION public.simbar_aset_next_code(kategori_ref bigint)
RETURNS text
LANGUAGE plpgsql SECURITY DEFINER SET search_path = ''
AS $function$
BEGIN
    PERFORM simbar.require_profile(ARRAY['admin', 'admin_gudang']);
    RETURN simbar.next_kode_aset(kategori_ref);
END;
$function$;

-- aset_id NULL creates an aset. form keys follow the barang_aset columns; "foto" is a photo reference
-- (omit the key to keep the current photo, send null to remove it).
CREATE OR REPLACE FUNCTION public.simbar_aset_save(aset_id bigint, form jsonb)
RETURNS bigint
LANGUAGE plpgsql SECURITY DEFINER SET search_path = ''
AS $function$
DECLARE
    actor simbar.users%ROWTYPE := simbar.require_profile(ARRAY['admin', 'admin_gudang']);
    current simbar.barang_aset%ROWTYPE;
    code text := btrim(form->>'kode_aset');
    title text := btrim(form->>'nama_aset');
    kategori_ref bigint := nullif(form->>'kategori_aset_id', '')::bigint;
    room_ref bigint := nullif(form->>'ruangan_id', '')::bigint;
    kondisi_value text := form->>'kondisi';
    masuk date := nullif(form->>'tanggal_masuk', '')::date;
    tahun integer := nullif(form->>'tahun_perolehan', '')::integer;
    qty integer := nullif(form->>'jumlah', '')::integer;
    harga numeric := nullif(form->>'harga_perolehan', '')::numeric;
    residu numeric := coalesce(nullif(form->>'nilai_residu', '')::numeric, 0);
    umur integer := nullif(form->>'umur_manfaat_override', '')::integer;
    photo text;
    saved bigint;
    room_name text;
BEGIN
    IF aset_id IS NOT NULL THEN
        SELECT * INTO current FROM simbar.barang_aset WHERE id = aset_id FOR UPDATE;
        IF NOT FOUND THEN
            RAISE no_data_found USING MESSAGE = 'Aset tidak ditemukan.';
        END IF;
    END IF;
    photo := CASE WHEN form ? 'foto' THEN nullif(form->>'foto', '') ELSE current.foto END;

    IF coalesce(code, '') = '' OR char_length(code) > 50 THEN
        RAISE check_violation USING MESSAGE = 'Kode aset harus diisi (maksimal 50 karakter).';
    ELSIF EXISTS (SELECT 1 FROM simbar.barang_aset WHERE kode_aset = code AND id IS DISTINCT FROM aset_id) THEN
        RAISE unique_violation USING MESSAGE = 'Kode aset sudah ada.';
    ELSIF coalesce(title, '') = '' OR char_length(title) > 255 THEN
        RAISE check_violation USING MESSAGE = 'Nama aset harus diisi.';
    ELSIF kategori_ref IS NULL OR NOT EXISTS (SELECT 1 FROM simbar.kategori_aset WHERE id = kategori_ref) THEN
        RAISE check_violation USING MESSAGE = 'Kategori aset harus dipilih.';
    ELSIF room_ref IS NULL OR NOT EXISTS (SELECT 1 FROM simbar.ruangan WHERE id = room_ref) THEN
        RAISE check_violation USING MESSAGE = 'Ruangan harus dipilih.';
    ELSIF kondisi_value IS NULL OR kondisi_value NOT IN ('baik', 'rusak_ringan', 'rusak_berat') THEN
        RAISE check_violation USING MESSAGE = 'Kondisi harus dipilih.';
    ELSIF masuk IS NULL THEN
        RAISE check_violation USING MESSAGE = 'Tanggal perolehan harus diisi.';
    ELSIF tahun IS NOT NULL AND tahun < 1980 THEN
        RAISE check_violation USING MESSAGE = 'Tahun perolehan minimal 1980.';
    ELSIF qty IS NULL OR qty < 1 THEN
        RAISE check_violation USING MESSAGE = 'Jumlah minimal 1.';
    ELSIF (aset_id IS NULL AND harga IS NULL) OR harga < 0 OR residu < 0 OR umur < 0 THEN
        RAISE check_violation USING MESSAGE = 'Harga perolehan harus diisi dan nilai tidak boleh negatif.';
    ELSIF char_length(coalesce(form->>'merk', '')) > 100 OR char_length(coalesce(form->>'sumber_dana', '')) > 100
       OR char_length(coalesce(form->>'nomor_register', '')) > 50 OR char_length(coalesce(form->>'spesifikasi', '')) > 255
       OR char_length(coalesce(form->>'no_sertifikat', '')) > 255 OR char_length(coalesce(form->>'asal_perolehan', '')) > 255
       OR char_length(coalesce(form->>'ukuran_konstruksi', '')) > 255 THEN
        RAISE check_violation USING MESSAGE = 'Salah satu isian terlalu panjang.';
    END IF;
    IF photo IS DISTINCT FROM current.foto THEN
        PERFORM simbar.check_media_reference(actor.id, photo);
    END IF;

    IF aset_id IS NULL THEN
        INSERT INTO simbar.barang_aset (kode_aset, nama_aset, kategori_aset_id, ruangan_id, kondisi, tanggal_masuk, tahun_perolehan,
            jumlah, harga_perolehan, nilai_residu, umur_manfaat_override, sumber_dana, nomor_register, no_sertifikat, asal_perolehan,
            ukuran_konstruksi, merk, spesifikasi, foto, keterangan, butuh_perbaikan, status_aset, created_by, created_at, updated_at)
        VALUES (code, title, kategori_ref, room_ref, kondisi_value, masuk, coalesce(tahun, extract(year FROM masuk)::integer),
            qty, harga, residu, umur, nullif(btrim(form->>'sumber_dana'), ''), nullif(btrim(form->>'nomor_register'), ''),
            nullif(btrim(form->>'no_sertifikat'), ''), nullif(btrim(form->>'asal_perolehan'), ''), nullif(btrim(form->>'ukuran_konstruksi'), ''),
            nullif(btrim(form->>'merk'), ''), nullif(btrim(form->>'spesifikasi'), ''), photo, nullif(btrim(form->>'keterangan'), ''),
            kondisi_value = 'rusak_berat', 'aktif', actor.id, simbar.local_now(), simbar.local_now())
        RETURNING id INTO saved;
        SELECT nama_ruangan INTO room_name FROM simbar.ruangan WHERE id = room_ref;
        PERFORM simbar.write_log(actor.id, 'barang_aset_created', 'Aset "' || title || '" (' || code || ') ditambahkan ke ruangan '
            || room_name || ' dengan nilai Rp ' || replace(to_char(harga, 'FM999,999,999,999,990'), ',', '.'));
    ELSE
        UPDATE simbar.barang_aset SET kode_aset = code, nama_aset = title, kategori_aset_id = kategori_ref, ruangan_id = room_ref,
            kondisi = kondisi_value, tanggal_masuk = masuk, tahun_perolehan = tahun, jumlah = qty, harga_perolehan = harga,
            nilai_residu = residu, umur_manfaat_override = umur, sumber_dana = nullif(btrim(form->>'sumber_dana'), ''),
            nomor_register = nullif(btrim(form->>'nomor_register'), ''), no_sertifikat = nullif(btrim(form->>'no_sertifikat'), ''),
            asal_perolehan = nullif(btrim(form->>'asal_perolehan'), ''), ukuran_konstruksi = nullif(btrim(form->>'ukuran_konstruksi'), ''),
            merk = nullif(btrim(form->>'merk'), ''), spesifikasi = nullif(btrim(form->>'spesifikasi'), ''), foto = photo,
            keterangan = nullif(btrim(form->>'keterangan'), ''), butuh_perbaikan = kondisi_value = 'rusak_berat',
            updated_at = simbar.local_now()
        WHERE id = aset_id RETURNING id INTO saved;
        PERFORM simbar.write_log(actor.id, 'barang_aset_updated', 'Aset "' || title || '" (' || code || ') diupdate');
    END IF;
    RETURN saved;
END;
$function$;

-- Deletes one or many assets (their pemeliharaan history is removed with them, as in Laravel).
CREATE OR REPLACE FUNCTION public.simbar_aset_delete(aset_ids bigint[])
RETURNS integer
LANGUAGE plpgsql SECURITY DEFINER SET search_path = ''
AS $function$
DECLARE
    actor simbar.users%ROWTYPE := simbar.require_profile(ARRAY['admin', 'admin_gudang']);
    removed integer;
    single text;
BEGIN
    IF aset_ids IS NULL OR cardinality(aset_ids) NOT BETWEEN 1 AND 500 THEN
        RAISE check_violation USING MESSAGE = 'Pilih minimal 1 aset terlebih dahulu.';
    END IF;
    SELECT '"' || nama_aset || '" (' || kode_aset || ')' INTO single FROM simbar.barang_aset WHERE id = aset_ids[1];
    DELETE FROM simbar.barang_aset WHERE id = ANY (aset_ids);
    GET DIAGNOSTICS removed = ROW_COUNT;
    IF removed = 0 THEN
        RAISE no_data_found USING MESSAGE = 'Data aset tidak ditemukan.';
    END IF;
    PERFORM simbar.write_log(actor.id, CASE WHEN removed = 1 THEN 'barang_aset_deleted' ELSE 'barang_aset_bulk_deleted' END,
        CASE WHEN removed = 1 THEN 'Aset ' || single || ' dihapus' ELSE 'Hapus massal ' || removed || ' aset' END);
    RETURN removed;
END;
$function$;

CREATE OR REPLACE FUNCTION public.simbar_aset_move(aset_ids bigint[], target_room bigint)
RETURNS jsonb
LANGUAGE plpgsql SECURITY DEFINER SET search_path = ''
AS $function$
DECLARE
    actor simbar.users%ROWTYPE := simbar.require_profile(ARRAY['admin', 'admin_gudang']);
    room_name text;
    moved integer;
    skipped integer;
BEGIN
    IF aset_ids IS NULL OR cardinality(aset_ids) NOT BETWEEN 1 AND 500 THEN
        RAISE check_violation USING MESSAGE = 'Pilih minimal 1 aset terlebih dahulu.';
    END IF;
    SELECT nama_ruangan INTO room_name FROM simbar.ruangan WHERE id = target_room AND is_active;
    IF room_name IS NULL THEN
        RAISE check_violation USING MESSAGE = 'Ruangan tujuan tidak aktif atau tidak ditemukan.';
    END IF;
    SELECT count(*) INTO skipped FROM simbar.barang_aset WHERE id = ANY (aset_ids) AND ruangan_id = target_room;
    UPDATE simbar.barang_aset SET ruangan_id = target_room, updated_at = simbar.local_now()
    WHERE id = ANY (aset_ids) AND ruangan_id <> target_room;
    GET DIAGNOSTICS moved = ROW_COUNT;
    PERFORM simbar.write_log(actor.id, 'barang_aset_bulk_moved', 'Pindah massal ' || moved || ' aset ke ruangan ' || room_name
        || CASE WHEN skipped > 0 THEN ' (' || skipped || ' aset dilewati)' ELSE '' END);
    RETURN jsonb_build_object('moved', moved, 'skipped', skipped, 'room', room_name);
END;
$function$;

CREATE OR REPLACE FUNCTION public.simbar_ruangan_save(room_id bigint, room_name text, room_lokasi text DEFAULT NULL,
    room_keterangan text DEFAULT NULL, room_active boolean DEFAULT true)
RETURNS bigint
LANGUAGE plpgsql SECURITY DEFINER SET search_path = ''
AS $function$
DECLARE
    actor simbar.users%ROWTYPE := simbar.require_profile(ARRAY['admin', 'admin_gudang']);
    saved bigint;
BEGIN
    room_name := btrim(room_name);
    IF coalesce(room_name, '') = '' OR char_length(room_name) > 100 THEN
        RAISE check_violation USING MESSAGE = 'Nama ruangan harus diisi (maksimal 100 karakter).';
    ELSIF char_length(coalesce(room_lokasi, '')) > 255 THEN
        RAISE check_violation USING MESSAGE = 'Lokasi maksimal 255 karakter.';
    ELSIF EXISTS (SELECT 1 FROM simbar.ruangan WHERE nama_ruangan = room_name AND id IS DISTINCT FROM room_id) THEN
        RAISE unique_violation USING MESSAGE = 'Nama ruangan sudah ada.';
    END IF;
    IF room_id IS NULL THEN
        INSERT INTO simbar.ruangan (nama_ruangan, lokasi, keterangan, is_active, created_at, updated_at)
        VALUES (room_name, nullif(btrim(room_lokasi), ''), nullif(btrim(room_keterangan), ''), true, simbar.local_now(), simbar.local_now())
        RETURNING id INTO saved;
        PERFORM simbar.write_log(actor.id, 'ruangan_created', 'Ruangan "' || room_name || '" dibuat');
    ELSE
        UPDATE simbar.ruangan SET nama_ruangan = room_name, lokasi = nullif(btrim(room_lokasi), ''),
            keterangan = nullif(btrim(room_keterangan), ''), is_active = coalesce(room_active, true), updated_at = simbar.local_now()
        WHERE id = room_id RETURNING id INTO saved;
        IF saved IS NULL THEN
            RAISE no_data_found USING MESSAGE = 'Ruangan tidak ditemukan.';
        END IF;
        PERFORM simbar.write_log(actor.id, 'ruangan_updated', 'Ruangan "' || room_name || '" diupdate');
    END IF;
    RETURN saved;
END;
$function$;

-- Rooms with assets cannot be deleted; other history moves to the system archive room (as in Laravel).
CREATE OR REPLACE FUNCTION public.simbar_ruangan_delete(room_id bigint)
RETURNS void
LANGUAGE plpgsql SECURITY DEFINER SET search_path = ''
AS $function$
DECLARE
    actor simbar.users%ROWTYPE := simbar.require_profile(ARRAY['admin', 'admin_gudang']);
    room simbar.ruangan%ROWTYPE;
    archive bigint;
BEGIN
    SELECT * INTO room FROM simbar.ruangan WHERE id = room_id FOR UPDATE;
    IF NOT FOUND THEN
        RAISE no_data_found USING MESSAGE = 'Ruangan tidak ditemukan.';
    ELSIF EXISTS (SELECT 1 FROM simbar.barang_aset WHERE ruangan_id = room_id) THEN
        RAISE foreign_key_violation USING MESSAGE = 'Ruangan tidak dapat dihapus karena masih memiliki aset.';
    ELSIF room.nama_ruangan = 'Arsip Ruangan Dihapus' THEN
        RAISE check_violation USING MESSAGE = 'Ruangan arsip sistem tidak dapat dihapus.';
    END IF;
    SELECT id INTO archive FROM simbar.ruangan WHERE nama_ruangan = 'Arsip Ruangan Dihapus';
    IF archive IS NULL THEN
        INSERT INTO simbar.ruangan (nama_ruangan, lokasi, keterangan, is_active, created_at, updated_at)
        VALUES ('Arsip Ruangan Dihapus', 'Sistem', 'Ruangan penampung referensi historis saat ruangan asli dihapus.', false,
            simbar.local_now(), simbar.local_now())
        RETURNING id INTO archive;
    END IF;
    UPDATE simbar.pemeliharaan_aset SET ruangan_id = archive WHERE ruangan_id = room_id;
    UPDATE simbar.usulan_penerimaan_barang SET ruangan_id = archive WHERE ruangan_id = room_id;
    UPDATE simbar.usulan_permintaan_barang SET ruangan_id = archive WHERE ruangan_id = room_id;
    UPDATE simbar.permintaan_barang SET ruangan_id = archive WHERE ruangan_id = room_id;
    DELETE FROM simbar.ruangan WHERE id = room_id;
    PERFORM simbar.write_log(actor.id, 'ruangan_deleted', 'Ruangan "' || room.nama_ruangan || '" dihapus');
END;
$function$;

CREATE OR REPLACE FUNCTION public.simbar_kategori_aset_save(kategori_id bigint, form jsonb)
RETURNS bigint
LANGUAGE plpgsql SECURITY DEFINER SET search_path = ''
AS $function$
DECLARE
    actor simbar.users%ROWTYPE := simbar.require_profile(ARRAY['admin', 'admin_gudang']);
    title text := btrim(form->>'nama_kategori');
    group_value text := form->>'golongan';
    umur integer := nullif(form->>'umur_manfaat', '')::integer;
    saved bigint;
BEGIN
    IF coalesce(title, '') = '' OR char_length(title) > 100 THEN
        RAISE check_violation USING MESSAGE = 'Nama kategori harus diisi (maksimal 100 karakter).';
    ELSIF EXISTS (SELECT 1 FROM simbar.kategori_aset WHERE nama_kategori = title AND id IS DISTINCT FROM kategori_id) THEN
        RAISE unique_violation USING MESSAGE = 'Nama kategori sudah ada.';
    ELSIF group_value IS NULL OR group_value NOT IN ('tanah', 'peralatan_mesin', 'gedung_bangunan', 'jalan_irigasi', 'aset_tetap_lainnya', 'kdp') THEN
        RAISE check_violation USING MESSAGE = 'Golongan harus dipilih.';
    ELSIF umur IS NULL OR umur < 0 THEN
        RAISE check_violation USING MESSAGE = 'Umur manfaat harus diisi.';
    ELSIF char_length(coalesce(form->>'kode_golongan', '')) > 20 THEN
        RAISE check_violation USING MESSAGE = 'Kode golongan maksimal 20 karakter.';
    END IF;
    IF kategori_id IS NULL THEN
        INSERT INTO simbar.kategori_aset (nama_kategori, kode_golongan, golongan, umur_manfaat, deskripsi, is_active,
            dapat_disusutkan, created_at, updated_at)
        VALUES (title, nullif(btrim(form->>'kode_golongan'), ''), group_value, umur, nullif(btrim(form->>'deskripsi'), ''), true,
            group_value NOT IN ('tanah', 'kdp'), simbar.local_now(), simbar.local_now())
        RETURNING id INTO saved;
    ELSE
        UPDATE simbar.kategori_aset SET nama_kategori = title, kode_golongan = nullif(btrim(form->>'kode_golongan'), ''),
            golongan = group_value, umur_manfaat = umur, deskripsi = nullif(btrim(form->>'deskripsi'), ''),
            is_active = coalesce((form->>'is_active')::boolean, true), dapat_disusutkan = group_value NOT IN ('tanah', 'kdp'),
            updated_at = simbar.local_now()
        WHERE id = kategori_id RETURNING id INTO saved;
        IF saved IS NULL THEN
            RAISE no_data_found USING MESSAGE = 'Kategori tidak ditemukan.';
        END IF;
    END IF;
    PERFORM simbar.write_log(actor.id, CASE WHEN kategori_id IS NULL THEN 'kategori_aset_created' ELSE 'kategori_aset_updated' END,
        'Kategori aset "' || title || '" disimpan');
    RETURN saved;
END;
$function$;

CREATE OR REPLACE FUNCTION public.simbar_kategori_aset_delete(kategori_id bigint)
RETURNS void
LANGUAGE plpgsql SECURITY DEFINER SET search_path = ''
AS $function$
DECLARE
    actor simbar.users%ROWTYPE := simbar.require_profile(ARRAY['admin', 'admin_gudang']);
    removed text;
BEGIN
    IF EXISTS (SELECT 1 FROM simbar.barang_aset WHERE kategori_aset_id = kategori_id) THEN
        RAISE foreign_key_violation USING MESSAGE = 'Kategori tidak dapat dihapus karena masih memiliki aset.';
    END IF;
    DELETE FROM simbar.kategori_aset WHERE id = kategori_id RETURNING nama_kategori INTO removed;
    IF removed IS NULL THEN
        RAISE no_data_found USING MESSAGE = 'Kategori tidak ditemukan.';
    END IF;
    PERFORM simbar.write_log(actor.id, 'kategori_aset_deleted', 'Kategori aset "' || removed || '" dihapus');
END;
$function$;

DO $grants$
DECLARE
    signature text;
BEGIN
    FOREACH signature IN ARRAY ARRAY[
        'public.simbar_aset_options()',
        'public.simbar_aset_list(jsonb, integer)',
        'public.simbar_aset_get(bigint)',
        'public.simbar_aset_next_code(bigint)',
        'public.simbar_aset_save(bigint, jsonb)',
        'public.simbar_aset_delete(bigint[])',
        'public.simbar_aset_move(bigint[], bigint)',
        'public.simbar_ruangan_save(bigint, text, text, text, boolean)',
        'public.simbar_ruangan_delete(bigint)',
        'public.simbar_kategori_aset_save(bigint, jsonb)',
        'public.simbar_kategori_aset_delete(bigint)'
    ] LOOP
        EXECUTE 'REVOKE ALL ON FUNCTION ' || signature || ' FROM PUBLIC, anon, authenticated, service_role';
        EXECUTE 'GRANT EXECUTE ON FUNCTION ' || signature || ' TO authenticated';
    END LOOP;
END;
$grants$;

COMMIT;
