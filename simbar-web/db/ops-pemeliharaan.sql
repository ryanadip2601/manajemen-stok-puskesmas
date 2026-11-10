-- Pemeliharaan aset. Safe to re-run. Requires ops-core.sql and ops-media.sql.
-- Ports PemeliharaanAsetController: every role reports maintenance (staff see only their own reports);
-- admin and admin_gudang update, change status, and delete. Status changes update the aset condition.

BEGIN;

CREATE OR REPLACE FUNCTION public.simbar_pemeliharaan_options()
RETURNS jsonb
LANGUAGE plpgsql STABLE SECURITY DEFINER SET search_path = ''
AS $function$
BEGIN
    PERFORM simbar.require_profile(ARRAY['admin', 'admin_gudang', 'staff']);
    RETURN jsonb_build_object(
        'rooms', (SELECT coalesce(jsonb_agg(jsonb_build_object('id', id, 'name', nama_ruangan) ORDER BY lower(nama_ruangan)), '[]')
            FROM simbar.ruangan WHERE is_active),
        'assets', (SELECT coalesce(jsonb_agg(jsonb_build_object('id', id, 'code', kode_aset, 'name', nama_aset, 'roomId', ruangan_id,
                'kondisi', kondisi) ORDER BY lower(nama_aset), id), '[]') FROM simbar.barang_aset)
    );
END;
$function$;

-- filters: {search, ruangan, status, jenis, teknisi, dari, sampai, aset}. page_number 0 returns every match.
CREATE OR REPLACE FUNCTION public.simbar_pemeliharaan_list(filters jsonb DEFAULT '{}', page_number integer DEFAULT 1)
RETURNS jsonb
LANGUAGE plpgsql STABLE SECURITY DEFINER SET search_path = ''
AS $function$
DECLARE
    actor simbar.users%ROWTYPE := simbar.require_profile(ARRAY['admin', 'admin_gudang', 'staff']);
    search_text text := btrim(coalesce(filters->>'search', ''));
    pattern text;
    result jsonb;
BEGIN
    IF char_length(search_text) > 100 OR char_length(coalesce(filters->>'teknisi', '')) > 100 OR
       page_number IS NULL OR page_number NOT BETWEEN 0 AND 10000 THEN
        RAISE invalid_parameter_value USING MESSAGE = 'Filter pemeliharaan tidak valid.';
    END IF;
    pattern := '%' || replace(replace(replace(search_text, '!', '!!'), '%', '!%'), '_', '!_') || '%';

    WITH scoped AS MATERIALIZED (
        SELECT p.* FROM simbar.pemeliharaan_aset p WHERE actor.role <> 'staff' OR p.created_by = actor.id
    ), matched AS MATERIALIZED (
        SELECT p.id, p.tanggal_pemeliharaan AS date, p.jenis_pemeliharaan AS jenis, p.status, p.teknisi, p.biaya,
               p.deskripsi_masalah AS deskripsi, p.tindakan_perbaikan AS tindakan, p.tanggal_selesai AS selesai,
               a.kode_aset AS code, a.nama_aset AS name, r.nama_ruangan AS room, p.lampiran_foto IS NOT NULL AS has_photo
        FROM scoped p
        JOIN simbar.barang_aset a ON a.id = p.barang_aset_id
        JOIN simbar.ruangan r ON r.id = p.ruangan_id
        WHERE (p.teknisi ILIKE pattern ESCAPE '!' OR p.deskripsi_masalah ILIKE pattern ESCAPE '!'
               OR a.nama_aset ILIKE pattern ESCAPE '!' OR a.kode_aset ILIKE pattern ESCAPE '!')
          AND (nullif(filters->>'ruangan', '') IS NULL OR p.ruangan_id = (filters->>'ruangan')::bigint)
          AND (nullif(filters->>'aset', '') IS NULL OR p.barang_aset_id = (filters->>'aset')::bigint)
          AND (nullif(filters->>'status', '') IS NULL OR p.status = filters->>'status')
          AND (nullif(filters->>'jenis', '') IS NULL OR p.jenis_pemeliharaan = filters->>'jenis')
          AND (nullif(filters->>'teknisi', '') IS NULL OR p.teknisi ILIKE '%' || replace(replace(replace(filters->>'teknisi', '!', '!!'), '%', '!%'), '_', '!_') || '%' ESCAPE '!')
          AND (nullif(filters->>'dari', '') IS NULL OR p.tanggal_pemeliharaan >= (filters->>'dari')::date)
          AND (nullif(filters->>'sampai', '') IS NULL OR p.tanggal_pemeliharaan <= (filters->>'sampai')::date)
    )
    SELECT jsonb_build_object(
        'counts', (SELECT jsonb_build_object('total', count(*), 'menunggu', count(*) FILTER (WHERE status = 'menunggu'),
                'proses', count(*) FILTER (WHERE status = 'proses'), 'selesai', count(*) FILTER (WHERE status = 'selesai'),
                'rusak', count(*) FILTER (WHERE status = 'rusak')) FROM scoped),
        'total', (SELECT count(*) FROM matched),
        'rows', coalesce((SELECT jsonb_agg(to_jsonb(m) ORDER BY m.date DESC, m.id DESC)
            FROM (SELECT * FROM matched ORDER BY date DESC, id DESC
                  LIMIT CASE WHEN page_number = 0 THEN NULL ELSE 15 END
                  OFFSET CASE WHEN page_number = 0 THEN 0 ELSE (page_number - 1) * 15 END) m), '[]')
    ) INTO result;
    RETURN result;
