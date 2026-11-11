-- Usulan penerimaan barang (every approved role) and warehouse verification. Safe to re-run.
-- Ports UsulanPenerimaanController: approving an usulan creates the barang aset. Requires ops-core.sql.
-- foto_barang keeps Laravel's JSON array of photos (at most five): links, uploaded "media:" references, or legacy paths.

BEGIN;

CREATE OR REPLACE FUNCTION public.simbar_usulan_options()
RETURNS jsonb
LANGUAGE plpgsql STABLE SECURITY DEFINER SET search_path = ''
AS $function$
BEGIN
    PERFORM simbar.require_profile(ARRAY['admin', 'admin_gudang', 'staff']);
    RETURN jsonb_build_object(
        'rooms', (SELECT coalesce(jsonb_agg(jsonb_build_object('id', id, 'name', nama_ruangan) ORDER BY lower(nama_ruangan)), '[]')
            FROM simbar.ruangan WHERE is_active),
        'categories', (SELECT coalesce(jsonb_agg(jsonb_build_object('id', id, 'name', nama_kategori) ORDER BY lower(nama_kategori)), '[]')
            FROM simbar.kategori_aset WHERE is_active)
    );
END;
$function$;

-- page_number 0 returns every match (for CSV export) instead of one page of 15.
CREATE OR REPLACE FUNCTION public.simbar_usulan_list(
    mine_only boolean, status_filter text DEFAULT 'all', room_filter bigint DEFAULT NULL,
    search_text text DEFAULT '', page_number integer DEFAULT 1
) RETURNS jsonb
LANGUAGE plpgsql STABLE SECURITY DEFINER SET search_path = ''
AS $function$
DECLARE
    actor simbar.users%ROWTYPE := CASE WHEN mine_only
        THEN simbar.require_profile(ARRAY['admin', 'admin_gudang', 'staff']) ELSE simbar.require_menu(ARRAY['usulan-admin']) END;
    pattern text;
    result jsonb;
BEGIN
    IF status_filter IS NULL OR status_filter NOT IN ('all', 'pending', 'diproses', 'disetujui', 'ditolak', 'revisi') OR
       search_text IS NULL OR char_length(search_text) > 100 OR page_number IS NULL OR page_number NOT BETWEEN 0 AND 10000 THEN
        RAISE invalid_parameter_value USING MESSAGE = 'Filter usulan tidak valid.';
    END IF;
    pattern := '%' || replace(replace(replace(btrim(search_text), '!', '!!'), '%', '!%'), '_', '!_') || '%';

    WITH scoped AS MATERIALIZED (
        SELECT u.* FROM simbar.usulan_penerimaan_barang u WHERE NOT mine_only OR u.user_id = actor.id
    ), matched AS MATERIALIZED (
        SELECT u.id, u.kode_usulan AS code, u.status, u.prioritas, u.jumlah, u.nama_barang AS name, u.merk,
               u.kondisi_barang AS kondisi, u.kelengkapan, u.estimasi_harga AS harga, u.tanggal_usulan AS date,
               u.verified_at, r.nama_ruangan AS room, req.name AS requested_by, v.name AS verified_by
        FROM scoped u
        JOIN simbar.users req ON req.id = u.user_id
        JOIN simbar.ruangan r ON r.id = u.ruangan_id
        LEFT JOIN simbar.users v ON v.id = u.verified_by
        WHERE (status_filter = 'all' OR u.status = status_filter)
          AND (room_filter IS NULL OR u.ruangan_id = room_filter)
          AND (u.kode_usulan ILIKE pattern ESCAPE '!' OR u.nama_barang ILIKE pattern ESCAPE '!' OR req.name ILIKE pattern ESCAPE '!')
    )
    SELECT jsonb_build_object(
        'counts', (SELECT jsonb_object_agg(s, (SELECT count(*) FROM scoped WHERE status = s))
                   FROM unnest(ARRAY['pending', 'diproses', 'disetujui', 'ditolak', 'revisi']) AS s),
        'total', (SELECT count(*) FROM matched),
        'rows', coalesce((SELECT jsonb_agg(to_jsonb(m) ORDER BY m.date DESC, m.id DESC)
            FROM (SELECT * FROM matched ORDER BY date DESC, id DESC
                  LIMIT CASE WHEN page_number = 0 THEN NULL ELSE 15 END
                  OFFSET CASE WHEN page_number = 0 THEN 0 ELSE (page_number - 1) * 15 END) m), '[]')
    ) INTO result;
    RETURN result;
