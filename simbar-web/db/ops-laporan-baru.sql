-- Laporan, restructured: Persediaan (saldo awal/akhir per period), Mutasi (masuk + keluar), Pengajuan (all request kinds)
-- and Kartu Stok (one barang, running saldo). Requires ops-menus.sql (require_menu) and ops-verifikasi.sql (queue_rows).
-- Saldo is counted back from today's stock: saldo at the end of a period = stock now - net movement after the period.
-- That stays right for old periods and for barang whose opening stock was typed in when the barang was created. Safe to re-run.

BEGIN;

-- Persediaan per barang for [dari, sampai]: saldo awal, masuk, keluar, saldo akhir and their value at today's harga satuan.
-- Barang created after the period are left out.
CREATE OR REPLACE FUNCTION public.simbar_laporan_persediaan(dari date, sampai date, perolehan_filter text DEFAULT NULL, search_text text DEFAULT '')
RETURNS jsonb
LANGUAGE plpgsql STABLE SECURITY DEFINER SET search_path = ''
AS $function$
DECLARE
    pattern text;
BEGIN
    PERFORM simbar.require_menu(ARRAY['laporan']);
    IF dari IS NULL OR sampai IS NULL OR sampai < dari OR search_text IS NULL OR char_length(search_text) > 100 THEN
        RAISE invalid_parameter_value USING MESSAGE = 'Periode laporan tidak valid.';
    END IF;
    pattern := '%' || replace(replace(replace(btrim(search_text), '!', '!!'), '%', '!%'), '_', '!_') || '%';
    RETURN (WITH movement AS (
            SELECT item_id, date, quantity AS q FROM simbar.stock_in
            UNION ALL
            SELECT item_id, date, -quantity FROM simbar.stock_out
        ), per_item AS (
            SELECT i.id, i.code, i.name, i.perolehan, c.name AS category, u.symbol AS unit, i.price, i.stock,
                   coalesce(sum(m.q) FILTER (WHERE m.date > sampai), 0) AS after_end,
                   coalesce(sum(m.q) FILTER (WHERE m.date BETWEEN dari AND sampai AND m.q > 0), 0) AS masuk,
                   coalesce(-sum(m.q) FILTER (WHERE m.date BETWEEN dari AND sampai AND m.q < 0), 0) AS keluar
            FROM simbar.items i
            JOIN simbar.categories c ON c.id = i.category_id
            JOIN simbar.units u ON u.id = i.unit_id
            LEFT JOIN movement m ON m.item_id = i.id
            WHERE (i.created_at IS NULL OR i.created_at::date <= sampai)
              AND (nullif(perolehan_filter, '') IS NULL OR i.perolehan = perolehan_filter)
              AND (btrim(search_text) = '' OR i.name ILIKE pattern ESCAPE '!' OR i.code ILIKE pattern ESCAPE '!' OR c.name ILIKE pattern ESCAPE '!')
            GROUP BY i.id, c.name, u.symbol
        ), saldo AS (
            SELECT id, code, name, perolehan, category, unit, price, masuk, keluar,
                   stock - after_end - masuk + keluar AS saldo_awal, stock - after_end AS saldo_akhir
            FROM per_item
        )
        SELECT jsonb_build_object(
            'summary', jsonb_build_object('items', count(*), 'saldo_awal', coalesce(sum(saldo_awal), 0), 'masuk', coalesce(sum(masuk), 0),
                'keluar', coalesce(sum(keluar), 0), 'saldo_akhir', coalesce(sum(saldo_akhir), 0),
                'nilai_awal', coalesce(sum(saldo_awal * price), 0), 'nilai_akhir', coalesce(sum(saldo_akhir * price), 0)),
            'rows', coalesce(jsonb_agg(jsonb_build_object('id', id, 'code', code, 'name', name, 'perolehan', perolehan, 'category', category,
                'unit', unit, 'price', price, 'saldo_awal', saldo_awal, 'masuk', masuk, 'keluar', keluar, 'saldo_akhir', saldo_akhir,
                'nilai_akhir', saldo_akhir * price) ORDER BY lower(category), lower(name), id), '[]'::jsonb))
        FROM saldo);
END;
$function$;

-- Mutasi (barang masuk and keluar together) or Pengajuan (pengambilan, permintaan, usulan and pemeliharaan together).
-- filters: {dari, sampai, search, item, jenis, status}. page_number 0 returns every row (export and print).
CREATE OR REPLACE FUNCTION public.simbar_laporan_gabungan(report_kind text, filters jsonb DEFAULT '{}', page_number integer DEFAULT 1)
RETURNS jsonb
LANGUAGE plpgsql STABLE SECURITY DEFINER SET search_path = ''
AS $function$
DECLARE
    dari date := nullif(filters->>'dari', '')::date;
    sampai date := nullif(filters->>'sampai', '')::date;
    search_text text := btrim(coalesce(filters->>'search', ''));
    jenis_filter text := nullif(filters->>'jenis', '');
    status_filter text := nullif(filters->>'status', '');
    pattern text;
    page_limit integer := CASE WHEN page_number = 0 THEN NULL ELSE 15 END;
    page_offset integer := CASE WHEN page_number = 0 THEN 0 ELSE (page_number - 1) * 15 END;
    result jsonb;