END;
$function$;

CREATE OR REPLACE FUNCTION public.simbar_pemeliharaan_get(pemeliharaan_id bigint)
RETURNS jsonb
LANGUAGE plpgsql STABLE SECURITY DEFINER SET search_path = ''
AS $function$
DECLARE
    actor simbar.users%ROWTYPE := simbar.require_profile(ARRAY['admin', 'admin_gudang', 'staff']);
    result jsonb;
BEGIN
    SELECT to_jsonb(p) || jsonb_build_object('kode_aset', a.kode_aset, 'nama_aset', a.nama_aset, 'kondisi_aset', a.kondisi,
            'kategori', k.nama_kategori, 'ruangan', r.nama_ruangan, 'created_by_name', u.name)
    INTO result
    FROM simbar.pemeliharaan_aset p
    JOIN simbar.barang_aset a ON a.id = p.barang_aset_id
    JOIN simbar.kategori_aset k ON k.id = a.kategori_aset_id
    JOIN simbar.ruangan r ON r.id = p.ruangan_id
    LEFT JOIN simbar.users u ON u.id = p.created_by
    WHERE p.id = pemeliharaan_id AND (actor.role <> 'staff' OR p.created_by = actor.id);
    IF result IS NULL THEN
        RAISE no_data_found USING MESSAGE = 'Laporan pemeliharaan tidak ditemukan.';
    END IF;
    RETURN result;
END;
$function$;

-- Applies the aset side effects of a pemeliharaan status (selesai -> baik, rusak -> rusak berat).
CREATE OR REPLACE FUNCTION simbar.apply_pemeliharaan_status(aset_ref bigint, new_status text)
RETURNS void
LANGUAGE sql SECURITY DEFINER SET search_path = ''
AS $function$
    UPDATE simbar.barang_aset SET
        kondisi = CASE new_status WHEN 'selesai' THEN 'baik' ELSE 'rusak_berat' END,
        butuh_perbaikan = new_status = 'rusak', updated_at = simbar.local_now()
    WHERE id = aset_ref AND new_status IN ('selesai', 'rusak');
$function$;

-- pemeliharaan_id NULL reports new maintenance (any role); editing is for admin and admin_gudang.
-- form: {barang_aset_id, ruangan_id, tanggal_pemeliharaan, jenis_pemeliharaan, deskripsi_masalah, tindakan_perbaikan,
--        teknisi, biaya, catatan, status, tanggal_selesai, lampiran_foto (omit to keep, null to remove)}
CREATE OR REPLACE FUNCTION public.simbar_pemeliharaan_save(pemeliharaan_id bigint, form jsonb)
RETURNS bigint
LANGUAGE plpgsql SECURITY DEFINER SET search_path = ''
AS $function$
DECLARE
    actor simbar.users%ROWTYPE := simbar.require_profile(CASE WHEN pemeliharaan_id IS NULL
        THEN ARRAY['admin', 'admin_gudang', 'staff'] ELSE ARRAY['admin', 'admin_gudang'] END);
    current simbar.pemeliharaan_aset%ROWTYPE;
    aset_ref bigint := nullif(form->>'barang_aset_id', '')::bigint;
    room_ref bigint := nullif(form->>'ruangan_id', '')::bigint;
    tanggal date := nullif(form->>'tanggal_pemeliharaan', '')::date;
    jenis text := form->>'jenis_pemeliharaan';
    deskripsi text := nullif(btrim(form->>'deskripsi_masalah'), '');
    tech text := nullif(btrim(form->>'teknisi'), '');
    cost numeric := nullif(form->>'biaya', '')::numeric;
    new_status text := coalesce(form->>'status', 'menunggu');
    finished date := nullif(form->>'tanggal_selesai', '')::date;
    photo text;
    aset_name text;
    saved bigint;