END;
$function$;

CREATE OR REPLACE FUNCTION public.simbar_usulan_get(usulan_id bigint)
RETURNS jsonb
LANGUAGE plpgsql STABLE SECURITY DEFINER SET search_path = ''
AS $function$
DECLARE
    actor simbar.users%ROWTYPE := simbar.require_profile(ARRAY['admin', 'admin_gudang', 'staff']);
    result jsonb;
BEGIN
    SELECT jsonb_build_object('id', u.id, 'code', u.kode_usulan, 'status', u.status, 'roomId', u.ruangan_id, 'room', r.nama_ruangan,
            'kategoriId', u.kategori_aset_id, 'kategori', k.nama_kategori, 'name', u.nama_barang, 'merk', u.merk,
            'spesifikasi', u.spesifikasi, 'jumlah', u.jumlah, 'kondisi', u.kondisi_barang, 'kelengkapan', u.kelengkapan,
            'harga', u.estimasi_harga, 'sumber', u.sumber_barang, 'tanggalFisik', u.tanggal_penerimaan_fisik,
            'prioritas', u.prioritas, 'keterangan', u.keterangan_user, 'date', u.tanggal_usulan,
            'catatanAdmin', u.catatan_admin, 'alasanPenolakan', u.alasan_penolakan, 'permintaanRevisi', u.permintaan_revisi,
            'requestedBy', req.name, 'verifiedBy', v.name, 'verifiedAt', u.verified_at,
            'photos', CASE WHEN u.foto_barang ~ '^\s*\[' THEN u.foto_barang::jsonb ELSE '[]'::jsonb END,
            'aset', (SELECT jsonb_build_object('code', a.kode_aset, 'name', a.nama_aset) FROM simbar.barang_aset a WHERE a.id = u.barang_aset_id),
            'mine', u.user_id = actor.id)
    INTO result
    FROM simbar.usulan_penerimaan_barang u
    JOIN simbar.users req ON req.id = u.user_id
    JOIN simbar.ruangan r ON r.id = u.ruangan_id
    LEFT JOIN simbar.kategori_aset k ON k.id = u.kategori_aset_id
    LEFT JOIN simbar.users v ON v.id = u.verified_by
    WHERE u.id = usulan_id AND (u.user_id = actor.id OR simbar.has_menu(actor.role, ARRAY['usulan-admin']));
    IF result IS NULL THEN
        RAISE no_data_found USING MESSAGE = 'Usulan tidak ditemukan.';
    END IF;
    RETURN result;
END;
$function$;

-- usulan_id NULL submits a new usulan; otherwise the owner edits a pending/revisi usulan, which returns to pending.
-- form: {ruangan_id, kategori_aset_id, nama_barang, merk, spesifikasi, jumlah, kondisi_barang, kelengkapan,
--        estimasi_harga, sumber_barang, tanggal_penerimaan_fisik, prioritas, keterangan_user, photos: [url, ...]}
CREATE OR REPLACE FUNCTION public.simbar_usulan_save(usulan_id bigint, form jsonb)
RETURNS text
LANGUAGE plpgsql SECURITY DEFINER SET search_path = ''
AS $function$
DECLARE
    actor simbar.users%ROWTYPE := simbar.require_profile(ARRAY['admin', 'admin_gudang', 'staff']);
    current simbar.usulan_penerimaan_barang%ROWTYPE;
    room_ref bigint := nullif(form->>'ruangan_id', '')::bigint;
    kategori_ref bigint := nullif(form->>'kategori_aset_id', '')::bigint;
    name text := btrim(form->>'nama_barang');
    qty integer := nullif(form->>'jumlah', '')::integer;
    harga numeric := nullif(form->>'estimasi_harga', '')::numeric;
    photos jsonb := coalesce(form->'photos', '[]');
    duplicate text;
    code text;
    month_prefix text := 'USL-' || to_char(simbar.local_today(), 'YYYYMM') || '-';
