-- Permintaan barang (barang baru and restok) for every approved role, plus warehouse verification.
-- Ports PermintaanBarangController and the PermintaanBarang model. Safe to re-run.
-- Requires ops-core.sql and ops-stock.sql.

BEGIN;

CREATE OR REPLACE FUNCTION public.simbar_permintaan_options()
RETURNS jsonb
LANGUAGE plpgsql STABLE SECURITY DEFINER SET search_path = ''
AS $function$
BEGIN
    PERFORM simbar.require_profile(ARRAY['admin', 'admin_gudang', 'staff']);
    RETURN jsonb_build_object(
        'rooms', (SELECT coalesce(jsonb_agg(jsonb_build_object('id', id, 'name', nama_ruangan) ORDER BY lower(nama_ruangan)), '[]')
            FROM simbar.ruangan WHERE is_active),
        'items', (SELECT coalesce(jsonb_agg(jsonb_build_object('id', i.id, 'code', i.code, 'name', i.name, 'category', cat.name, 'photo', i.photo, 'stock', i.stock,
                'minimum', i.minimum_stock, 'unit', u.symbol, 'low', i.stock <= i.minimum_stock) ORDER BY lower(i.name), i.id), '[]')
            FROM simbar.items i JOIN simbar.units u ON u.id = i.unit_id LEFT JOIN simbar.categories cat ON cat.id = i.category_id),
        'categories', (SELECT coalesce(jsonb_agg(jsonb_build_object('id', id, 'name', name) ORDER BY lower(name)), '[]') FROM simbar.categories),
        'units', (SELECT coalesce(jsonb_agg(jsonb_build_object('id', id, 'name', name, 'symbol', symbol) ORDER BY lower(name)), '[]') FROM simbar.units)
    );
END;
$function$;

CREATE OR REPLACE FUNCTION public.simbar_permintaan_list(
    mine_only boolean, status_filter text DEFAULT 'all', tipe_filter text DEFAULT 'all',
    search_text text DEFAULT '', page_number integer DEFAULT 1
) RETURNS jsonb
LANGUAGE plpgsql STABLE SECURITY DEFINER SET search_path = ''
AS $function$
DECLARE
    actor simbar.users%ROWTYPE := CASE WHEN mine_only
        THEN simbar.require_profile(ARRAY['admin', 'admin_gudang', 'staff']) ELSE simbar.require_menu(ARRAY['verifikasi']) END;
    pattern text;
    result jsonb;
BEGIN
    IF status_filter IS NULL OR status_filter NOT IN ('all', 'pending', 'diproses', 'disetujui', 'ditolak', 'revisi', 'tersedia', 'dibaca') OR
       tipe_filter IS NULL OR tipe_filter NOT IN ('all', 'barang_baru', 'restok') OR
       search_text IS NULL OR char_length(search_text) > 100 OR page_number IS NULL OR page_number NOT BETWEEN 1 AND 10000 THEN
        RAISE invalid_parameter_value USING MESSAGE = 'Filter permintaan tidak valid.';
    END IF;
    pattern := '%' || replace(replace(replace(btrim(search_text), '!', '!!'), '%', '!%'), '_', '!_') || '%';

    WITH scoped AS MATERIALIZED (
        SELECT p.* FROM simbar.permintaan_barang p WHERE NOT mine_only OR p.user_id = actor.id
    ), matched AS MATERIALIZED (
        SELECT p.id, p.kode_permintaan AS code, p.tipe, p.status, p.prioritas, p.jumlah, p.nama_barang AS name, i.photo,
               p.tanggal_permintaan AS date, p.created_at, i.stock, u.symbol AS unit, r.nama_ruangan AS room,
               req.name AS requested_by, v.name AS verified_by
        FROM scoped p
        JOIN simbar.users req ON req.id = p.user_id
        LEFT JOIN simbar.items i ON i.id = p.item_id
        LEFT JOIN simbar.units u ON u.id = i.unit_id
        LEFT JOIN simbar.ruangan r ON r.id = p.ruangan_id
        LEFT JOIN simbar.users v ON v.id = p.verified_by
        WHERE (status_filter = 'all' OR p.status = status_filter)
          AND (tipe_filter = 'all' OR p.tipe = tipe_filter)
          AND (p.kode_permintaan ILIKE pattern ESCAPE '!' OR p.nama_barang ILIKE pattern ESCAPE '!'
               OR i.name ILIKE pattern ESCAPE '!' OR req.name ILIKE pattern ESCAPE '!')
    )
    SELECT jsonb_build_object(
        'counts', (SELECT jsonb_object_agg(s, (SELECT count(*) FROM scoped WHERE status = s))
                   FROM unnest(ARRAY['pending', 'diproses', 'disetujui', 'ditolak', 'revisi', 'tersedia', 'dibaca']) AS s),
        'total', (SELECT count(*) FROM matched),
        'rows', coalesce((SELECT jsonb_agg(to_jsonb(m) - 'created_at' ORDER BY m.date DESC, m.id DESC)
            FROM (SELECT * FROM matched ORDER BY date DESC, id DESC LIMIT 15 OFFSET (page_number - 1) * 15) m), '[]')
    ) INTO result;
    RETURN result;