BEGIN
    IF pemeliharaan_id IS NOT NULL THEN
        SELECT * INTO current FROM simbar.pemeliharaan_aset WHERE id = pemeliharaan_id FOR UPDATE;
        IF NOT FOUND THEN
            RAISE no_data_found USING MESSAGE = 'Laporan pemeliharaan tidak ditemukan.';
        END IF;
    END IF;
    photo := CASE WHEN form ? 'lampiran_foto' THEN nullif(form->>'lampiran_foto', '') ELSE current.lampiran_foto END;
    SELECT nama_aset INTO aset_name FROM simbar.barang_aset WHERE id = aset_ref;

    IF aset_name IS NULL THEN
        RAISE check_violation USING MESSAGE = 'Barang aset harus dipilih.';
    ELSIF room_ref IS NULL OR NOT EXISTS (SELECT 1 FROM simbar.ruangan WHERE id = room_ref) THEN
        RAISE check_violation USING MESSAGE = 'Ruangan harus dipilih.';
    ELSIF tanggal IS NULL THEN
        RAISE check_violation USING MESSAGE = 'Tanggal pemeliharaan harus diisi.';
    ELSIF jenis IS NULL OR jenis NOT IN ('rutinitas', 'perbaikan', 'penggantian') THEN
        RAISE check_violation USING MESSAGE = 'Jenis pemeliharaan harus dipilih.';
    ELSIF deskripsi IS NULL THEN
        RAISE check_violation USING MESSAGE = 'Deskripsi masalah harus diisi.';
    ELSIF tech IS NULL OR char_length(tech) > 100 THEN
        RAISE check_violation USING MESSAGE = 'Nama teknisi harus diisi (maksimal 100 karakter).';
    ELSIF cost < 0 THEN
        RAISE check_violation USING MESSAGE = 'Biaya tidak boleh negatif.';
    ELSIF new_status NOT IN ('menunggu', 'proses', 'selesai', 'rusak') THEN
        RAISE check_violation USING MESSAGE = 'Status tidak valid.';
    END IF;
    IF photo IS DISTINCT FROM current.lampiran_foto THEN
        PERFORM simbar.check_media_reference(actor.id, photo);
    END IF;

    IF pemeliharaan_id IS NULL THEN
        INSERT INTO simbar.pemeliharaan_aset (barang_aset_id, ruangan_id, tanggal_pemeliharaan, jenis_pemeliharaan, deskripsi_masalah,
            tindakan_perbaikan, status, teknisi, biaya, lampiran_foto, catatan, created_by, created_at, updated_at)
        VALUES (aset_ref, room_ref, tanggal, jenis, deskripsi, nullif(btrim(form->>'tindakan_perbaikan'), ''), 'menunggu', tech, cost,
            photo, nullif(btrim(form->>'catatan'), ''), actor.id, simbar.local_now(), simbar.local_now())
        RETURNING id INTO saved;
        IF jenis = 'perbaikan' THEN
            UPDATE simbar.barang_aset SET butuh_perbaikan = true, updated_at = simbar.local_now() WHERE id = aset_ref;
        END IF;
        PERFORM simbar.write_log(actor.id, 'pemeliharaan_created', 'Pemeliharaan untuk aset "' || aset_name || '" dibuat oleh teknisi ' || tech);
        RETURN saved;
    END IF;

    IF new_status = 'selesai' AND finished IS NULL THEN
        finished := simbar.local_today();
    END IF;
    UPDATE simbar.pemeliharaan_aset SET barang_aset_id = aset_ref, ruangan_id = room_ref, tanggal_pemeliharaan = tanggal,
        jenis_pemeliharaan = jenis, deskripsi_masalah = deskripsi, tindakan_perbaikan = nullif(btrim(form->>'tindakan_perbaikan'), ''),
        status = new_status, teknisi = tech, biaya = cost, lampiran_foto = photo, tanggal_selesai = finished,
        catatan = nullif(btrim(form->>'catatan'), ''), updated_at = simbar.local_now()
    WHERE id = pemeliharaan_id;
    PERFORM simbar.apply_pemeliharaan_status(aset_ref, new_status);
    PERFORM simbar.write_log(actor.id, 'pemeliharaan_updated', 'Pemeliharaan #' || pemeliharaan_id || ' diupdate, status: ' || new_status);
    RETURN pemeliharaan_id;