BEGIN
    PERFORM simbar.require_menu(ARRAY['laporan']);
    IF report_kind IS NULL OR report_kind NOT IN ('mutasi', 'pengajuan') OR char_length(search_text) > 100
       OR page_number IS NULL OR page_number NOT BETWEEN 0 AND 10000
       OR (report_kind = 'mutasi' AND jenis_filter IS NOT NULL AND jenis_filter NOT IN ('in', 'out'))
       OR (report_kind = 'pengajuan' AND jenis_filter IS NOT NULL AND jenis_filter NOT IN ('pickup', 'permintaan', 'usulan', 'pemeliharaan'))
       OR (status_filter IS NOT NULL AND status_filter NOT IN ('menunggu', 'revisi', 'selesai', 'ditolak', 'dibatalkan')) THEN
        RAISE invalid_parameter_value USING MESSAGE = 'Filter laporan tidak valid.';
    END IF;
    pattern := '%' || replace(replace(replace(search_text, '!', '!!'), '%', '!%'), '_', '!_') || '%';

    IF report_kind = 'mutasi' THEN
        WITH matched AS MATERIALIZED (
            SELECT m.jenis, m.id, m.date, m.quantity, m.notes, i.code, i.name, c.name AS category, u.symbol AS unit, p.name AS petugas, m.created_at
            FROM (SELECT 'in'::text AS jenis, id, item_id, user_id, quantity, date, notes, created_at FROM simbar.stock_in
                  UNION ALL
                  SELECT 'out', id, item_id, user_id, quantity, date, notes, created_at FROM simbar.stock_out) m
            JOIN simbar.items i ON i.id = m.item_id
            JOIN simbar.categories c ON c.id = i.category_id
            JOIN simbar.units u ON u.id = i.unit_id
            LEFT JOIN simbar.users p ON p.id = m.user_id
            WHERE (dari IS NULL OR m.date >= dari) AND (sampai IS NULL OR m.date <= sampai)
              AND (jenis_filter IS NULL OR m.jenis = jenis_filter)
              AND (nullif(filters->>'item', '') IS NULL OR m.item_id = (filters->>'item')::bigint)
              AND (search_text = '' OR i.name ILIKE pattern ESCAPE '!' OR i.code ILIKE pattern ESCAPE '!' OR coalesce(m.notes, '') ILIKE pattern ESCAPE '!')
        )
        SELECT jsonb_build_object(
            'stats', (SELECT jsonb_build_object('total', count(*), 'masuk', coalesce(sum(quantity) FILTER (WHERE jenis = 'in'), 0),
                'keluar', coalesce(sum(quantity) FILTER (WHERE jenis = 'out'), 0)) FROM matched),
            'total', (SELECT count(*) FROM matched),
            'rows', coalesce((SELECT jsonb_agg(to_jsonb(r) - 'created_at' ORDER BY r.date DESC, r.created_at DESC NULLS LAST, r.jenis, r.id DESC)
                FROM (SELECT * FROM matched ORDER BY date DESC, created_at DESC NULLS LAST, jenis, id DESC LIMIT page_limit OFFSET page_offset) r), '[]'::jsonb))
        INTO result;
    ELSE
        WITH matched AS MATERIALIZED (
            SELECT * FROM simbar.queue_rows(ARRAY['pickup', 'permintaan', 'usulan', 'pemeliharaan']) q
            WHERE (dari IS NULL OR q.tanggal >= dari) AND (sampai IS NULL OR q.tanggal <= sampai)
              AND (jenis_filter IS NULL OR q.kind = jenis_filter) AND (status_filter IS NULL OR q.bucket = status_filter)
              AND (search_text = '' OR q.title ILIKE pattern ESCAPE '!' OR coalesce(q.code, '') ILIKE pattern ESCAPE '!'
                   OR coalesce(q.requester, '') ILIKE pattern ESCAPE '!')
        )
        SELECT jsonb_build_object(
            'stats', (SELECT jsonb_build_object('total', count(*),
                'menunggu', count(*) FILTER (WHERE bucket = 'menunggu'), 'revisi', count(*) FILTER (WHERE bucket = 'revisi'),
                'selesai', count(*) FILTER (WHERE bucket = 'selesai'), 'ditolak', count(*) FILTER (WHERE bucket = 'ditolak'),
                'dibatalkan', count(*) FILTER (WHERE bucket = 'dibatalkan')) FROM matched),
            'total', (SELECT count(*) FROM matched),
            'rows', coalesce((SELECT jsonb_agg(to_jsonb(r) - 'created_at' ORDER BY r.tanggal DESC, r.created_at DESC NULLS LAST, r.kind, r.id DESC)
                FROM (SELECT * FROM matched ORDER BY tanggal DESC, created_at DESC NULLS LAST, kind, id DESC LIMIT page_limit OFFSET page_offset) r), '[]'::jsonb))
        INTO result;
    END IF;
    RETURN result;
