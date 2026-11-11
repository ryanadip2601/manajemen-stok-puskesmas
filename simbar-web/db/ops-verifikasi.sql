-- "Verifikasi": the warehouse inbox. One queue of everyone's pengambilan, permintaan, usulan and pemeliharaan,
-- for the Verifikasi page and the waiting count on its menu. Each kind follows the right that already guards
-- its actions: checklist, verifikasi, usulan-admin (Atur Menu); pemeliharaan stays with admin and admin_gudang,
-- like simbar_pemeliharaan_status. Safe to re-run.

BEGIN;

CREATE OR REPLACE FUNCTION simbar.queue_kinds(person_role text)
RETURNS text[]
LANGUAGE sql STABLE SECURITY DEFINER SET search_path = ''
AS $function$
    SELECT ARRAY(SELECT k FROM (VALUES (1, 'pickup', 'checklist'), (2, 'permintaan', 'verifikasi'), (3, 'usulan', 'usulan-admin'),
        (4, 'pemeliharaan', NULL)) AS q(n, k, menu)
        WHERE CASE WHEN q.menu IS NULL THEN person_role IN ('admin', 'admin_gudang') ELSE simbar.has_menu(person_role, ARRAY[q.menu]) END
        ORDER BY q.n);
$function$;

-- Every request of the given kinds, with its bucket: menunggu (needs the warehouse), revisi (back with the requester),
-- selesai, ditolak, dibatalkan.
-- The row type gained photo columns; plpgsql callers are not tracked by Postgres, so dropping is safe.
DROP FUNCTION IF EXISTS simbar.queue_rows(text[]);
CREATE OR REPLACE FUNCTION simbar.queue_rows(kinds text[])
RETURNS TABLE (kind text, id bigint, code text, title text, requester text, jumlah integer, satuan text, tanggal date,
               status text, bucket text, note text, created_at timestamp without time zone, photo text, aset_foto text)
LANGUAGE sql STABLE SECURITY DEFINER SET search_path = ''
AS $function$
    SELECT 'pickup', r.id, NULL, i.name, u.name, r.quantity, un.symbol, r.request_date, r.status,
           CASE r.status WHEN 'pending' THEN 'menunggu' WHEN 'approved' THEN 'menunggu' WHEN 'completed' THEN 'selesai'
                WHEN 'rejected' THEN 'ditolak' ELSE 'dibatalkan' END,
           CASE WHEN r.status = 'rejected' THEN r.reject_reason ELSE r.notes END, r.created_at, i.photo, NULL
    FROM simbar.stock_out_requests r
    JOIN simbar.items i ON i.id = r.item_id
    LEFT JOIN simbar.units un ON un.id = i.unit_id
    LEFT JOIN simbar.users u ON u.id = r.user_id
    WHERE 'pickup' = ANY (kinds)
    UNION ALL
    SELECT 'permintaan', p.id, p.kode_permintaan, p.nama_barang, u.name, p.jumlah, un.symbol, p.tanggal_permintaan, p.status,
           CASE WHEN p.status IN ('pending', 'diproses') THEN 'menunggu' WHEN p.status = 'revisi' THEN 'revisi'
                WHEN p.status = 'ditolak' THEN 'ditolak' ELSE 'selesai' END,
           CASE p.status WHEN 'revisi' THEN p.permintaan_revisi WHEN 'ditolak' THEN p.alasan_penolakan ELSE p.keterangan END,
           p.created_at, i.photo, NULL
    FROM simbar.permintaan_barang p
    LEFT JOIN simbar.items i ON i.id = coalesce(p.created_item_id, p.item_id)
    LEFT JOIN simbar.units un ON un.id = i.unit_id
    LEFT JOIN simbar.users u ON u.id = p.user_id
    WHERE 'permintaan' = ANY (kinds)
    UNION ALL
    SELECT 'usulan', s.id, s.kode_usulan, s.nama_barang, u.name, s.jumlah, NULL, s.tanggal_usulan, s.status,
           CASE WHEN s.status IN ('pending', 'diproses') THEN 'menunggu' WHEN s.status = 'revisi' THEN 'revisi'
                WHEN s.status = 'ditolak' THEN 'ditolak' ELSE 'selesai' END,
           CASE s.status WHEN 'revisi' THEN s.permintaan_revisi WHEN 'ditolak' THEN s.alasan_penolakan ELSE s.keterangan_user END,
           s.created_at, NULL, NULL
    FROM simbar.usulan_penerimaan_barang s
    LEFT JOIN simbar.users u ON u.id = s.user_id
    WHERE 'usulan' = ANY (kinds)
    UNION ALL
    SELECT 'pemeliharaan', m.id, a.kode_aset, a.nama_aset, u.name, NULL, NULL, m.tanggal_pemeliharaan, m.status,
           CASE WHEN m.status IN ('menunggu', 'proses') THEN 'menunggu' ELSE 'selesai' END,
           m.deskripsi_masalah, m.created_at, NULL, a.foto
    FROM simbar.pemeliharaan_aset m
    JOIN simbar.barang_aset a ON a.id = m.barang_aset_id
    LEFT JOIN simbar.users u ON u.id = m.created_by
    WHERE 'pemeliharaan' = ANY (kinds);
$function$;

