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

-- Kartu aset: what a scanned label shows without signing in. The label's own data, condition, status, kalibrasi
-- and the dates of the last maintenance. No money, no people (petugas, teknisi, pencatat), no photo, no notes.
-- Changing anything still needs the app and a signed-in, approved profile (simbar_aset_get and the save functions).
CREATE OR REPLACE FUNCTION public.simbar_aset_public(aset_id bigint)
RETURNS jsonb
LANGUAGE plpgsql STABLE SECURITY DEFINER SET search_path = ''
AS $function$
DECLARE
    result jsonb;
BEGIN
    SELECT jsonb_build_object('id', a.id, 'kode_aset', a.kode_aset, 'nama_aset', a.nama_aset, 'merk', a.merk,
            'kategori', k.nama_kategori, 'golongan', k.golongan, 'ruangan', r.nama_ruangan,
            'kondisi', a.kondisi, 'butuh_perbaikan', a.butuh_perbaikan, 'status_aset', a.status_aset, 'jumlah', a.jumlah,
            'tahun_perolehan', a.tahun_perolehan, 'nomor_register', a.nomor_register,
            'spesifikasi', a.spesifikasi, 'ukuran_konstruksi', a.ukuran_konstruksi,
            -- Whether a photo exists (the picture itself comes through /api/media?aset=<id>) and, without one,
            -- the kode of the same-name asset whose photo is shown as an example.
            'foto', coalesce(a.foto, '') <> '',
            'contoh', CASE WHEN coalesce(a.foto, '') = '' THEN simbar.aset_contoh(a.nama_aset, a.id)->>'kode' END,
            'kalibrasi_tanggal', kal.tanggal, 'kalibrasi_hasil', kal.hasil,
            'kalibrasi_berikutnya', (kal.tanggal + interval '1 year')::date,
            'kalibrasi_status', CASE WHEN kal.tanggal IS NULL THEN CASE WHEN k.nama_kategori ILIKE 'alat kesehatan%' THEN 'belum' END
                WHEN (kal.tanggal + interval '1 year')::date < simbar.local_today() THEN 'lewat'
                WHEN (kal.tanggal + interval '1 year')::date <= simbar.local_today() + 30 THEN 'segera'
                ELSE 'berlaku' END,
            'pemeliharaan', (SELECT coalesce(jsonb_agg(jsonb_build_object('id', p.id, 'tanggal', p.tanggal_pemeliharaan,
                    'jenis', p.jenis_pemeliharaan, 'status', p.status, 'hasil', p.hasil_kalibrasi)
                ORDER BY p.tanggal_pemeliharaan DESC, p.id DESC), '[]')
                FROM (SELECT * FROM simbar.pemeliharaan_aset WHERE barang_aset_id = a.id
                      ORDER BY tanggal_pemeliharaan DESC, id DESC LIMIT 5) p))
    INTO result
    FROM simbar.barang_aset a
    JOIN simbar.kategori_aset k ON k.id = a.kategori_aset_id
    JOIN simbar.ruangan r ON r.id = a.ruangan_id
    LEFT JOIN LATERAL (SELECT p.tanggal_pemeliharaan AS tanggal, p.hasil_kalibrasi AS hasil FROM simbar.pemeliharaan_aset p
        WHERE p.barang_aset_id = a.id AND p.jenis_pemeliharaan = 'kalibrasi' AND p.status = 'selesai'
        ORDER BY p.tanggal_pemeliharaan DESC, p.id DESC LIMIT 1) kal ON true
    WHERE a.id = aset_id;
    IF result IS NULL THEN
        RAISE no_data_found USING MESSAGE = 'Aset tidak ditemukan.';
    END IF;
    RETURN result;
END;
$function$;

-- The photo shown on the public asset card: the asset's own photo, or the example photo from a same-name asset.
-- Only ever the photo of the asset asked for, never another reference; NULL when there is nothing to show.
CREATE OR REPLACE FUNCTION public.simbar_aset_public_foto(aset_id bigint)
RETURNS jsonb
LANGUAGE plpgsql STABLE SECURITY DEFINER SET search_path = ''
AS $function$
DECLARE
    reference text;
BEGIN
    SELECT CASE WHEN coalesce(a.foto, '') <> '' THEN a.foto ELSE simbar.aset_contoh(a.nama_aset, a.id)->>'foto' END
    INTO reference
    FROM simbar.barang_aset a WHERE a.id = aset_id;
    IF reference IS NULL THEN
        RETURN NULL;
    END IF;
    RETURN (SELECT jsonb_build_object('publicId', cloudinary_public_id, 'format', format, 'size', file_size)
        FROM simbar.media_objects WHERE legacy_reference = reference);
END;
$function$;

REVOKE ALL ON FUNCTION public.simbar_public_dashboard() FROM PUBLIC, anon, authenticated, service_role;
GRANT EXECUTE ON FUNCTION public.simbar_public_dashboard() TO anon, authenticated;
REVOKE ALL ON FUNCTION public.simbar_aset_public(bigint) FROM PUBLIC, anon, authenticated, service_role;
GRANT EXECUTE ON FUNCTION public.simbar_aset_public(bigint) TO anon, authenticated;
REVOKE ALL ON FUNCTION public.simbar_aset_public_foto(bigint) FROM PUBLIC, anon, authenticated, service_role;
GRANT EXECUTE ON FUNCTION public.simbar_aset_public_foto(bigint) TO anon, authenticated;

COMMIT;