END;
$function$;

-- Kartu stok: one barang's masuk and keluar in [dari, sampai], oldest first, with the saldo after every line.
CREATE OR REPLACE FUNCTION public.simbar_kartu_stok(item_ref bigint, dari date, sampai date)
RETURNS jsonb
LANGUAGE plpgsql STABLE SECURITY DEFINER SET search_path = ''
AS $function$
DECLARE
    item record;
    saldo_awal bigint;
BEGIN
    PERFORM simbar.require_menu(ARRAY['laporan']);
    IF item_ref IS NULL OR dari IS NULL OR sampai IS NULL OR sampai < dari THEN
        RAISE invalid_parameter_value USING MESSAGE = 'Barang dan periode kartu stok wajib diisi.';
    END IF;
    SELECT i.id, i.code, i.name, i.stock, i.price, i.perolehan, c.name AS category, u.name AS unit_name, u.symbol AS unit
    INTO item FROM simbar.items i JOIN simbar.categories c ON c.id = i.category_id JOIN simbar.units u ON u.id = i.unit_id
    WHERE i.id = item_ref;
    IF NOT FOUND THEN
        RAISE no_data_found USING MESSAGE = 'Barang tidak ditemukan.';
    END IF;
    saldo_awal := item.stock
        - coalesce((SELECT sum(quantity) FROM simbar.stock_in WHERE item_id = item_ref AND date >= dari), 0)
        + coalesce((SELECT sum(quantity) FROM simbar.stock_out WHERE item_id = item_ref AND date >= dari), 0);
    RETURN (WITH lines AS (
            SELECT 'in'::text AS jenis, s.id, s.date, s.quantity, s.quantity AS q, s.notes, p.name AS petugas, s.created_at
            FROM simbar.stock_in s LEFT JOIN simbar.users p ON p.id = s.user_id
            WHERE s.item_id = item_ref AND s.date BETWEEN dari AND sampai
            UNION ALL
            SELECT 'out', s.id, s.date, s.quantity, -s.quantity, s.notes, p.name, s.created_at
            FROM simbar.stock_out s LEFT JOIN simbar.users p ON p.id = s.user_id
            WHERE s.item_id = item_ref AND s.date BETWEEN dari AND sampai
        ), running AS (
            SELECT *, saldo_awal + sum(q) OVER (ORDER BY date, created_at NULLS FIRST, jenis, id ROWS UNBOUNDED PRECEDING) AS saldo,
                   row_number() OVER (ORDER BY date, created_at NULLS FIRST, jenis, id) AS n
            FROM lines
        )
        SELECT jsonb_build_object(
            'item', jsonb_build_object('id', item.id, 'code', item.code, 'name', item.name, 'category', item.category, 'unit', item.unit,
                'unit_name', item.unit_name, 'price', item.price, 'perolehan', item.perolehan),
            'saldo_awal', saldo_awal,
            'masuk', coalesce((SELECT sum(quantity) FROM lines WHERE jenis = 'in'), 0),
            'keluar', coalesce((SELECT sum(quantity) FROM lines WHERE jenis = 'out'), 0),
            'saldo_akhir', saldo_awal + coalesce((SELECT sum(q) FROM lines), 0),
            'rows', coalesce((SELECT jsonb_agg(jsonb_build_object('jenis', jenis, 'id', id, 'date', date, 'quantity', quantity,
                'notes', notes, 'petugas', petugas, 'saldo', saldo) ORDER BY n) FROM running), '[]'::jsonb)));
END;
$function$;

DO $grants$
DECLARE
    signature text;
BEGIN
    FOREACH signature IN ARRAY ARRAY[
        'public.simbar_laporan_persediaan(date, date, text, text)',
        'public.simbar_laporan_gabungan(text, jsonb, integer)',
        'public.simbar_kartu_stok(bigint, date, date)'
    ] LOOP
        EXECUTE 'REVOKE ALL ON FUNCTION ' || signature || ' FROM PUBLIC, anon, authenticated, service_role';
        EXECUTE 'GRANT EXECUTE ON FUNCTION ' || signature || ' TO authenticated';
    END LOOP;
END;
$grants$;

COMMIT;