END;
$function$;

CREATE OR REPLACE FUNCTION public.simbar_permintaan_get(permintaan_id bigint)
RETURNS jsonb
LANGUAGE plpgsql STABLE SECURITY DEFINER SET search_path = ''
AS $function$
DECLARE
    actor simbar.users%ROWTYPE := simbar.require_profile(ARRAY['admin', 'admin_gudang', 'staff']);
    result jsonb;
BEGIN
    SELECT jsonb_build_object('id', p.id, 'code', p.kode_permintaan, 'tipe', p.tipe, 'status', p.status,
            'itemId', p.item_id, 'name', p.nama_barang, 'roomId', p.ruangan_id, 'room', r.nama_ruangan, 'jumlah', p.jumlah,
            'prioritas', p.prioritas, 'keterangan', p.keterangan, 'date', p.tanggal_permintaan,
            'catatanAdmin', p.catatan_admin, 'alasanPenolakan', p.alasan_penolakan, 'permintaanRevisi', p.permintaan_revisi,
            'requestedBy', req.name, 'verifiedBy', v.name, 'verifiedAt', p.verified_at,
            'foto', CASE WHEN p.tipe_foto_barang = 'url' THEN p.foto_barang END,
            'fotoRef', CASE WHEN p.foto_barang LIKE 'media:%' THEN p.foto_barang END,
            'hasUploadedPhoto', p.tipe_foto_barang = 'upload' AND p.foto_barang IS NOT NULL AND p.foto_barang NOT LIKE 'media:%',
            'item', CASE WHEN i.id IS NOT NULL THEN jsonb_build_object('name', i.name, 'code', i.code, 'stock', i.stock, 'photo', i.photo,
                'minimum', i.minimum_stock, 'unit', u.symbol) END,
            'createdItem', (SELECT jsonb_build_object('code', c.code, 'name', c.name) FROM simbar.items c WHERE c.id = p.created_item_id),
            'mine', p.user_id = actor.id)
    INTO result
    FROM simbar.permintaan_barang p
    JOIN simbar.users req ON req.id = p.user_id
    LEFT JOIN simbar.items i ON i.id = p.item_id
    LEFT JOIN simbar.units u ON u.id = i.unit_id
    LEFT JOIN simbar.ruangan r ON r.id = p.ruangan_id
    LEFT JOIN simbar.users v ON v.id = p.verified_by
    WHERE p.id = permintaan_id AND (p.user_id = actor.id OR simbar.has_menu(actor.role, ARRAY['verifikasi']));
    IF result IS NULL THEN
        RAISE no_data_found USING MESSAGE = 'Permintaan tidak ditemukan.';
    END IF;
    RETURN result;
END;
$function$;

