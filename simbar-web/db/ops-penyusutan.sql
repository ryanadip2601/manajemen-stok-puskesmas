-- Penyusutan aset (straight line, monthly) for admin and admin_gudang. Safe to re-run. Requires ops-core.sql.
-- Ports PenyusutanService/PenyusutanController. Depreciation starts the month after acquisition and is capped
-- at (nilai perolehan - nilai residu); tanah and KDP are not depreciated.

BEGIN;

CREATE OR REPLACE FUNCTION simbar.hitung_penyusutan(aset_ref bigint, tanggal_hitung date)
RETURNS jsonb
LANGUAGE plpgsql STABLE SECURITY DEFINER SET search_path = ''
AS $function$
DECLARE
    a simbar.barang_aset%ROWTYPE;
    k simbar.kategori_aset%ROWTYPE;
    perolehan numeric;
    residu numeric;
    umur integer;
    per_tahun numeric;
    per_bulan numeric;
    bulan integer;
    akumulasi numeric;
    sisa integer;
BEGIN
    SELECT * INTO a FROM simbar.barang_aset WHERE id = aset_ref;
    IF NOT FOUND THEN
        RETURN NULL;
    END IF;
    SELECT * INTO k FROM simbar.kategori_aset WHERE id = a.kategori_aset_id;
    perolehan := coalesce(a.harga_perolehan, 0) + coalesce(a.nilai_penambahan, 0) - coalesce(a.nilai_pengurangan, 0) - coalesce(a.nilai_koreksi, 0);
    residu := coalesce(a.nilai_residu, 0);
    -- getUmurManfaat: a zero override or kategori value falls through, like PHP's truthiness check.
    umur := coalesce(nullif(a.umur_manfaat_override, 0), nullif(k.umur_manfaat, 0),
        CASE coalesce(k.golongan, 'peralatan_mesin') WHEN 'tanah' THEN 0 WHEN 'peralatan_mesin' THEN 5 WHEN 'gedung_bangunan' THEN 50
            WHEN 'jalan_irigasi' THEN 10 WHEN 'aset_tetap_lainnya' THEN 4 WHEN 'kdp' THEN 0 ELSE 5 END);

    IF (k.id IS NOT NULL AND (NOT k.dapat_disusutkan OR k.golongan IN ('tanah', 'kdp'))) OR umur <= 0 THEN
        RETURN jsonb_build_object('nilai_perolehan', perolehan, 'nilai_residu', residu, 'umur_manfaat', umur, 'dapat_disusutkan', false,
            'penyusutan_per_tahun', 0, 'penyusutan_per_bulan', 0, 'jumlah_bulan', 0, 'akumulasi_penyusutan', 0,
            'nilai_buku', perolehan, 'persentase_penyusutan', 0, 'sisa_umur_bulan', NULL, 'sudah_habis', false);
    END IF;

    per_tahun := (perolehan - residu) / umur;
    per_bulan := per_tahun / 12;
    bulan := greatest(0, (extract(year FROM tanggal_hitung) * 12 + extract(month FROM tanggal_hitung))::integer
        - (extract(year FROM a.tanggal_masuk) * 12 + extract(month FROM a.tanggal_masuk))::integer);
    akumulasi := least(per_bulan * bulan, perolehan - residu);
    sisa := greatest(0, umur * 12 - bulan);
    RETURN jsonb_build_object('nilai_perolehan', perolehan, 'nilai_residu', residu, 'umur_manfaat', umur, 'dapat_disusutkan', true,
        'penyusutan_per_tahun', round(per_tahun, 2), 'penyusutan_per_bulan', round(per_bulan, 2), 'jumlah_bulan', bulan,
        'akumulasi_penyusutan', round(akumulasi, 2), 'nilai_buku', round(greatest(0, perolehan - akumulasi), 2),
        'persentase_penyusutan', CASE WHEN perolehan > 0 THEN round(akumulasi / perolehan * 100, 2) ELSE 0 END,
        'sisa_umur_bulan', sisa, 'sudah_habis', sisa <= 0);