CREATE OR REPLACE FUNCTION public.simbar_queue(
    kind_filter text DEFAULT 'all', bucket_filter text DEFAULT 'menunggu', search_text text DEFAULT '', page_number integer DEFAULT 1
) RETURNS jsonb
LANGUAGE plpgsql STABLE SECURITY DEFINER SET search_path = ''
AS $function$
DECLARE
    actor simbar.users%ROWTYPE := simbar.require_profile(ARRAY['admin', 'admin_gudang', 'staff']);
    kinds text[] := simbar.queue_kinds(actor.role);
    pattern text;
    oldest_first boolean := bucket_filter = 'menunggu';
    result jsonb;
BEGIN
    IF kind_filter IS NULL OR kind_filter NOT IN ('all', 'pickup', 'permintaan', 'usulan', 'pemeliharaan') OR
       bucket_filter IS NULL OR bucket_filter NOT IN ('all', 'menunggu', 'revisi', 'selesai', 'ditolak', 'dibatalkan') OR
       search_text IS NULL OR char_length(search_text) > 100 OR page_number IS NULL OR page_number NOT BETWEEN 1 AND 10000 THEN
        RAISE invalid_parameter_value USING MESSAGE = 'Filter verifikasi tidak valid.';
    END IF;
    pattern := '%' || replace(replace(replace(btrim(search_text), '!', '!!'), '%', '!%'), '_', '!_') || '%';

    WITH all_rows AS MATERIALIZED (
        SELECT * FROM simbar.queue_rows(kinds)
    ), searched AS MATERIALIZED (
        SELECT * FROM all_rows
        WHERE btrim(search_text) = '' OR title ILIKE pattern ESCAPE '!' OR coalesce(code, '') ILIKE pattern ESCAPE '!'
           OR coalesce(requester, '') ILIKE pattern ESCAPE '!'
    ), chosen AS (
        SELECT * FROM searched
        WHERE (kind_filter = 'all' OR kind = kind_filter) AND (bucket_filter = 'all' OR bucket = bucket_filter)
    )
    SELECT jsonb_build_object(
        'kinds', to_jsonb(kinds),
        -- Waiting per kind (ignores the kind and status filters), for the summary above the list.
        'waiting', (SELECT jsonb_build_object('all', count(*),
            'pickup', count(*) FILTER (WHERE kind = 'pickup'), 'permintaan', count(*) FILTER (WHERE kind = 'permintaan'),
            'usulan', count(*) FILTER (WHERE kind = 'usulan'), 'pemeliharaan', count(*) FILTER (WHERE kind = 'pemeliharaan'))
            FROM searched WHERE bucket = 'menunggu'),
        -- Per status within the chosen kind, for the status filter.
        'counts', (SELECT jsonb_build_object('all', count(*),
            'menunggu', count(*) FILTER (WHERE bucket = 'menunggu'), 'revisi', count(*) FILTER (WHERE bucket = 'revisi'),
            'selesai', count(*) FILTER (WHERE bucket = 'selesai'), 'ditolak', count(*) FILTER (WHERE bucket = 'ditolak'),
            'dibatalkan', count(*) FILTER (WHERE bucket = 'dibatalkan'))
            FROM searched WHERE kind_filter = 'all' OR kind = kind_filter),
        'total', (SELECT count(*) FROM chosen),
        'rows', coalesce((SELECT jsonb_agg(to_jsonb(x) - 'created_at' - 'sort_key' ORDER BY x.sort_key) FROM (
            SELECT c.*, row_number() OVER (ORDER BY
                    CASE WHEN oldest_first THEN c.tanggal END ASC, CASE WHEN oldest_first THEN c.created_at END ASC NULLS LAST,
                    CASE WHEN NOT oldest_first THEN c.tanggal END DESC, CASE WHEN NOT oldest_first THEN c.created_at END DESC NULLS LAST,
                    c.kind, c.id) AS sort_key
            FROM chosen AS c
            ORDER BY sort_key
            LIMIT 15 OFFSET (page_number - 1) * 15) AS x), '[]'::jsonb))
    INTO result;
    RETURN result;
END;
$function$;

-- The number on the Verifikasi menu: everything waiting for the caller's role.
CREATE OR REPLACE FUNCTION public.simbar_queue_waiting()
RETURNS integer
LANGUAGE plpgsql STABLE SECURITY DEFINER SET search_path = ''
AS $function$
DECLARE
    actor simbar.users%ROWTYPE := simbar.require_profile(ARRAY['admin', 'admin_gudang', 'staff']);
BEGIN
    RETURN (SELECT count(*) FROM simbar.queue_rows(simbar.queue_kinds(actor.role)) WHERE bucket = 'menunggu');
END;
$function$;

REVOKE ALL ON FUNCTION simbar.queue_kinds(text) FROM PUBLIC, anon, authenticated, service_role;
REVOKE ALL ON FUNCTION simbar.queue_rows(text[]) FROM PUBLIC, anon, authenticated, service_role;
REVOKE ALL ON FUNCTION public.simbar_queue(text, text, text, integer) FROM PUBLIC, anon, authenticated, service_role;
REVOKE ALL ON FUNCTION public.simbar_queue_waiting() FROM PUBLIC, anon, authenticated, service_role;
GRANT EXECUTE ON FUNCTION public.simbar_queue(text, text, text, integer) TO authenticated;
GRANT EXECUTE ON FUNCTION public.simbar_queue_waiting() TO authenticated;

COMMIT;
