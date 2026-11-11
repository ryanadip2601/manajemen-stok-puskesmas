-- Laporan (admin only, like Laravel's admin middleware) and the dashboard for every role. Safe to re-run.
-- Ports LaporanController, ReportController and StockService::getDashboardStats. Requires ops-core.sql.

BEGIN;

-- kind: barang_masuk, barang_keluar, pengambilan, permintaan_bhp, usulan_aset.
-- filters: {dari, sampai, search, item, status, tipe, prioritas, ruangan}. page_number 0 returns every row (CSV).
CREATE OR REPLACE FUNCTION public.simbar_laporan(report_kind text, filters jsonb DEFAULT '{}', page_number integer DEFAULT 1)
RETURNS jsonb
LANGUAGE plpgsql STABLE SECURITY DEFINER SET search_path = ''
AS $function$
DECLARE
    dari date := nullif(filters->>'dari', '')::date;
    sampai date := nullif(filters->>'sampai', '')::date;
    search_text text := btrim(coalesce(filters->>'search', ''));
    pattern text;
    page_limit integer := CASE WHEN page_number = 0 THEN NULL ELSE 15 END;
    page_offset integer := CASE WHEN page_number = 0 THEN 0 ELSE (page_number - 1) * 15 END;
    result jsonb;
BEGIN
    PERFORM simbar.require_menu(ARRAY['laporan']);
    IF report_kind IS NULL OR report_kind NOT IN ('barang_masuk', 'barang_keluar', 'pengambilan', 'permintaan_bhp', 'usulan_aset')
       OR char_length(search_text) > 100 OR page_number IS NULL OR page_number NOT BETWEEN 0 AND 10000 THEN
        RAISE invalid_parameter_value USING MESSAGE = 'Filter laporan tidak valid.';
    END IF;
    pattern := '%' || replace(replace(replace(search_text, '!', '!!'), '%', '!%'), '_', '!_') || '%';

    IF report_kind IN ('barang_masuk', 'barang_keluar') THEN
        WITH matched AS MATERIALIZED (
            SELECT m.id, m.date, m.quantity, m.notes, i.code, i.name, c.name AS category, u.name AS unit, p.name AS petugas, m.created_at
            FROM (SELECT id, item_id, user_id, quantity, date, notes, created_at FROM simbar.stock_in WHERE report_kind = 'barang_masuk'
                  UNION ALL
                  SELECT id, item_id, user_id, quantity, date, notes, created_at FROM simbar.stock_out WHERE report_kind = 'barang_keluar') m
            JOIN simbar.items i ON i.id = m.item_id
            JOIN simbar.categories c ON c.id = i.category_id
            JOIN simbar.units u ON u.id = i.unit_id
            LEFT JOIN simbar.users p ON p.id = m.user_id
            WHERE (dari IS NULL OR m.date >= dari) AND (sampai IS NULL OR m.date <= sampai)
              AND (i.name ILIKE pattern ESCAPE '!' OR i.code ILIKE pattern ESCAPE '!')
              AND (nullif(filters->>'item', '') IS NULL OR m.item_id = (filters->>'item')::bigint)
        )
        SELECT jsonb_build_object('stats', (SELECT jsonb_build_object('total_transaksi', count(*), 'total_qty', coalesce(sum(quantity), 0)) FROM matched),
            'total', (SELECT count(*) FROM matched),
            'rows', coalesce((SELECT jsonb_agg(to_jsonb(r) - 'created_at' ORDER BY r.date DESC, r.created_at DESC NULLS LAST, r.id DESC)
                FROM (SELECT * FROM matched ORDER BY date DESC, created_at DESC NULLS LAST, id DESC LIMIT page_limit OFFSET page_offset) r), '[]'))
        INTO result;
    ELSIF report_kind = 'pengambilan' THEN
        WITH matched AS MATERIALIZED (
            SELECT r.id, r.request_date AS date, r.quantity, r.status, r.notes, r.processed_at, u.name AS pemohon, i.name AS barang,
                   un.symbol AS unit, p.name AS processed_by, r.created_at
            FROM simbar.stock_out_requests r
            JOIN simbar.users u ON u.id = r.user_id
            JOIN simbar.items i ON i.id = r.item_id
            JOIN simbar.units un ON un.id = i.unit_id
            LEFT JOIN simbar.users p ON p.id = r.processed_by
            WHERE (dari IS NULL OR r.request_date >= dari) AND (sampai IS NULL OR r.request_date <= sampai)
              AND (nullif(filters->>'status', '') IS NULL OR r.status = filters->>'status')
              AND (u.name ILIKE pattern ESCAPE '!' OR i.name ILIKE pattern ESCAPE '!')
        )
        SELECT jsonb_build_object('stats', (SELECT jsonb_build_object('total', (SELECT count(*) FROM matched),
                'pending', count(*) FILTER (WHERE status = 'pending'), 'approved', count(*) FILTER (WHERE status = 'approved'),
                'completed', count(*) FILTER (WHERE status = 'completed'), 'rejected', count(*) FILTER (WHERE status = 'rejected'))
                FROM simbar.stock_out_requests),
            'total', (SELECT count(*) FROM matched),
            'rows', coalesce((SELECT jsonb_agg(to_jsonb(r) - 'created_at' ORDER BY r.date DESC, r.created_at DESC NULLS LAST, r.id DESC)
                FROM (SELECT * FROM matched ORDER BY date DESC, created_at DESC NULLS LAST, id DESC LIMIT page_limit OFFSET page_offset) r), '[]'))
        INTO result;
    ELSIF report_kind = 'permintaan_bhp' THEN
        WITH matched AS MATERIALIZED (
            SELECT p.id, p.kode_permintaan AS code, p.tanggal_permintaan AS date, p.tipe, p.status, p.prioritas, p.jumlah,
                   CASE WHEN p.tipe = 'restok' THEN coalesce(i.name, p.nama_barang) ELSE p.nama_barang END AS barang,
                   u.name AS pemohon, v.name AS verified_by
            FROM simbar.permintaan_barang p
            JOIN simbar.users u ON u.id = p.user_id
            LEFT JOIN simbar.items i ON i.id = p.item_id
            LEFT JOIN simbar.users v ON v.id = p.verified_by
            WHERE (dari IS NULL OR p.tanggal_permintaan >= dari) AND (sampai IS NULL OR p.tanggal_permintaan <= sampai)
              AND (nullif(filters->>'status', '') IS NULL OR p.status = filters->>'status')
              AND (nullif(filters->>'tipe', '') IS NULL OR p.tipe = filters->>'tipe')
              AND (p.nama_barang ILIKE pattern ESCAPE '!' OR p.kode_permintaan ILIKE pattern ESCAPE '!'
                   OR u.name ILIKE pattern ESCAPE '!' OR i.name ILIKE pattern ESCAPE '!')
        )
        SELECT jsonb_build_object('stats', (SELECT jsonb_build_object('total', (SELECT count(*) FROM matched),
                'pending', count(*) FILTER (WHERE status = 'pending'), 'disetujui', count(*) FILTER (WHERE status = 'disetujui'),
                'ditolak', count(*) FILTER (WHERE status = 'ditolak'), 'barang_baru', count(*) FILTER (WHERE tipe = 'barang_baru'),
                'restok', count(*) FILTER (WHERE tipe = 'restok')) FROM simbar.permintaan_barang),
            'total', (SELECT count(*) FROM matched),
            'rows', coalesce((SELECT jsonb_agg(to_jsonb(r) ORDER BY r.date DESC, r.id DESC)
                FROM (SELECT * FROM matched ORDER BY date DESC, id DESC LIMIT page_limit OFFSET page_offset) r), '[]'))
        INTO result;
    ELSE
        WITH matched AS MATERIALIZED (
            SELECT s.id, s.kode_usulan AS code, s.tanggal_usulan AS date, s.status, s.prioritas, s.jumlah, s.nama_barang AS barang,
                   s.merk, s.estimasi_harga, r.nama_ruangan AS ruangan, u.name AS pengusul, v.name AS verified_by
            FROM simbar.usulan_penerimaan_barang s
            JOIN simbar.users u ON u.id = s.user_id
            JOIN simbar.ruangan r ON r.id = s.ruangan_id
            LEFT JOIN simbar.users v ON v.id = s.verified_by
            WHERE (dari IS NULL OR s.tanggal_usulan >= dari) AND (sampai IS NULL OR s.tanggal_usulan <= sampai)
              AND (nullif(filters->>'status', '') IS NULL OR s.status = filters->>'status')
              AND (nullif(filters->>'prioritas', '') IS NULL OR s.prioritas = filters->>'prioritas')
              AND (nullif(filters->>'ruangan', '') IS NULL OR s.ruangan_id = (filters->>'ruangan')::bigint)
              AND (s.nama_barang ILIKE pattern ESCAPE '!' OR s.kode_usulan ILIKE pattern ESCAPE '!' OR u.name ILIKE pattern ESCAPE '!')
        )
        SELECT jsonb_build_object('stats', (SELECT jsonb_build_object('total', (SELECT count(*) FROM matched),
                'pending', count(*) FILTER (WHERE status = 'pending'), 'diproses', count(*) FILTER (WHERE status = 'diproses'),
                'disetujui', count(*) FILTER (WHERE status = 'disetujui'), 'ditolak', count(*) FILTER (WHERE status = 'ditolak'),
                'revisi', count(*) FILTER (WHERE status = 'revisi')) FROM simbar.usulan_penerimaan_barang),
            'total', (SELECT count(*) FROM matched),
            'rows', coalesce((SELECT jsonb_agg(to_jsonb(r) ORDER BY r.date DESC, r.id DESC)
                FROM (SELECT * FROM matched ORDER BY date DESC, id DESC LIMIT page_limit OFFSET page_offset) r), '[]'))
        INTO result;
    END IF;
    RETURN result;
END;
$function$;

-- Laporan bulanan/triwulan: per barang, movement inside the period plus current stock value.
CREATE OR REPLACE FUNCTION public.simbar_laporan_periode(dari date, sampai date, perolehan_filter text DEFAULT NULL)
RETURNS jsonb
LANGUAGE plpgsql STABLE SECURITY DEFINER SET search_path = ''
AS $function$
BEGIN
    PERFORM simbar.require_menu(ARRAY['laporan']);
    IF dari IS NULL OR sampai IS NULL OR sampai < dari THEN
        RAISE invalid_parameter_value USING MESSAGE = 'Periode laporan tidak valid.';
    END IF;
    RETURN (SELECT jsonb_build_object(
            'summary', jsonb_build_object('total_items', count(*), 'total_stock_in', coalesce(sum(total_in), 0),
                'total_stock_out', coalesce(sum(total_out), 0), 'total_stock', coalesce(sum(stock), 0), 'total_value', coalesce(sum(nilai), 0)),
            'rows', coalesce(jsonb_agg(to_jsonb(t) ORDER BY lower(t.name), t.id), '[]'))
        FROM (SELECT i.id, i.code, i.name, i.perolehan, c.name AS category, u.name AS unit, i.price, i.stock, i.stock * i.price AS nilai,
                coalesce((SELECT sum(quantity) FROM simbar.stock_in s WHERE s.item_id = i.id AND s.date BETWEEN dari AND sampai), 0) AS total_in,
                coalesce((SELECT sum(quantity) FROM simbar.stock_out s WHERE s.item_id = i.id AND s.date BETWEEN dari AND sampai), 0) AS total_out
              FROM simbar.items i JOIN simbar.categories c ON c.id = i.category_id JOIN simbar.units u ON u.id = i.unit_id
              WHERE nullif(perolehan_filter, '') IS NULL OR i.perolehan = perolehan_filter) t);
END;
$function$;

CREATE OR REPLACE FUNCTION public.simbar_laporan_tahunan(tahun integer)
RETURNS jsonb
LANGUAGE plpgsql STABLE SECURITY DEFINER SET search_path = ''
AS $function$
BEGIN
    PERFORM simbar.require_menu(ARRAY['laporan']);
    IF tahun IS NULL OR tahun NOT BETWEEN 2000 AND 2100 THEN
        RAISE invalid_parameter_value USING MESSAGE = 'Tahun laporan tidak valid.';
    END IF;
    RETURN jsonb_build_object(
        'months', (SELECT jsonb_agg(jsonb_build_object('month', m,
                'stock_in', coalesce((SELECT sum(quantity) FROM simbar.stock_in WHERE extract(year FROM date) = tahun AND extract(month FROM date) = m), 0),
                'stock_out', coalesce((SELECT sum(quantity) FROM simbar.stock_out WHERE extract(year FROM date) = tahun AND extract(month FROM date) = m), 0))
            ORDER BY m) FROM generate_series(1, 12) AS m),
        'top_items', (SELECT coalesce(jsonb_agg(to_jsonb(t) ORDER BY t.total_in DESC, t.name), '[]') FROM (
            SELECT i.id, i.name, i.code, u.symbol AS unit,
                   coalesce((SELECT sum(quantity) FROM simbar.stock_in s WHERE s.item_id = i.id AND extract(year FROM s.date) = tahun), 0) AS total_in,
                   coalesce((SELECT sum(quantity) FROM simbar.stock_out s WHERE s.item_id = i.id AND extract(year FROM s.date) = tahun), 0) AS total_out
            FROM simbar.items i JOIN simbar.units u ON u.id = i.unit_id
            ORDER BY total_in DESC, i.name LIMIT 10) t)
    );
END;
$function$;

-- Dashboard: stock overview for everyone; work queues and request insight for admin and admin_gudang.
CREATE OR REPLACE FUNCTION public.simbar_dashboard(range_days integer DEFAULT 30)
RETURNS jsonb
LANGUAGE plpgsql STABLE SECURITY DEFINER SET search_path = ''
AS $function$
DECLARE
    actor simbar.users%ROWTYPE := simbar.require_profile(ARRAY['admin', 'admin_gudang', 'staff']);
    today date := simbar.local_today();
    start_date date;
    month_start date := date_trunc('month', simbar.local_today())::date;
    result jsonb;
BEGIN
    range_days := CASE WHEN range_days IN (30, 90, 180) THEN range_days ELSE 30 END;
    start_date := today - (range_days - 1);
    result := jsonb_build_object(
        'range_days', range_days,
        'stats', jsonb_build_object('total_items', (SELECT count(*) FROM simbar.items), 'total_stock', (SELECT coalesce(sum(stock), 0) FROM simbar.items),
            'low_stock', (SELECT count(*) FROM simbar.items WHERE stock <= minimum_stock),
            'categories', (SELECT count(DISTINCT category_id) FROM simbar.items), 'assets', (SELECT count(*) FROM simbar.barang_aset)),
        'low_stock_items', (SELECT coalesce(jsonb_agg(to_jsonb(t) ORDER BY t.stock, t.name), '[]') FROM (
            SELECT i.id, i.code, i.name, i.stock, i.minimum_stock AS minimum, u.symbol AS unit FROM simbar.items i JOIN simbar.units u ON u.id = i.unit_id
            WHERE i.stock <= i.minimum_stock ORDER BY i.stock, i.name LIMIT 10) t),
        'monthly', (SELECT jsonb_agg(jsonb_build_object('month', to_char(m, 'YYYY-MM'),
                'stock_in', coalesce((SELECT sum(quantity) FROM simbar.stock_in WHERE date >= m AND date < m + interval '1 month'), 0),
                'stock_out', coalesce((SELECT sum(quantity) FROM simbar.stock_out WHERE date >= m AND date < m + interval '1 month'), 0)) ORDER BY m)
            FROM generate_series(month_start - interval '5 months', month_start, interval '1 month') AS m),
        'categories', (SELECT coalesce(jsonb_agg(to_jsonb(t) ORDER BY t.stock DESC, t.name), '[]') FROM (
            SELECT c.name, count(i.id) AS items, coalesce(sum(i.stock), 0) AS stock FROM simbar.categories c
            LEFT JOIN simbar.items i ON i.category_id = c.id GROUP BY c.id, c.name) t),
        'top_items', (SELECT coalesce(jsonb_agg(to_jsonb(t) ORDER BY t.stock DESC, t.name), '[]') FROM (
            SELECT i.name, i.stock, u.symbol AS unit FROM simbar.items i JOIN simbar.units u ON u.id = i.unit_id ORDER BY i.stock DESC, i.name LIMIT 5) t),
        'recent', (SELECT coalesce(jsonb_agg(to_jsonb(t) ORDER BY t.created_at DESC NULLS LAST, t.id DESC), '[]') FROM (
            SELECT * FROM (
                SELECT 'in' AS kind, s.id, s.date, s.quantity, s.created_at, i.name, u.symbol AS unit, p.name AS petugas FROM simbar.stock_in s
                JOIN simbar.items i ON i.id = s.item_id JOIN simbar.units u ON u.id = i.unit_id LEFT JOIN simbar.users p ON p.id = s.user_id
                UNION ALL
                SELECT 'out', s.id, s.date, s.quantity, s.created_at, i.name, u.symbol, p.name FROM simbar.stock_out s
                JOIN simbar.items i ON i.id = s.item_id JOIN simbar.units u ON u.id = i.unit_id LEFT JOIN simbar.users p ON p.id = s.user_id
            ) movement ORDER BY created_at DESC NULLS LAST, id DESC LIMIT 8) t)
    );
    IF actor.role IN ('admin', 'admin_gudang') THEN
        result := result || jsonb_build_object(
            'queues', jsonb_build_object(
                'pending_pickup', (SELECT count(*) FROM simbar.stock_out_requests WHERE status = 'pending'),
                'pending_bhp', (SELECT count(*) FROM simbar.permintaan_barang WHERE status IN ('pending', 'diproses')),
                'pending_usulan', (SELECT count(*) FROM simbar.usulan_penerimaan_barang WHERE status IN ('pending', 'diproses')),
                'pending_pemeliharaan', (SELECT count(*) FROM simbar.pemeliharaan_aset WHERE status = 'menunggu'),
                'pending_users', CASE WHEN actor.role = 'admin' THEN (SELECT count(*) FROM simbar.users WHERE NOT is_approved) ELSE 0 END),
            'funnel', (SELECT jsonb_build_object('total', count(*), 'pending', count(*) FILTER (WHERE status = 'pending'),
                    'approved', count(*) FILTER (WHERE status = 'approved'), 'completed', count(*) FILTER (WHERE status = 'completed'),
                    'rejected', count(*) FILTER (WHERE status = 'rejected'), 'cancelled', count(*) FILTER (WHERE status = 'cancelled'),
                    'avg_approval_hours', round(coalesce(avg(extract(epoch FROM processed_at - created_at) / 3600)
                        FILTER (WHERE processed_at IS NOT NULL AND created_at IS NOT NULL), 0)::numeric, 1),
                    'processed_within_24h', count(*) FILTER (WHERE processed_at - created_at <= interval '24 hours'),
                    'processed', count(*) FILTER (WHERE processed_at IS NOT NULL AND created_at IS NOT NULL))
                FROM simbar.stock_out_requests WHERE request_date BETWEEN start_date AND today),
            'pareto', (SELECT coalesce(jsonb_agg(to_jsonb(t) ORDER BY t.total_out DESC, t.name), '[]') FROM (
                SELECT i.name, u.symbol AS unit, sum(s.quantity) AS total_out FROM simbar.stock_out s
                JOIN simbar.items i ON i.id = s.item_id JOIN simbar.units u ON u.id = i.unit_id
                WHERE s.date BETWEEN start_date AND today GROUP BY i.id, i.name, u.symbol ORDER BY total_out DESC, i.name LIMIT 10) t),
            'aging', (SELECT jsonb_build_object('0-30 hari', count(*) FILTER (WHERE age BETWEEN 0 AND 30),
                    '31-60 hari', count(*) FILTER (WHERE age BETWEEN 31 AND 60), '61-90 hari', count(*) FILTER (WHERE age BETWEEN 61 AND 90),
                    '>90 hari', count(*) FILTER (WHERE age > 90), 'Belum bergerak', count(*) FILTER (WHERE age IS NULL))
                FROM (SELECT today - greatest((SELECT max(date) FROM simbar.stock_in s WHERE s.item_id = i.id),
                        (SELECT max(date) FROM simbar.stock_out s WHERE s.item_id = i.id)) AS age FROM simbar.items i) aged));
    END IF;
    RETURN result;
END;
$function$;

DO $grants$
DECLARE
    signature text;
BEGIN
    FOREACH signature IN ARRAY ARRAY[
        'public.simbar_laporan(text, jsonb, integer)',
        'public.simbar_laporan_periode(date, date, text)',
        'public.simbar_laporan_tahunan(integer)',
        'public.simbar_dashboard(integer)'
    ] LOOP
        EXECUTE 'REVOKE ALL ON FUNCTION ' || signature || ' FROM PUBLIC, anon, authenticated, service_role';
        EXECUTE 'GRANT EXECUTE ON FUNCTION ' || signature || ' TO authenticated';
    END LOOP;
END;
$grants$;

COMMIT;