END;
$function$;

-- Realtime depreciation of every asset that matches filters ({golongan, ruangan, kategori, aktif_only}).
CREATE OR REPLACE FUNCTION simbar.penyusutan_rows(filters jsonb, tanggal_hitung date)
RETURNS TABLE (row_data jsonb)
LANGUAGE sql STABLE SECURITY DEFINER SET search_path = ''
AS $function$
    SELECT jsonb_build_object('id', a.id, 'kode_aset', a.kode_aset, 'nama_aset', a.nama_aset, 'kategori', k.nama_kategori,
            'golongan', k.golongan, 'ruangan', r.nama_ruangan, 'kondisi', a.kondisi, 'jumlah', a.jumlah, 'merk', a.merk,
            'sumber_dana', a.sumber_dana, 'tahun_perolehan', a.tahun_perolehan, 'tanggal_perolehan', a.tanggal_masuk)
        || simbar.hitung_penyusutan(a.id, tanggal_hitung)
    FROM simbar.barang_aset a
    JOIN simbar.kategori_aset k ON k.id = a.kategori_aset_id
    JOIN simbar.ruangan r ON r.id = a.ruangan_id
    WHERE (coalesce((filters->>'aktif_only')::boolean, true) = false
           OR (a.status_aset = 'aktif' AND a.harga_perolehan IS NOT NULL AND a.harga_perolehan > 0))
      AND (nullif(filters->>'golongan', '') IS NULL OR k.golongan = filters->>'golongan')
      AND (nullif(filters->>'ruangan', '') IS NULL OR a.ruangan_id = (filters->>'ruangan')::bigint)
      AND (nullif(filters->>'kategori', '') IS NULL OR a.kategori_aset_id = (filters->>'kategori')::bigint)
      AND (nullif(filters->>'kondisi', '') IS NULL OR a.kondisi = filters->>'kondisi')
    ORDER BY a.kode_aset;
$function$;

