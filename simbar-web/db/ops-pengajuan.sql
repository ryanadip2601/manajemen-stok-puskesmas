-- "Pengajuan saya": one list of the caller's own pengambilan, permintaan, usulan and laporan pemeliharaan, for the Pengajuan page.
-- Only the kinds the caller's role may use (Atur Menu rights pickup / permintaan / usulan / pemeliharaan) are listed.
-- Every status is also put in one of five buckets so the page can filter across kinds. Safe to re-run.

BEGIN;

CREATE OR REPLACE FUNCTION public.simbar_my_requests(
    kind_filter text DEFAULT 'all', bucket_filter text DEFAULT 'all', search_text text DEFAULT '', page_number integer DEFAULT 1
) RETURNS jsonb
LANGUAGE plpgsql STABLE SECURITY DEFINER SET search_path = ''
AS $function$
DECLARE
    actor simbar.users%ROWTYPE := simbar.require_profile(ARRAY['admin', 'admin_gudang', 'staff']);
    kinds text[];
    pattern text;
    result jsonb;
BEGIN
    IF kind_filter IS NULL OR kind_filter NOT IN ('all', 'pickup', 'permintaan', 'usulan', 'pemeliharaan') OR
       bucket_filter IS NULL OR bucket_filter NOT IN ('all', 'menunggu', 'revisi', 'disetujui', 'ditolak', 'dibatalkan') OR
       search_text IS NULL OR char_length(search_text) > 100 OR page_number IS NULL OR page_number NOT BETWEEN 1 AND 10000 THEN
        RAISE invalid_parameter_value USING MESSAGE = 'Filter pengajuan tidak valid.';
    END IF;
    kinds := ARRAY(SELECT k FROM unnest(ARRAY['pickup', 'permintaan', 'usulan', 'pemeliharaan']) AS k WHERE simbar.has_menu(actor.role, ARRAY[k]));
    pattern := '%' || replace(replace(replace(btrim(search_text), '!', '!!'), '%', '!%'), '_', '!_') || '%';

    WITH mine AS MATERIALIZED (
        SELECT 'pickup'::text AS kind, r.id, NULL::text AS code, i.name::text AS title, r.quantity AS jumlah, un.symbol::text AS satuan,
               r.request_date AS tanggal, r.status::text AS status,
               CASE r.status WHEN 'pending' THEN 'menunggu' WHEN 'rejected' THEN 'ditolak' WHEN 'cancelled' THEN 'dibatalkan' ELSE 'disetujui' END AS bucket,
               CASE WHEN r.status = 'rejected' THEN r.reject_reason ELSE r.notes END AS note, r.created_at,
               i.photo::text AS photo, NULL::text AS aset_foto
        FROM simbar.stock_out_requests r
        JOIN simbar.items i ON i.id = r.item_id
        LEFT JOIN simbar.units un ON un.id = i.unit_id
        WHERE r.user_id = actor.id
        UNION ALL
        SELECT 'permintaan', p.id, p.kode_permintaan::text, p.nama_barang::text, p.jumlah, un.symbol::text, p.tanggal_permintaan, p.status::text,
               CASE WHEN p.status IN ('pending', 'diproses') THEN 'menunggu' WHEN p.status = 'revisi' THEN 'revisi'
                    WHEN p.status = 'ditolak' THEN 'ditolak' ELSE 'disetujui' END,
               CASE p.status WHEN 'revisi' THEN p.permintaan_revisi WHEN 'ditolak' THEN p.alasan_penolakan ELSE p.catatan_admin END,
               p.created_at, i.photo::text, NULL::text
        FROM simbar.permintaan_barang p
        LEFT JOIN simbar.items i ON i.id = coalesce(p.created_item_id, p.item_id)
        LEFT JOIN simbar.units un ON un.id = i.unit_id
        WHERE p.user_id = actor.id
        UNION ALL
        SELECT 'usulan', s.id, s.kode_usulan::text, s.nama_barang::text, s.jumlah, NULL::text, s.tanggal_usulan, s.status::text,
               CASE WHEN s.status IN ('pending', 'diproses') THEN 'menunggu' WHEN s.status = 'revisi' THEN 'revisi'
                    WHEN s.status = 'ditolak' THEN 'ditolak' ELSE 'disetujui' END,
               CASE s.status WHEN 'revisi' THEN s.permintaan_revisi WHEN 'ditolak' THEN s.alasan_penolakan ELSE s.catatan_admin END,
               s.created_at, NULL::text, NULL::text
        FROM simbar.usulan_penerimaan_barang s
        WHERE s.user_id = actor.id
        UNION ALL
        SELECT 'pemeliharaan', m.id, a.kode_aset::text, a.nama_aset::text, NULL::integer, NULL::text, m.tanggal_pemeliharaan, m.status::text,
               CASE WHEN m.status IN ('menunggu', 'proses') THEN 'menunggu' ELSE 'disetujui' END,
               coalesce(m.tindakan_perbaikan, m.deskripsi_masalah), m.created_at, NULL::text, a.foto::text
        FROM simbar.pemeliharaan_aset m
        JOIN simbar.barang_aset a ON a.id = m.barang_aset_id
        WHERE m.created_by = actor.id
    ), filtered AS MATERIALIZED (
        SELECT * FROM mine
        WHERE kind = ANY (kinds) AND (kind_filter = 'all' OR kind = kind_filter)
          AND (btrim(search_text) = '' OR title ILIKE pattern ESCAPE '!' OR coalesce(code, '') ILIKE pattern ESCAPE '!')
    ), chosen AS (
        SELECT * FROM filtered WHERE bucket_filter = 'all' OR bucket = bucket_filter
    )
    SELECT jsonb_build_object(
        'kinds', to_jsonb(kinds),
        'counts', (SELECT jsonb_build_object('all', count(*),
            'menunggu', count(*) FILTER (WHERE bucket = 'menunggu'), 'revisi', count(*) FILTER (WHERE bucket = 'revisi'),
            'disetujui', count(*) FILTER (WHERE bucket = 'disetujui'), 'ditolak', count(*) FILTER (WHERE bucket = 'ditolak'),
            'dibatalkan', count(*) FILTER (WHERE bucket = 'dibatalkan')) FROM filtered),
        'total', (SELECT count(*) FROM chosen),
        'rows', coalesce((SELECT jsonb_agg(to_jsonb(x) - 'created_at' ORDER BY x.tanggal DESC, x.created_at DESC NULLS LAST, x.id DESC)
            FROM (SELECT * FROM chosen ORDER BY tanggal DESC, created_at DESC NULLS LAST, id DESC
                  LIMIT 15 OFFSET (page_number - 1) * 15) AS x), '[]'::jsonb))
    INTO result;
    RETURN result;
END;
$function$;

REVOKE ALL ON FUNCTION public.simbar_my_requests(text, text, text, integer) FROM PUBLIC, anon, authenticated, service_role;
GRANT EXECUTE ON FUNCTION public.simbar_my_requests(text, text, text, integer) TO authenticated;

COMMIT;