-- permintaan_id NULL submits a new request; otherwise the owner edits a pending/revisi request,
-- which goes back to pending. foto_url is a link or an uploaded "media:" reference; remove_photo clears the photo.
CREATE OR REPLACE FUNCTION public.simbar_permintaan_save(
    permintaan_id bigint, request_tipe text, item_ref bigint, request_name text, room_ref bigint,
    request_jumlah integer, request_prioritas text, request_keterangan text DEFAULT NULL,
    foto_url text DEFAULT NULL, remove_photo boolean DEFAULT false
) RETURNS text
LANGUAGE plpgsql SECURITY DEFINER SET search_path = ''
AS $function$
DECLARE
    actor simbar.users%ROWTYPE := simbar.require_profile(ARRAY['admin', 'admin_gudang', 'staff']);
    current simbar.permintaan_barang%ROWTYPE;
    item simbar.items%ROWTYPE;
    duplicate text;
    code text;
    month_prefix text := 'PBR-' || to_char(simbar.local_today(), 'YYYYMM') || '-';
BEGIN
    request_name := btrim(request_name);
    request_keterangan := nullif(btrim(request_keterangan), '');
    foto_url := nullif(btrim(foto_url), '');

    IF request_tipe IS NULL OR request_tipe NOT IN ('barang_baru', 'restok') THEN
        RAISE check_violation USING MESSAGE = 'Jenis permintaan tidak valid.';
    ELSIF request_prioritas IS NULL OR request_prioritas NOT IN ('rendah', 'sedang', 'tinggi') THEN
        RAISE check_violation USING MESSAGE = 'Prioritas wajib dipilih.';
    ELSIF request_jumlah IS NULL OR request_jumlah < 1 OR request_jumlah > (CASE request_tipe WHEN 'restok' THEN 99999 ELSE 9999 END) THEN
        RAISE check_violation USING MESSAGE = 'Jumlah minimal 1.';
    ELSIF char_length(coalesce(request_keterangan, '')) > 2000 THEN
        RAISE check_violation USING MESSAGE = 'Keterangan maksimal 2000 karakter.';
    ELSIF foto_url IS NOT NULL AND ((foto_url !~ '^https?://[^\s]+$' AND foto_url !~ '^media:[0-9a-f]{64}$') OR char_length(foto_url) > 2000) THEN
        RAISE check_violation USING MESSAGE = 'URL gambar tidak valid.';
    END IF;

    IF foto_url LIKE 'media:%' THEN
        PERFORM simbar.check_media_reference(actor.id, foto_url);
    END IF;

    IF permintaan_id IS NOT NULL THEN
        SELECT * INTO current FROM simbar.permintaan_barang
        WHERE id = permintaan_id AND user_id = actor.id AND status IN ('pending', 'revisi') FOR UPDATE;
        IF NOT FOUND THEN
            RAISE no_data_found USING MESSAGE = 'Permintaan tidak ditemukan atau tidak dapat diubah.';
        END IF;
    END IF;

    IF request_tipe = 'restok' THEN
        SELECT * INTO item FROM simbar.items WHERE id = item_ref;
        IF NOT FOUND THEN
            RAISE check_violation USING MESSAGE = 'Barang harus dipilih.';
        END IF;
        request_name := item.name;
        room_ref := NULL;
        IF permintaan_id IS NULL THEN
            IF item.stock > item.minimum_stock THEN
                RAISE check_violation USING MESSAGE = 'Barang "' || item.name || '" masih memiliki stok yang cukup (' || item.stock
                    || '). Permintaan restok hanya untuk barang yang stoknya habis atau menipis.';
            END IF;
            SELECT kode_permintaan INTO duplicate FROM simbar.permintaan_barang
            WHERE user_id = actor.id AND item_id = item.id AND tipe = 'restok' AND status IN ('pending', 'disetujui') LIMIT 1;
            IF duplicate IS NOT NULL THEN
                RAISE unique_violation USING MESSAGE = 'Anda sudah memiliki permintaan restok yang masih aktif untuk barang ini. Kode: ' || duplicate;
            END IF;
        END IF;
    ELSE
        item_ref := NULL;
        IF coalesce(request_name, '') = '' OR char_length(request_name) > 255 THEN
            RAISE check_violation USING MESSAGE = 'Nama barang wajib diisi.';
        ELSIF room_ref IS NOT NULL AND NOT EXISTS (SELECT 1 FROM simbar.ruangan WHERE id = room_ref) THEN
            RAISE check_violation USING MESSAGE = 'Ruangan tidak ditemukan.';
        END IF;
        IF permintaan_id IS NULL THEN
            SELECT kode_permintaan INTO duplicate FROM simbar.permintaan_barang
            WHERE user_id = actor.id AND nama_barang = request_name AND tipe = 'barang_baru'
              AND status IN ('pending', 'diproses', 'revisi') AND (room_ref IS NULL OR ruangan_id = room_ref) LIMIT 1;
            IF duplicate IS NOT NULL THEN
                RAISE unique_violation USING MESSAGE = 'Anda sudah memiliki permintaan pending untuk barang yang sama. Kode: ' || duplicate;
            END IF;
        END IF;
    END IF;

    IF permintaan_id IS NULL THEN
        -- Serialise code generation so two requests in the same month never get the same number.
        PERFORM pg_advisory_xact_lock(hashtext('simbar.permintaan_barang.kode'));
        SELECT month_prefix || lpad((coalesce(max(right(kode_permintaan, 4)::integer), 0) + 1)::text, 4, '0') INTO code
        FROM simbar.permintaan_barang WHERE kode_permintaan LIKE month_prefix || '____' AND right(kode_permintaan, 4) ~ '^[0-9]{4}$';

        INSERT INTO simbar.permintaan_barang (kode_permintaan, user_id, tipe, item_id, nama_barang, ruangan_id, jumlah,
            prioritas, keterangan, tanggal_permintaan, status, foto_barang, tipe_foto_barang, created_at, updated_at)
        VALUES (code, actor.id, request_tipe, item_ref, request_name, room_ref, request_jumlah, request_prioritas,
            request_keterangan, simbar.local_today(), 'pending', foto_url, CASE WHEN foto_url LIKE 'media:%' THEN 'upload' WHEN foto_url IS NOT NULL THEN 'url' END,
            simbar.local_now(), simbar.local_now());
        PERFORM simbar.write_log(actor.id, CASE request_tipe WHEN 'restok' THEN 'permintaan_barang_restok_created' ELSE 'permintaan_barang_baru_created' END,
            'User ' || actor.name || ' mengajukan ' || CASE request_tipe WHEN 'restok' THEN 'restok barang: ' ELSE 'permintaan barang baru: ' END
            || request_name || ' (' || code || ')');
        RETURN code;
    END IF;

    UPDATE simbar.permintaan_barang SET tipe = request_tipe, item_id = item_ref, nama_barang = request_name,
        ruangan_id = room_ref, jumlah = request_jumlah, prioritas = request_prioritas, keterangan = request_keterangan,
        status = 'pending', permintaan_revisi = NULL, updated_at = simbar.local_now(),
        foto_barang = CASE WHEN remove_photo THEN NULL WHEN foto_url IS NOT NULL THEN foto_url ELSE foto_barang END,
        tipe_foto_barang = CASE WHEN remove_photo THEN NULL WHEN foto_url LIKE 'media:%' THEN 'upload' WHEN foto_url IS NOT NULL THEN 'url' ELSE tipe_foto_barang END
    WHERE id = permintaan_id;
    PERFORM simbar.write_log(actor.id, 'permintaan_barang_updated', 'User ' || actor.name || ' memperbarui permintaan: ' || current.kode_permintaan);
    RETURN current.kode_permintaan;