CREATE OR REPLACE FUNCTION public.simbar_penyusutan_summary(tahun_filter integer)
RETURNS jsonb
LANGUAGE plpgsql STABLE SECURITY DEFINER SET search_path = ''
AS $function$
BEGIN
    PERFORM simbar.require_menu(ARRAY['penyusutan']);
    RETURN jsonb_build_object(
        'tahun', tahun_filter,
        'years', (SELECT coalesce(jsonb_agg(DISTINCT tahun ORDER BY tahun DESC), '[]') FROM simbar.penyusutan_aset),
        'stored', (SELECT jsonb_build_object('total_aset', count(*), 'total_nilai_perolehan', coalesce(sum(nilai_perolehan), 0),
                'total_penyusutan', coalesce(sum(penyusutan_periode), 0), 'total_akumulasi', coalesce(sum(akumulasi_penyusutan_akhir), 0),
                'total_nilai_buku', coalesce(sum(nilai_buku_akhir), 0))
            FROM simbar.penyusutan_aset WHERE tahun = tahun_filter AND bulan IS NULL),
        'realtime', (SELECT jsonb_build_object('total_aset', count(*),
                'total_nilai_perolehan', coalesce(sum((row_data->>'nilai_perolehan')::numeric), 0),
                'total_akumulasi', coalesce(sum((row_data->>'akumulasi_penyusutan')::numeric), 0),
                'total_nilai_buku', coalesce(sum((row_data->>'nilai_buku')::numeric), 0),
                'aset_habis_umur', count(*) FILTER (WHERE (row_data->>'sudah_habis')::boolean))
            FROM simbar.penyusutan_rows('{}', simbar.local_today())),
        'golongan', (SELECT jsonb_agg(to_jsonb(t) - 'ord' ORDER BY t.ord) FROM (
            SELECT g.ord, g.golongan, g.umur AS umur_manfaat, count(p.id) AS jumlah_aset,
                   coalesce(sum(p.nilai_perolehan), 0) AS total_nilai_perolehan, coalesce(sum(p.akumulasi_penyusutan_akhir), 0) AS total_akumulasi,
                   coalesce(sum(p.nilai_buku_akhir), 0) AS total_nilai_buku, coalesce(sum(p.penyusutan_periode), 0) AS total_penyusutan_tahun
            FROM (VALUES (1, 'tanah', 0), (2, 'peralatan_mesin', 5), (3, 'gedung_bangunan', 50), (4, 'jalan_irigasi', 10),
                         (5, 'aset_tetap_lainnya', 4), (6, 'kdp', 0)) AS g(ord, golongan, umur)
            LEFT JOIN simbar.kategori_aset k ON k.golongan = g.golongan
            LEFT JOIN simbar.barang_aset a ON a.kategori_aset_id = k.id
            LEFT JOIN simbar.penyusutan_aset p ON p.barang_aset_id = a.id AND p.tahun = tahun_filter AND p.bulan IS NULL
            GROUP BY g.ord, g.golongan, g.umur) t),
        'tahun_perolehan', (SELECT coalesce(jsonb_agg(t ORDER BY t.tahun_perolehan DESC NULLS LAST), '[]') FROM (
            SELECT a.tahun_perolehan, count(*) AS jumlah_aset, sum(p.nilai_perolehan) AS total_nilai_perolehan,
                   sum(p.akumulasi_penyusutan_akhir) AS total_akumulasi, sum(p.nilai_buku_akhir) AS total_nilai_buku,
                   sum(p.penyusutan_periode) AS total_penyusutan
            FROM simbar.penyusutan_aset p JOIN simbar.barang_aset a ON a.id = p.barang_aset_id
            WHERE p.tahun = tahun_filter AND p.bulan IS NULL GROUP BY a.tahun_perolehan) t)
    );
END;
$function$;

-- Annual run (prosesPenyusutanTahunan): stores the 31 December position of every active asset with a price.
CREATE OR REPLACE FUNCTION public.simbar_penyusutan_proses(tahun_proses integer)
RETURNS jsonb
LANGUAGE plpgsql SECURITY DEFINER SET search_path = ''
AS $function$
DECLARE
    actor simbar.users%ROWTYPE := simbar.require_menu(ARRAY['penyusutan']);
    tanggal date;
    item record;
    h jsonb;
    awal numeric;
    total integer := 0;
    processed integer := 0;
    skipped integer := 0;
    total_penyusutan numeric := 0;