END;
$function$;

CREATE OR REPLACE FUNCTION public.simbar_pemeliharaan_status(pemeliharaan_id bigint, new_status text, tindakan text DEFAULT NULL)
RETURNS void
LANGUAGE plpgsql SECURITY DEFINER SET search_path = ''
AS $function$
DECLARE
    actor simbar.users%ROWTYPE := simbar.require_profile(ARRAY['admin', 'admin_gudang']);
    current simbar.pemeliharaan_aset%ROWTYPE;
BEGIN
    IF new_status IS NULL OR new_status NOT IN ('menunggu', 'proses', 'selesai', 'rusak') THEN
        RAISE check_violation USING MESSAGE = 'Status tidak valid.';
    END IF;
    SELECT * INTO current FROM simbar.pemeliharaan_aset WHERE id = pemeliharaan_id FOR UPDATE;
    IF NOT FOUND THEN
        RAISE no_data_found USING MESSAGE = 'Laporan pemeliharaan tidak ditemukan.';
    END IF;
    UPDATE simbar.pemeliharaan_aset SET status = new_status,
        tindakan_perbaikan = CASE WHEN new_status = 'selesai' THEN coalesce(nullif(btrim(tindakan), ''), tindakan_perbaikan) ELSE tindakan_perbaikan END,
        tanggal_selesai = CASE WHEN new_status = 'selesai' THEN simbar.local_today() ELSE tanggal_selesai END,
        updated_at = simbar.local_now()
    WHERE id = pemeliharaan_id;
    PERFORM simbar.apply_pemeliharaan_status(current.barang_aset_id, new_status);
    PERFORM simbar.write_log(actor.id, 'pemeliharaan_updated', 'Pemeliharaan #' || pemeliharaan_id || ' diupdate, status: ' || new_status);
END;
$function$;

CREATE OR REPLACE FUNCTION public.simbar_pemeliharaan_delete(pemeliharaan_id bigint)
RETURNS void
LANGUAGE plpgsql SECURITY DEFINER SET search_path = ''
AS $function$
DECLARE
    actor simbar.users%ROWTYPE := simbar.require_profile(ARRAY['admin', 'admin_gudang']);
BEGIN
    DELETE FROM simbar.pemeliharaan_aset WHERE id = pemeliharaan_id;
    IF NOT FOUND THEN
        RAISE no_data_found USING MESSAGE = 'Laporan pemeliharaan tidak ditemukan.';
    END IF;
    PERFORM simbar.write_log(actor.id, 'pemeliharaan_deleted', 'Pemeliharaan #' || pemeliharaan_id || ' dihapus');
END;
$function$;

REVOKE ALL ON FUNCTION simbar.apply_pemeliharaan_status(bigint, text) FROM PUBLIC, anon, authenticated, service_role;

DO $grants$
DECLARE
    signature text;
BEGIN
    FOREACH signature IN ARRAY ARRAY[
        'public.simbar_pemeliharaan_options()',
        'public.simbar_pemeliharaan_list(jsonb, integer)',
        'public.simbar_pemeliharaan_get(bigint)',
        'public.simbar_pemeliharaan_save(bigint, jsonb)',
        'public.simbar_pemeliharaan_status(bigint, text, text)',
        'public.simbar_pemeliharaan_delete(bigint)'
    ] LOOP
        EXECUTE 'REVOKE ALL ON FUNCTION ' || signature || ' FROM PUBLIC, anon, authenticated, service_role';
        EXECUTE 'GRANT EXECUTE ON FUNCTION ' || signature || ' TO authenticated';
    END LOOP;
END;
$grants$;

COMMIT;