END;
$function$;

CREATE OR REPLACE FUNCTION public.simbar_permintaan_cancel(permintaan_id bigint)
RETURNS void
LANGUAGE plpgsql SECURITY DEFINER SET search_path = ''
AS $function$
DECLARE
    actor simbar.users%ROWTYPE := simbar.require_profile(ARRAY['admin', 'admin_gudang', 'staff']);
    removed simbar.permintaan_barang%ROWTYPE;
BEGIN
    DELETE FROM simbar.permintaan_barang
    WHERE id = permintaan_id AND user_id = actor.id AND status IN ('pending', 'revisi')
    RETURNING * INTO removed;
    IF removed.id IS NULL THEN
        RAISE no_data_found USING MESSAGE = 'Permintaan tidak ditemukan atau tidak dapat dibatalkan.';
    END IF;
    PERFORM simbar.write_log(actor.id, 'permintaan_barang_cancelled', 'User ' || actor.name || ' membatalkan permintaan: '
        || removed.kode_permintaan || ' (' || removed.nama_barang || ')');
END;
$function$;

-- request_action: proses, approve, reject, revision. Approving barang baru creates the barang from new_item
-- ({"code", "category_id", "unit_id", "price", "perolehan", "minimum_stock"}) with the requested jumlah as stock.
CREATE OR REPLACE FUNCTION public.simbar_permintaan_act(
    permintaan_id bigint, request_action text, note text DEFAULT NULL, new_item jsonb DEFAULT NULL
) RETURNS text
LANGUAGE plpgsql SECURITY DEFINER SET search_path = ''
AS $function$
DECLARE
    actor simbar.users%ROWTYPE := simbar.require_menu(ARRAY['verifikasi']);
    request simbar.permintaan_barang%ROWTYPE;
    item_code text;
    created bigint;