BEGIN
    IF tahun_proses IS NULL OR tahun_proses < 2000 OR tahun_proses > extract(year FROM simbar.local_today()) + 1 THEN
        RAISE check_violation USING MESSAGE = 'Tahun penyusutan tidak valid.';
    END IF;
    tanggal := make_date(tahun_proses, 12, 31);
    PERFORM pg_advisory_xact_lock(hashtext('simbar.penyusutan_aset.proses'));

    FOR item IN SELECT a.* FROM simbar.barang_aset a
        WHERE a.status_aset = 'aktif' AND a.harga_perolehan IS NOT NULL AND a.harga_perolehan > 0 ORDER BY a.id
    LOOP
        total := total + 1;
        h := simbar.hitung_penyusutan(item.id, tanggal);
        IF NOT (h->>'dapat_disusutkan')::boolean THEN
            skipped := skipped + 1;
            CONTINUE;
        END IF;
        SELECT coalesce((SELECT akumulasi_penyusutan_akhir FROM simbar.penyusutan_aset
            WHERE barang_aset_id = item.id AND tahun = tahun_proses - 1 AND bulan IS NULL LIMIT 1), 0) INTO awal;

        UPDATE simbar.penyusutan_aset SET tanggal_hitung = tanggal, nilai_perolehan = (h->>'nilai_perolehan')::numeric,
            nilai_residu = (h->>'nilai_residu')::numeric, umur_manfaat = (h->>'umur_manfaat')::integer,
            penyusutan_per_tahun = (h->>'penyusutan_per_tahun')::numeric, penyusutan_per_bulan = (h->>'penyusutan_per_bulan')::numeric,
            penyusutan_periode = (h->>'penyusutan_per_tahun')::numeric, akumulasi_penyusutan_awal = awal,
            akumulasi_penyusutan_akhir = (h->>'akumulasi_penyusutan')::numeric,
            nilai_buku_awal = (h->>'nilai_perolehan')::numeric - awal, nilai_buku_akhir = (h->>'nilai_buku')::numeric,
            mutasi_penambahan = coalesce(item.nilai_penambahan, 0), mutasi_pengurangan = coalesce(item.nilai_pengurangan, 0),
            mutasi_koreksi = coalesce(item.nilai_koreksi, 0), metode_penyusutan = 'garis_lurus', created_by = actor.id,
            updated_at = simbar.local_now()
        WHERE barang_aset_id = item.id AND tahun = tahun_proses AND bulan IS NULL;
        IF NOT FOUND THEN
            INSERT INTO simbar.penyusutan_aset (barang_aset_id, tahun, bulan, tanggal_hitung, nilai_perolehan, nilai_residu, umur_manfaat,
                penyusutan_per_tahun, penyusutan_per_bulan, penyusutan_periode, akumulasi_penyusutan_awal, akumulasi_penyusutan_akhir,
                nilai_buku_awal, nilai_buku_akhir, mutasi_penambahan, mutasi_pengurangan, mutasi_koreksi, metode_penyusutan,
                created_by, created_at, updated_at)
            VALUES (item.id, tahun_proses, NULL, tanggal, (h->>'nilai_perolehan')::numeric, (h->>'nilai_residu')::numeric,
                (h->>'umur_manfaat')::integer, (h->>'penyusutan_per_tahun')::numeric, (h->>'penyusutan_per_bulan')::numeric,
                (h->>'penyusutan_per_tahun')::numeric, awal, (h->>'akumulasi_penyusutan')::numeric,
                (h->>'nilai_perolehan')::numeric - awal, (h->>'nilai_buku')::numeric, coalesce(item.nilai_penambahan, 0),
                coalesce(item.nilai_pengurangan, 0), coalesce(item.nilai_koreksi, 0), 'garis_lurus', actor.id,
                simbar.local_now(), simbar.local_now());
        END IF;
        processed := processed + 1;
        total_penyusutan := total_penyusutan + (h->>'penyusutan_per_tahun')::numeric;
    END LOOP;

    PERFORM simbar.write_log(actor.id, 'proses_penyusutan', 'Proses penyusutan tahun ' || tahun_proses || ': ' || processed
        || ' aset diproses, total penyusutan Rp ' || replace(to_char(round(total_penyusutan), 'FM999,999,999,999,990'), ',', '.'));
    RETURN jsonb_build_object('total_aset', total, 'aset_diproses', processed, 'aset_skip', skipped, 'total_penyusutan', total_penyusutan);
END;
$function$;

-- Realtime list for the detail report and for the barang aset CSV export. tanggal NULL means today.
CREATE OR REPLACE FUNCTION public.simbar_penyusutan_detail(filters jsonb DEFAULT '{}', tanggal date DEFAULT NULL)
RETURNS jsonb
LANGUAGE plpgsql STABLE SECURITY DEFINER SET search_path = ''
AS $function$
BEGIN
    PERFORM simbar.require_menu(ARRAY['penyusutan']);
    RETURN (SELECT coalesce(jsonb_agg(row_data), '[]') FROM simbar.penyusutan_rows(coalesce(filters, '{}'), coalesce(tanggal, simbar.local_today())));
END;
$function$;

