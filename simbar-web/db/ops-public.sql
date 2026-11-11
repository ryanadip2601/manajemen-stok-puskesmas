BEGIN;

-- Public dashboard shown before sign-in: totals, charts and item names only.
-- No people (petugas, pemohon), no transactions, no work queues.
CREATE OR REPLACE FUNCTION public.simbar_public_dashboard()
RETURNS jsonb
LANGUAGE plpgsql STABLE SECURITY DEFINER SET search_path = ''
AS $function$
DECLARE
    today date := simbar.local_today();
    start_date date := simbar.local_today() - 29;
    month_start date := date_trunc('month', simbar.local_today())::date;
BEGIN
    RETURN jsonb_build_object(
        'range_days', 30,
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
        'pareto', (SELECT coalesce(jsonb_agg(to_jsonb(t) ORDER BY t.total_out DESC, t.name), '[]') FROM (
            SELECT i.name, u.symbol AS unit, sum(s.quantity) AS total_out FROM simbar.stock_out s
            JOIN simbar.items i ON i.id = s.item_id JOIN simbar.units u ON u.id = i.unit_id
            WHERE s.date BETWEEN start_date AND today GROUP BY i.id, i.name, u.symbol ORDER BY total_out DESC, i.name LIMIT 10) t),
        'aging', (SELECT jsonb_build_object('0-30 hari', count(*) FILTER (WHERE age BETWEEN 0 AND 30),
                '31-60 hari', count(*) FILTER (WHERE age BETWEEN 31 AND 60), '61-90 hari', count(*) FILTER (WHERE age BETWEEN 61 AND 90),
                '>90 hari', count(*) FILTER (WHERE age > 90), 'Belum bergerak', count(*) FILTER (WHERE age IS NULL))
            FROM (SELECT today - greatest((SELECT max(date) FROM simbar.stock_in s WHERE s.item_id = i.id),
                    (SELECT max(date) FROM simbar.stock_out s WHERE s.item_id = i.id)) AS age FROM simbar.items i) aged)
    );
END;
$function$;

REVOKE ALL ON FUNCTION public.simbar_public_dashboard() FROM PUBLIC, anon, authenticated, service_role;
GRANT EXECUTE ON FUNCTION public.simbar_public_dashboard() TO anon, authenticated;

COMMIT;