BEGIN
    note := nullif(btrim(note), '');
    SELECT * INTO request FROM simbar.permintaan_barang WHERE id = permintaan_id FOR UPDATE;
    IF NOT FOUND THEN
        RAISE no_data_found USING MESSAGE = 'Permintaan tidak ditemukan.';
    ELSIF request_action IS NULL OR request_action NOT IN ('proses', 'approve', 'reject', 'revision') THEN
        RAISE invalid_parameter_value USING MESSAGE = 'Aksi permintaan tidak valid.';
    ELSIF request_action = 'proses' AND request.status <> 'pending' THEN
        RAISE check_violation USING MESSAGE = 'Hanya permintaan yang menunggu verifikasi yang dapat diproses.';
    ELSIF request.status NOT IN ('pending', 'diproses') THEN
        RAISE check_violation USING MESSAGE = 'Permintaan ini sudah diverifikasi sebelumnya.';
    ELSIF char_length(coalesce(note, '')) > 1000 THEN
        RAISE check_violation USING MESSAGE = 'Catatan maksimal 1000 karakter.';
    ELSIF request_action = 'reject' AND note IS NULL THEN
        RAISE check_violation USING MESSAGE = 'Alasan penolakan wajib diisi.';
    ELSIF request_action = 'revision' AND note IS NULL THEN
        RAISE check_violation USING MESSAGE = 'Permintaan revisi wajib diisi.';
    END IF;

    IF request_action = 'proses' THEN
        UPDATE simbar.permintaan_barang SET status = 'diproses', verified_by = actor.id, updated_at = simbar.local_now() WHERE id = request.id;
        PERFORM simbar.write_log(actor.id, 'permintaan_barang_diproses', 'Admin ' || actor.name || ' memproses permintaan: ' || request.kode_permintaan);
    ELSIF request_action = 'reject' THEN
        UPDATE simbar.permintaan_barang SET status = 'ditolak', alasan_penolakan = note, verified_by = actor.id,
            verified_at = simbar.local_now(), updated_at = simbar.local_now() WHERE id = request.id;
        PERFORM simbar.write_log(actor.id, 'permintaan_barang_rejected', 'Admin ' || actor.name || ' menolak permintaan: ' || request.kode_permintaan);
    ELSIF request_action = 'revision' THEN
        UPDATE simbar.permintaan_barang SET status = 'revisi', permintaan_revisi = note, verified_by = actor.id,
            verified_at = simbar.local_now(), updated_at = simbar.local_now() WHERE id = request.id;
        PERFORM simbar.write_log(actor.id, 'permintaan_barang_revision_requested', 'Admin ' || actor.name || ' meminta revisi permintaan: ' || request.kode_permintaan);
    ELSE
        UPDATE simbar.permintaan_barang SET status = 'disetujui', catatan_admin = note, verified_by = actor.id,
            verified_at = simbar.local_now(), updated_at = simbar.local_now() WHERE id = request.id;
        IF request.tipe = 'barang_baru' THEN
            IF new_item IS NULL OR jsonb_typeof(new_item) <> 'object' THEN
                RAISE check_violation USING MESSAGE = 'Lengkapi data barang baru sebelum menyetujui.';
            END IF;
            -- simbar_item_save validates the code, category, unit, price and perolehan, and logs the new barang.
            PERFORM set_config('simbar.via', 'verifikasi', true);
            created := public.simbar_item_save(NULL, new_item->>'code', request.nama_barang, (new_item->>'category_id')::bigint,
                (new_item->>'unit_id')::bigint, new_item->>'perolehan', (new_item->>'price')::numeric,
                coalesce((new_item->>'minimum_stock')::integer, 5),
                'Dari permintaan barang #' || request.kode_permintaan || '. ' || coalesce(request.keterangan, ''), NULL, request.jumlah);
            PERFORM set_config('simbar.via', '', true);
            UPDATE simbar.permintaan_barang SET created_item_id = created WHERE id = request.id;
            SELECT code INTO item_code FROM simbar.items WHERE id = created;
            PERFORM simbar.write_log(actor.id, 'permintaan_barang_baru_approved', 'Admin ' || actor.name
                || ' menyetujui permintaan barang baru: ' || request.kode_permintaan || '. Data barang dibuat: ' || item_code);
            RETURN item_code;
        END IF;
        PERFORM simbar.write_log(actor.id, 'permintaan_barang_restok_approved', 'Admin ' || actor.name
            || ' menyetujui permintaan restok: ' || request.kode_permintaan || ' (' || request.nama_barang || ')');
    END IF;
    RETURN NULL;