-- Kartu aset: today's position plus every stored period.
CREATE OR REPLACE FUNCTION public.simbar_penyusutan_kartu(aset_id bigint)
RETURNS jsonb
LANGUAGE plpgsql STABLE SECURITY DEFINER SET search_path = ''
AS $function$
DECLARE
    result jsonb;
BEGIN
    PERFORM simbar.require_menu(ARRAY['penyusutan']);
    SELECT (SELECT row_data FROM simbar.penyusutan_rows('{"aktif_only": false}', simbar.local_today()) WHERE (row_data->>'id')::bigint = aset_id)
        || jsonb_build_object('riwayat', (SELECT coalesce(jsonb_agg(jsonb_build_object('tahun', tahun, 'bulan', bulan,
                'nilai_perolehan', nilai_perolehan, 'penyusutan_periode', penyusutan_periode, 'akumulasi_awal', akumulasi_penyusutan_awal,
                'akumulasi_akhir', akumulasi_penyusutan_akhir, 'nilai_buku_akhir', nilai_buku_akhir, 'umur_manfaat', umur_manfaat)
            ORDER BY tahun DESC, bulan DESC NULLS FIRST), '[]') FROM simbar.penyusutan_aset WHERE barang_aset_id = aset_id))
    INTO result;
    IF result IS NULL OR NOT result ? 'id' THEN
        RAISE no_data_found USING MESSAGE = 'Aset tidak ditemukan.';
    END IF;
    RETURN result;
END;
$function$;

-- Stored annual rows for the CSV export (exportData).
CREATE OR REPLACE FUNCTION public.simbar_penyusutan_export(tahun_filter integer)
RETURNS jsonb
LANGUAGE plpgsql STABLE SECURITY DEFINER SET search_path = ''
AS $function$
DECLARE
    actor simbar.users%ROWTYPE := simbar.require_menu(ARRAY['penyusutan']);
BEGIN
    RETURN (SELECT coalesce(jsonb_agg(jsonb_build_object('kode_aset', a.kode_aset, 'nama_aset', a.nama_aset, 'kategori', k.nama_kategori,
            'golongan', k.golongan, 'ruangan', r.nama_ruangan, 'tahun_perolehan', a.tahun_perolehan, 'nilai_perolehan', p.nilai_perolehan,
            'umur_manfaat', p.umur_manfaat, 'penyusutan_per_tahun', p.penyusutan_per_tahun, 'akumulasi_penyusutan', p.akumulasi_penyusutan_akhir,
            'nilai_buku', p.nilai_buku_akhir) ORDER BY a.kode_aset), '[]')
        FROM simbar.penyusutan_aset p
        JOIN simbar.barang_aset a ON a.id = p.barang_aset_id
        JOIN simbar.kategori_aset k ON k.id = a.kategori_aset_id
        JOIN simbar.ruangan r ON r.id = a.ruangan_id
        WHERE p.tahun = tahun_filter AND p.bulan IS NULL);
END;
$function$;

REVOKE ALL ON FUNCTION simbar.hitung_penyusutan(bigint, date) FROM PUBLIC, anon, authenticated, service_role;
REVOKE ALL ON FUNCTION simbar.penyusutan_rows(jsonb, date) FROM PUBLIC, anon, authenticated, service_role;

DO $grants$
DECLARE
    signature text;
BEGIN
    FOREACH signature IN ARRAY ARRAY[
        'public.simbar_penyusutan_summary(integer)',
        'public.simbar_penyusutan_proses(integer)',
        'public.simbar_penyusutan_detail(jsonb, date)',
        'public.simbar_penyusutan_kartu(bigint)',
        'public.simbar_penyusutan_export(integer)'
    ] LOOP
        EXECUTE 'REVOKE ALL ON FUNCTION ' || signature || ' FROM PUBLIC, anon, authenticated, service_role';
        EXECUTE 'GRANT EXECUTE ON FUNCTION ' || signature || ' TO authenticated';
    END LOOP;
END;
$grants$;

COMMIT;
