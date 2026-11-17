-- Foto aset: a real photo per asset, taken on the spot (scan the label QR, tap "Ambil foto"), plus an example photo
-- for assets not photographed yet: the newest real photo of another asset with the same name. Requires ops-media.sql. Safe to re-run.

BEGIN;

ALTER TABLE simbar.barang_aset ADD COLUMN IF NOT EXISTS foto_diambil_at timestamp without time zone;
ALTER TABLE simbar.barang_aset ADD COLUMN IF NOT EXISTS foto_diambil_oleh bigint REFERENCES simbar.users(id) ON DELETE SET NULL;

-- Newest real photo among assets with the same name (ignoring case and spaces), for showing as "contoh".
CREATE OR REPLACE FUNCTION simbar.aset_contoh(nama text, except_id bigint)
RETURNS jsonb
LANGUAGE sql STABLE SECURITY DEFINER SET search_path = ''
AS $function$
    SELECT jsonb_build_object('foto', a.foto, 'kode', a.kode_aset)
    FROM simbar.barang_aset a
    WHERE a.id <> except_id AND coalesce(a.foto, '') <> ''
      AND regexp_replace(lower(btrim(a.nama_aset)), '\s+', ' ', 'g') = regexp_replace(lower(btrim(nama)), '\s+', ' ', 'g')
    ORDER BY a.foto_diambil_at DESC NULLS LAST, a.updated_at DESC NULLS LAST, a.id DESC
    LIMIT 1;
$function$;

-- Save (or clear) the photo of one asset. Only the warehouse may change assets, like simbar_aset_save.
CREATE OR REPLACE FUNCTION public.simbar_aset_set_foto(aset_id bigint, reference text)
RETURNS jsonb
LANGUAGE plpgsql SECURITY DEFINER SET search_path = ''
AS $function$
DECLARE
    actor simbar.users%ROWTYPE := simbar.require_profile(ARRAY['admin', 'admin_gudang']);
    aset simbar.barang_aset%ROWTYPE;
BEGIN
    IF reference IS NOT NULL AND (char_length(reference) > 300 OR reference !~ '^(media:[0-9a-f]{64}|uploads/[A-Za-z0-9_\-/.]{1,250})$') THEN
        RAISE invalid_parameter_value USING MESSAGE = 'Referensi foto tidak valid.';
    END IF;
    PERFORM simbar.check_media_reference(actor.id, reference);
    SELECT * INTO aset FROM simbar.barang_aset WHERE id = aset_id FOR UPDATE;
    IF NOT FOUND THEN
        RAISE no_data_found USING MESSAGE = 'Aset tidak ditemukan.';
    END IF;
    UPDATE simbar.barang_aset SET foto = reference,
        foto_diambil_at = CASE WHEN reference IS NULL THEN NULL ELSE simbar.local_now() END,
        foto_diambil_oleh = CASE WHEN reference IS NULL THEN NULL ELSE actor.id END,
        updated_at = simbar.local_now()
    WHERE id = aset_id;
    PERFORM simbar.write_log(actor.id, 'aset_foto_updated', actor.name || CASE WHEN reference IS NULL THEN ' menghapus foto aset ' ELSE ' memotret aset ' END
        || aset.kode_aset || ' (' || aset.nama_aset || ')');
    RETURN jsonb_build_object('foto', reference, 'foto_diambil_at', CASE WHEN reference IS NULL THEN NULL ELSE simbar.local_now() END,
        'foto_diambil_oleh_name', CASE WHEN reference IS NULL THEN NULL ELSE actor.name END);
END;
$function$;

-- Photo progress per ruangan (assets with a real photo of their own), for the "Nomor & label" tab.
CREATE OR REPLACE FUNCTION public.simbar_aset_foto_progress()
RETURNS jsonb
LANGUAGE plpgsql STABLE SECURITY DEFINER SET search_path = ''
AS $function$
BEGIN
    PERFORM simbar.require_profile(ARRAY['admin', 'admin_gudang']);
    RETURN (SELECT jsonb_build_object(
        'total', (SELECT count(*) FROM simbar.barang_aset),
        'difoto', (SELECT count(*) FROM simbar.barang_aset WHERE coalesce(foto, '') <> ''),
        'ruangan', coalesce(jsonb_agg(jsonb_build_object('id', r.id, 'nama', r.nama_ruangan, 'total', t.total, 'difoto', t.difoto)
            ORDER BY (t.difoto::numeric / t.total), r.nama_ruangan), '[]'::jsonb))
        FROM simbar.ruangan r
        JOIN LATERAL (SELECT count(*) AS total, count(*) FILTER (WHERE coalesce(foto, '') <> '') AS difoto
                      FROM simbar.barang_aset a WHERE a.ruangan_id = r.id) t ON t.total > 0);
END;
$function$;

REVOKE ALL ON FUNCTION simbar.aset_contoh(text, bigint) FROM PUBLIC, anon, authenticated, service_role;
REVOKE ALL ON FUNCTION public.simbar_aset_set_foto(bigint, text) FROM PUBLIC, anon, authenticated, service_role;
REVOKE ALL ON FUNCTION public.simbar_aset_foto_progress() FROM PUBLIC, anon, authenticated, service_role;
GRANT EXECUTE ON FUNCTION public.simbar_aset_set_foto(bigint, text) TO authenticated;
GRANT EXECUTE ON FUNCTION public.simbar_aset_foto_progress() TO authenticated;

COMMIT;