BEGIN
    IF room_ref IS NULL OR NOT EXISTS (SELECT 1 FROM simbar.ruangan WHERE id = room_ref) THEN
        RAISE check_violation USING MESSAGE = 'Ruangan wajib dipilih.';
    ELSIF kategori_ref IS NOT NULL AND NOT EXISTS (SELECT 1 FROM simbar.kategori_aset WHERE id = kategori_ref) THEN
        RAISE check_violation USING MESSAGE = 'Kategori aset tidak ditemukan.';
    ELSIF coalesce(name, '') = '' OR char_length(name) > 255 THEN
        RAISE check_violation USING MESSAGE = 'Nama barang wajib diisi.';
    ELSIF char_length(coalesce(form->>'merk', '')) > 100 OR char_length(coalesce(form->>'sumber_barang', '')) > 100 THEN
        RAISE check_violation USING MESSAGE = 'Merk dan sumber barang maksimal 100 karakter.';
    ELSIF char_length(coalesce(form->>'spesifikasi', '')) > 1000 OR char_length(coalesce(form->>'keterangan_user', '')) > 2000 THEN
        RAISE check_violation USING MESSAGE = 'Spesifikasi atau keterangan terlalu panjang.';
    ELSIF qty IS NULL OR qty NOT BETWEEN 1 AND 9999 THEN
        RAISE check_violation USING MESSAGE = 'Jumlah minimal 1.';
    ELSIF coalesce(form->>'kondisi_barang', '') NOT IN ('baru', 'bekas_baik', 'bekas_rusak_ringan') THEN
        RAISE check_violation USING MESSAGE = 'Kondisi barang wajib dipilih.';
    ELSIF coalesce(form->>'kelengkapan', '') NOT IN ('lengkap', 'tidak_lengkap') THEN
        RAISE check_violation USING MESSAGE = 'Kelengkapan wajib dipilih.';
    ELSIF coalesce(form->>'prioritas', '') NOT IN ('rendah', 'sedang', 'tinggi') THEN
        RAISE check_violation USING MESSAGE = 'Prioritas wajib dipilih.';
    ELSIF harga < 0 THEN
        RAISE check_violation USING MESSAGE = 'Estimasi harga tidak boleh negatif.';
    ELSIF jsonb_typeof(photos) <> 'array' OR jsonb_array_length(photos) > 5 OR EXISTS (
        SELECT 1 FROM jsonb_array_elements(photos) p
        WHERE jsonb_typeof(p) <> 'string' OR (p #>> '{}' !~ '^https?://[^\s]+$' AND p #>> '{}' !~ '^media:[0-9a-f]{64}$'
              AND p #>> '{}' !~ '^uploads/') OR char_length(p #>> '{}') > 2048) THEN
        RAISE check_violation USING MESSAGE = 'Maksimal 5 link foto yang valid.';
    END IF;

    PERFORM simbar.check_media_reference(actor.id, p #>> '{}') FROM jsonb_array_elements(photos) p
    WHERE p #>> '{}' LIKE 'media:%' AND (usulan_id IS NULL OR NOT EXISTS (SELECT 1 FROM simbar.usulan_penerimaan_barang u
        WHERE u.id = usulan_id AND u.foto_barang ~ '^\s*\[' AND u.foto_barang::jsonb ? (p #>> '{}')));
    IF EXISTS (SELECT 1 FROM jsonb_array_elements(photos) p WHERE p #>> '{}' LIKE 'uploads/%'
        AND NOT EXISTS (SELECT 1 FROM simbar.usulan_penerimaan_barang u WHERE u.id = usulan_id AND u.foto_barang ~ '^\s*\['
            AND u.foto_barang::jsonb ? (p #>> '{}'))) THEN
        RAISE check_violation USING MESSAGE = 'Maksimal 5 link foto yang valid.';
    END IF;

    IF usulan_id IS NOT NULL THEN
        SELECT * INTO current FROM simbar.usulan_penerimaan_barang
        WHERE id = usulan_id AND user_id = actor.id AND status IN ('pending', 'revisi') FOR UPDATE;
        IF NOT FOUND THEN
            RAISE no_data_found USING MESSAGE = 'Usulan tidak ditemukan atau tidak dapat diubah.';
        END IF;
        -- Keep legacy uploaded paths that the owner did not remove; they cannot be re-added from the browser.
        UPDATE simbar.usulan_penerimaan_barang SET ruangan_id = room_ref, kategori_aset_id = kategori_ref, nama_barang = name,
            merk = nullif(btrim(form->>'merk'), ''), spesifikasi = nullif(btrim(form->>'spesifikasi'), ''), jumlah = qty,
            kondisi_barang = form->>'kondisi_barang', kelengkapan = form->>'kelengkapan', estimasi_harga = harga,
            sumber_barang = nullif(btrim(form->>'sumber_barang'), ''),
            tanggal_penerimaan_fisik = nullif(form->>'tanggal_penerimaan_fisik', '')::date, prioritas = form->>'prioritas',
            keterangan_user = nullif(btrim(form->>'keterangan_user'), ''), foto_barang = photos::text,
            status = 'pending', permintaan_revisi = NULL, updated_at = simbar.local_now()
        WHERE id = usulan_id;
        PERFORM simbar.write_log(actor.id, 'usulan_penerimaan_updated', 'User ' || actor.name || ' memperbarui usulan: '
            || current.kode_usulan || CASE WHEN current.status = 'revisi' THEN ' (dari status revisi)' ELSE '' END);
        RETURN current.kode_usulan;
    END IF;

    SELECT kode_usulan INTO duplicate FROM simbar.usulan_penerimaan_barang
    WHERE user_id = actor.id AND ruangan_id = room_ref AND nama_barang = name AND status IN ('pending', 'diproses', 'revisi') LIMIT 1;
    IF duplicate IS NOT NULL THEN
        RAISE unique_violation USING MESSAGE = 'Anda sudah memiliki usulan pending untuk barang yang sama di ruangan ini. Kode usulan: ' || duplicate;
    END IF;

    PERFORM pg_advisory_xact_lock(hashtext('simbar.usulan_penerimaan_barang.kode'));
    SELECT month_prefix || lpad((coalesce(max(right(kode_usulan, 4)::integer), 0) + 1)::text, 4, '0') INTO code
    FROM simbar.usulan_penerimaan_barang WHERE kode_usulan LIKE month_prefix || '____' AND right(kode_usulan, 4) ~ '^[0-9]{4}$';

    INSERT INTO simbar.usulan_penerimaan_barang (kode_usulan, user_id, ruangan_id, kategori_aset_id, nama_barang, merk, spesifikasi,
        jumlah, kondisi_barang, kelengkapan, estimasi_harga, sumber_barang, foto_barang, tanggal_usulan, tanggal_penerimaan_fisik,
        status, prioritas, keterangan_user, created_at, updated_at)
    VALUES (code, actor.id, room_ref, kategori_ref, name, nullif(btrim(form->>'merk'), ''), nullif(btrim(form->>'spesifikasi'), ''),
        qty, form->>'kondisi_barang', form->>'kelengkapan', harga, nullif(btrim(form->>'sumber_barang'), ''), photos::text,
        simbar.local_today(), nullif(form->>'tanggal_penerimaan_fisik', '')::date, 'pending', form->>'prioritas',
        nullif(btrim(form->>'keterangan_user'), ''), simbar.local_now(), simbar.local_now());
    PERFORM simbar.write_log(actor.id, 'usulan_penerimaan_created', 'User ' || actor.name
        || ' mengajukan usulan penerimaan barang: ' || name || ' (' || code || ')');
    RETURN code;
END;
$function$;

CREATE OR REPLACE FUNCTION public.simbar_usulan_cancel(usulan_id bigint)
RETURNS void
LANGUAGE plpgsql SECURITY DEFINER SET search_path = ''
AS $function$
DECLARE
    actor simbar.users%ROWTYPE := simbar.require_profile(ARRAY['admin', 'admin_gudang', 'staff']);
    removed text;
BEGIN
    DELETE FROM simbar.usulan_penerimaan_barang
    WHERE id = usulan_id AND user_id = actor.id AND status IN ('pending', 'revisi')
    RETURNING kode_usulan INTO removed;
    IF removed IS NULL THEN
        RAISE no_data_found USING MESSAGE = 'Usulan tidak ditemukan atau tidak dapat dibatalkan.';
    END IF;
    PERFORM simbar.write_log(actor.id, 'usulan_penerimaan_cancelled', 'User ' || actor.name || ' membatalkan usulan: ' || removed);
END;
$function$;

-- request_action: proses, approve (needs kategori_ref; creates the barang aset), reject, revision, delete.
CREATE OR REPLACE FUNCTION public.simbar_usulan_act(usulan_id bigint, request_action text, note text DEFAULT NULL, kategori_ref bigint DEFAULT NULL)
RETURNS text
LANGUAGE plpgsql SECURITY DEFINER SET search_path = ''
AS $function$
DECLARE
    actor simbar.users%ROWTYPE := simbar.require_menu(ARRAY['usulan-admin']);
    usulan simbar.usulan_penerimaan_barang%ROWTYPE;
    received date;
    first_photo text;
    aset_id bigint;
    aset_code text;
BEGIN
    note := nullif(btrim(note), '');
    SELECT * INTO usulan FROM simbar.usulan_penerimaan_barang WHERE id = usulan_id FOR UPDATE;
    IF NOT FOUND THEN
        RAISE no_data_found USING MESSAGE = 'Usulan tidak ditemukan.';
    ELSIF request_action IS NULL OR request_action NOT IN ('proses', 'approve', 'reject', 'revision', 'delete') THEN
        RAISE invalid_parameter_value USING MESSAGE = 'Aksi usulan tidak valid.';
    ELSIF request_action = 'delete' THEN
        DELETE FROM simbar.usulan_penerimaan_barang WHERE id = usulan.id;
        PERFORM simbar.write_log(actor.id, 'usulan_penerimaan_deleted', 'Admin ' || actor.name || ' menghapus usulan: ' || usulan.kode_usulan);
        RETURN NULL;
    ELSIF request_action = 'proses' AND usulan.status <> 'pending' THEN
        RAISE check_violation USING MESSAGE = 'Hanya usulan yang menunggu verifikasi yang dapat diproses.';
    ELSIF usulan.status NOT IN ('pending', 'diproses') THEN
        RAISE check_violation USING MESSAGE = 'Usulan ini sudah diverifikasi sebelumnya.';
    ELSIF char_length(coalesce(note, '')) > 1000 THEN
        RAISE check_violation USING MESSAGE = 'Catatan maksimal 1000 karakter.';
    ELSIF request_action = 'reject' AND note IS NULL THEN
        RAISE check_violation USING MESSAGE = 'Alasan penolakan wajib diisi.';
    ELSIF request_action = 'revision' AND note IS NULL THEN
        RAISE check_violation USING MESSAGE = 'Permintaan revisi wajib diisi.';
    ELSIF request_action = 'approve' AND NOT EXISTS (SELECT 1 FROM simbar.kategori_aset WHERE id = kategori_ref) THEN
        RAISE check_violation USING MESSAGE = 'Kategori aset wajib dipilih untuk membuat inventaris.';
    END IF;

    IF request_action = 'proses' THEN
        UPDATE simbar.usulan_penerimaan_barang SET status = 'diproses', verified_by = actor.id, updated_at = simbar.local_now() WHERE id = usulan.id;
        PERFORM simbar.write_log(actor.id, 'usulan_penerimaan_diproses', 'Admin ' || actor.name || ' memproses usulan: ' || usulan.kode_usulan);
    ELSIF request_action = 'reject' THEN
        UPDATE simbar.usulan_penerimaan_barang SET status = 'ditolak', alasan_penolakan = note, verified_by = actor.id,
            verified_at = simbar.local_now(), updated_at = simbar.local_now() WHERE id = usulan.id;
        PERFORM simbar.write_log(actor.id, 'usulan_penerimaan_rejected', 'Admin ' || actor.name || ' menolak usulan: ' || usulan.kode_usulan || '. Alasan: ' || note);
    ELSIF request_action = 'revision' THEN
        UPDATE simbar.usulan_penerimaan_barang SET status = 'revisi', permintaan_revisi = note, verified_by = actor.id,
            verified_at = simbar.local_now(), updated_at = simbar.local_now() WHERE id = usulan.id;
        PERFORM simbar.write_log(actor.id, 'usulan_penerimaan_revision_requested', 'Admin ' || actor.name || ' meminta revisi usulan: ' || usulan.kode_usulan);
    ELSE
        received := coalesce(usulan.tanggal_penerimaan_fisik, usulan.tanggal_usulan);
        first_photo := CASE WHEN usulan.foto_barang ~ '^\s*\[' THEN usulan.foto_barang::jsonb ->> 0 END;
        aset_code := simbar.next_kode_aset(kategori_ref);
        INSERT INTO simbar.barang_aset (kode_aset, nama_aset, kategori_aset_id, ruangan_id, kondisi, tanggal_masuk, tahun_perolehan,
            sumber_dana, jumlah, harga_perolehan, merk, spesifikasi, foto, keterangan, status_aset, created_by, created_at, updated_at)
        VALUES (aset_code, usulan.nama_barang, kategori_ref, usulan.ruangan_id,
            CASE usulan.kondisi_barang WHEN 'bekas_rusak_ringan' THEN 'rusak_ringan' ELSE 'baik' END,
            received, extract(year FROM received)::integer, CASE WHEN usulan.sumber_barang = 'Hibah' THEN 'Hibah' ELSE 'Lainnya' END,
            usulan.jumlah, usulan.estimasi_harga, usulan.merk, left(usulan.spesifikasi, 255), first_photo,
            'Dari usulan penerimaan #' || usulan.kode_usulan || '. ' || coalesce(usulan.keterangan_user, ''),
            'aktif', actor.id, simbar.local_now(), simbar.local_now())
        RETURNING id INTO aset_id;
        UPDATE simbar.usulan_penerimaan_barang SET status = 'disetujui', kategori_aset_id = kategori_ref, catatan_admin = note,
            verified_by = actor.id, verified_at = simbar.local_now(), barang_aset_id = aset_id, updated_at = simbar.local_now()
        WHERE id = usulan.id;
        PERFORM simbar.write_log(actor.id, 'usulan_penerimaan_approved', 'Admin ' || actor.name || ' menyetujui usulan: '
            || usulan.kode_usulan || '. Barang aset dibuat: ' || aset_code);
        RETURN aset_code;
    END IF;
    RETURN NULL;
END;
$function$;

DO $grants$
DECLARE
    signature text;
BEGIN
    FOREACH signature IN ARRAY ARRAY[
        'public.simbar_usulan_options()',
        'public.simbar_usulan_list(boolean, text, bigint, text, integer)',
        'public.simbar_usulan_get(bigint)',
        'public.simbar_usulan_save(bigint, jsonb)',
        'public.simbar_usulan_cancel(bigint)',
        'public.simbar_usulan_act(bigint, text, text, bigint)'
    ] LOOP
        EXECUTE 'REVOKE ALL ON FUNCTION ' || signature || ' FROM PUBLIC, anon, authenticated, service_role';
        EXECUTE 'GRANT EXECUTE ON FUNCTION ' || signature || ' TO authenticated';
    END LOOP;
END;
$grants$;

COMMIT;