END;
$function$;

-- Restok requests of the caller that became available and were not yet acknowledged.
CREATE OR REPLACE FUNCTION public.simbar_permintaan_notifications()
RETURNS jsonb
LANGUAGE plpgsql STABLE SECURITY DEFINER SET search_path = ''
AS $function$
DECLARE
    actor simbar.users%ROWTYPE := simbar.require_profile(ARRAY['admin', 'admin_gudang', 'staff']);
BEGIN
    RETURN (SELECT coalesce(jsonb_agg(jsonb_build_object('id', p.id, 'code', p.kode_permintaan, 'name', coalesce(i.name, p.nama_barang), 'photo', i.photo,
            'jumlah', p.jumlah, 'stock', coalesce(i.stock, 0)) ORDER BY p.updated_at DESC NULLS LAST, p.id DESC), '[]')
        FROM simbar.permintaan_barang p LEFT JOIN simbar.items i ON i.id = p.item_id
        WHERE p.user_id = actor.id AND p.tipe = 'restok' AND p.status = 'tersedia' AND p.notified_at IS NULL);
END;
$function$;

CREATE OR REPLACE FUNCTION public.simbar_permintaan_mark_read()
RETURNS integer
LANGUAGE plpgsql SECURITY DEFINER SET search_path = ''
AS $function$
DECLARE
    actor simbar.users%ROWTYPE := simbar.require_profile(ARRAY['admin', 'admin_gudang', 'staff']);
    changed integer;
BEGIN
    UPDATE simbar.permintaan_barang SET status = 'dibaca', notified_at = simbar.local_now(), updated_at = simbar.local_now()
    WHERE user_id = actor.id AND status = 'tersedia' AND notified_at IS NULL;
    GET DIAGNOSTICS changed = ROW_COUNT;
    RETURN changed;
END;
$function$;

DO $grants$
DECLARE
    signature text;
BEGIN
    FOREACH signature IN ARRAY ARRAY[
        'public.simbar_permintaan_options()',
        'public.simbar_permintaan_list(boolean, text, text, text, integer)',
        'public.simbar_permintaan_get(bigint)',
        'public.simbar_permintaan_save(bigint, text, bigint, text, bigint, integer, text, text, text, boolean)',
        'public.simbar_permintaan_cancel(bigint)',
        'public.simbar_permintaan_act(bigint, text, text, jsonb)',
        'public.simbar_permintaan_notifications()',
        'public.simbar_permintaan_mark_read()'
    ] LOOP
        EXECUTE 'REVOKE ALL ON FUNCTION ' || signature || ' FROM PUBLIC, anon, authenticated, service_role';
        EXECUTE 'GRANT EXECUTE ON FUNCTION ' || signature || ' TO authenticated';
    END LOOP;
END;
$grants$;

COMMIT;
