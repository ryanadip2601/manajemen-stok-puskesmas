-- Rapikan data: merge duplicate barang, remember pairs that are real variants, fix suspicious asset quantities,
-- give assets a nomor register, and list assets for printing labels. Requires ops-core.sql and ops-menus.sql. Safe to re-run.

BEGIN;

-- Pairs of barang the administrator marked as different variants (e.g. Tinta 664 Y and M), so they are not suggested again.
CREATE TABLE IF NOT EXISTS simbar.item_pair_ignored (
    item_a bigint NOT NULL REFERENCES simbar.items(id) ON DELETE CASCADE,
    item_b bigint NOT NULL REFERENCES simbar.items(id) ON DELETE CASCADE,
    created_at timestamp without time zone,
    PRIMARY KEY (item_a, item_b),
    CHECK (item_a < item_b)
);
ALTER TABLE simbar.item_pair_ignored ENABLE ROW LEVEL SECURITY;
REVOKE ALL ON TABLE simbar.item_pair_ignored FROM PUBLIC, anon, authenticated;

CREATE OR REPLACE FUNCTION public.simbar_item_pairs_ignored()
RETURNS jsonb
LANGUAGE plpgsql STABLE SECURITY DEFINER SET search_path = ''
AS $function$
BEGIN
    PERFORM simbar.require_menu(ARRAY['catalog']);
    RETURN (SELECT coalesce(jsonb_agg(jsonb_build_array(item_a, item_b)), '[]'::jsonb) FROM simbar.item_pair_ignored);
END;
$function$;

CREATE OR REPLACE FUNCTION public.simbar_item_pair_ignore(first_id bigint, second_id bigint)
RETURNS void
LANGUAGE plpgsql SECURITY DEFINER SET search_path = ''
AS $function$
DECLARE
    actor simbar.users%ROWTYPE := simbar.require_menu(ARRAY['catalog']);
BEGIN
    IF first_id IS NULL OR second_id IS NULL OR first_id = second_id THEN
        RAISE invalid_parameter_value USING MESSAGE = 'Pilih dua barang yang berbeda.';
    END IF;
    INSERT INTO simbar.item_pair_ignored (item_a, item_b, created_at)
    VALUES (least(first_id, second_id), greatest(first_id, second_id), simbar.local_now())
    ON CONFLICT DO NOTHING;
END;
$function$;

-- Administrator only: fold one barang into another. Every transaction and request moves to the kept barang,
-- the stock is added up, and the other barang is removed. Both must use the same satuan.
CREATE OR REPLACE FUNCTION public.simbar_item_merge(keep_id bigint, drop_id bigint)
RETURNS bigint
LANGUAGE plpgsql SECURITY DEFINER SET search_path = ''
AS $function$
DECLARE
    actor simbar.users%ROWTYPE := simbar.require_profile(ARRAY['admin']);
    kept simbar.items%ROWTYPE;
    dropped simbar.items%ROWTYPE;
BEGIN
    IF keep_id IS NULL OR drop_id IS NULL OR keep_id = drop_id THEN
        RAISE invalid_parameter_value USING MESSAGE = 'Pilih dua barang yang berbeda.';
    END IF;
    SELECT * INTO kept FROM simbar.items WHERE id = keep_id FOR UPDATE;
    SELECT * INTO dropped FROM simbar.items WHERE id = drop_id FOR UPDATE;
    IF kept.id IS NULL OR dropped.id IS NULL THEN
        RAISE no_data_found USING MESSAGE = 'Barang tidak ditemukan.';
    END IF;
    IF kept.unit_id <> dropped.unit_id THEN
        RAISE check_violation USING MESSAGE = 'Satuan kedua barang berbeda. Samakan satuannya dulu lewat Ubah barang, lalu gabungkan.';
    END IF;
    UPDATE simbar.stock_in SET item_id = keep_id WHERE item_id = drop_id;
    UPDATE simbar.stock_out SET item_id = keep_id WHERE item_id = drop_id;
    UPDATE simbar.stock_out_requests SET item_id = keep_id WHERE item_id = drop_id;
    UPDATE simbar.permintaan_barang SET item_id = keep_id WHERE item_id = drop_id;
    UPDATE simbar.permintaan_barang SET created_item_id = keep_id WHERE created_item_id = drop_id;
    UPDATE simbar.permintaan_restok SET item_id = keep_id WHERE item_id = drop_id;
    UPDATE simbar.usulan_permintaan_barang SET item_id = keep_id WHERE item_id = drop_id;
    UPDATE simbar.items SET stock = stock + dropped.stock, updated_at = simbar.local_now() WHERE id = keep_id;
    DELETE FROM simbar.items WHERE id = drop_id;
    PERFORM simbar.write_log(actor.id, 'item_merged', actor.name || ' menggabungkan barang ' || dropped.name || ' (' || dropped.code
        || ', stok ' || dropped.stock || ') ke ' || kept.name || ' (' || kept.code || ')');
    RETURN keep_id;
END;
$function$;

-- "Sudah benar" on the jumlah check: the asset is not listed again (a corrected jumlah also sets it).
ALTER TABLE simbar.barang_aset ADD COLUMN IF NOT EXISTS jumlah_dicek boolean NOT NULL DEFAULT false;

-- Why an asset's jumlah deserves a look, or NULL when it looks fine:
--   tahun   jumlah > 100: the year typed into jumlah (always listed; skipped by numbering and labels)
--   banyak  20 or more units of one asset in one room
--   mahal   3 or more units at Rp 5 juta or more each (the total price may have been multiplied)
--   tunggal 3 or more units of equipment a room normally has one of (mesin absen, server, brankas, dental chair, ...)
CREATE OR REPLACE FUNCTION simbar.jumlah_alasan(a simbar.barang_aset)
RETURNS text
LANGUAGE sql IMMUTABLE
AS $function$
    SELECT CASE WHEN a.jumlah > 100 THEN 'tahun'
                WHEN a.jumlah_dicek THEN NULL
                WHEN a.jumlah >= 20 THEN 'banyak'
                WHEN a.jumlah >= 3 AND coalesce(a.harga_perolehan, 0) >= 5000000 THEN 'mahal'
                WHEN a.jumlah >= 3 AND a.nama_aset ~* '(absen|server|brankas|dental chair|hematolog|anali[sz]er|analyzer|proyektor|projector|lcd|sterili[sz]ator|autoclave|lemari obat putar|mesin pembuat|doppler|cctv|dvr|modem|microtoi|ukuran tinggi|kulkas|lemari es|freezer|\mac\M|air conditioner|genset|mesin cuci|televisi|\mtv\M|\musg\M|\mekg\M|\mecg\M|nebuli[sz]er|centrifuge|mikroskop|microscope|rontgen|x-ray|dispenser)' THEN 'tunggal'
           END;
$function$;

-- Assets whose jumlah looks wrong, with the reason. Those with jumlah > 100 are skipped by numbering and labels.
CREATE OR REPLACE FUNCTION public.simbar_aset_suspicious()
RETURNS jsonb
LANGUAGE plpgsql STABLE SECURITY DEFINER SET search_path = ''
AS $function$
BEGIN
    PERFORM simbar.require_profile(ARRAY['admin', 'admin_gudang']);
    RETURN (SELECT coalesce(jsonb_agg(jsonb_build_object('id', a.id, 'kode', a.kode_aset, 'nama', a.nama_aset, 'jumlah', a.jumlah,
            'tahun', a.tahun_perolehan, 'ruangan', r.nama_ruangan, 'harga', a.harga_perolehan, 'register', a.nomor_register,
            'alasan', simbar.jumlah_alasan(a))
            ORDER BY CASE simbar.jumlah_alasan(a) WHEN 'tahun' THEN 0 WHEN 'mahal' THEN 1 WHEN 'tunggal' THEN 2 ELSE 3 END, a.jumlah DESC, a.id), '[]'::jsonb)
        FROM simbar.barang_aset a LEFT JOIN simbar.ruangan r ON r.id = a.ruangan_id WHERE simbar.jumlah_alasan(a) IS NOT NULL);
END;
$function$;

-- "Sudah benar": keep the jumlah and stop listing the asset. Not for jumlah > 100, which always needs a fix.
CREATE OR REPLACE FUNCTION public.simbar_aset_jumlah_ok(aset_id bigint)
RETURNS void
LANGUAGE plpgsql SECURITY DEFINER SET search_path = ''
AS $function$
DECLARE
    actor simbar.users%ROWTYPE := simbar.require_profile(ARRAY['admin', 'admin_gudang']);
    aset simbar.barang_aset%ROWTYPE;
BEGIN
    SELECT * INTO aset FROM simbar.barang_aset WHERE id = aset_id FOR UPDATE;
    IF NOT FOUND THEN
        RAISE no_data_found USING MESSAGE = 'Aset tidak ditemukan.';
    ELSIF aset.jumlah > 100 THEN
        RAISE check_violation USING MESSAGE = 'Jumlah lebih dari 100 harus diperbaiki dulu.';
    END IF;
    UPDATE simbar.barang_aset SET jumlah_dicek = true, updated_at = simbar.local_now() WHERE id = aset_id;
    PERFORM simbar.write_log(actor.id, 'aset_jumlah_checked', actor.name || ' memastikan jumlah aset ' || aset.kode_aset || ' (' || aset.jumlah || ') sudah benar');
END;
$function$;

CREATE OR REPLACE FUNCTION public.simbar_aset_fix_jumlah(aset_id bigint, new_jumlah integer, new_tahun integer DEFAULT NULL)
RETURNS void
LANGUAGE plpgsql SECURITY DEFINER SET search_path = ''
AS $function$
DECLARE
    actor simbar.users%ROWTYPE := simbar.require_profile(ARRAY['admin', 'admin_gudang']);
    aset simbar.barang_aset%ROWTYPE;
BEGIN
    IF new_jumlah IS NULL OR new_jumlah NOT BETWEEN 1 AND 10000 THEN
        RAISE invalid_parameter_value USING MESSAGE = 'Jumlah harus 1 sampai 10.000.';
    END IF;
    IF new_tahun IS NOT NULL AND new_tahun NOT BETWEEN 1950 AND 2100 THEN
        RAISE invalid_parameter_value USING MESSAGE = 'Tahun perolehan tidak valid.';
    END IF;
    SELECT * INTO aset FROM simbar.barang_aset WHERE id = aset_id FOR UPDATE;
    IF NOT FOUND THEN
        RAISE no_data_found USING MESSAGE = 'Aset tidak ditemukan.';
    END IF;
    UPDATE simbar.barang_aset SET jumlah = new_jumlah, tahun_perolehan = coalesce(new_tahun, tahun_perolehan),
        -- A register range made for the old jumlah no longer fits.
        nomor_register = CASE WHEN new_jumlah <> aset.jumlah AND position('-' in coalesce(nomor_register, '')) > 0 THEN NULL ELSE nomor_register END,
        jumlah_dicek = true, updated_at = simbar.local_now()
    WHERE id = aset_id;
    PERFORM simbar.write_log(actor.id, 'aset_jumlah_fixed', actor.name || ' memperbaiki jumlah aset ' || aset.kode_aset || ' dari '
        || aset.jumlah || ' menjadi ' || new_jumlah || coalesce(', tahun ' || new_tahun, ''));
END;
$function$;

-- Highest register number already used inside a kategori ("0007" -> 7, "0003-0005" -> 5).
CREATE OR REPLACE FUNCTION simbar.register_last(kategori_ref bigint)
RETURNS integer
LANGUAGE sql STABLE SECURITY DEFINER SET search_path = ''
AS $function$
    SELECT coalesce(max((regexp_match(nomor_register, '(\d+)\s*$'))[1]::integer), 0)
    FROM simbar.barang_aset
    WHERE kategori_aset_id IS NOT DISTINCT FROM kategori_ref AND nomor_register ~ '\d\s*$' AND length((regexp_match(nomor_register, '(\d+)\s*$'))[1]) <= 6;
$function$;

-- How many assets still need a nomor register, and how many are held back by a suspicious jumlah.
CREATE OR REPLACE FUNCTION public.simbar_aset_register_status()
RETURNS jsonb
LANGUAGE plpgsql STABLE SECURITY DEFINER SET search_path = ''
AS $function$
BEGIN
    PERFORM simbar.require_profile(ARRAY['admin', 'admin_gudang']);
    RETURN (SELECT jsonb_build_object(
        'total', count(*),
        'tanpa_register', count(*) FILTER (WHERE coalesce(btrim(nomor_register), '') = '' AND jumlah <= 100),
        'ditahan', count(*) FILTER (WHERE coalesce(btrim(nomor_register), '') = '' AND jumlah > 100),
        'unit_tanpa_register', coalesce(sum(jumlah) FILTER (WHERE coalesce(btrim(nomor_register), '') = '' AND jumlah <= 100), 0))
        FROM simbar.barang_aset);
END;
$function$;

-- Give every asset without a nomor register the next free numbers in its kategori: "0004" for one unit,
-- "0005-0007" for three. Oldest assets first. Assets with a suspicious jumlah (> 100) are skipped.
CREATE OR REPLACE FUNCTION public.simbar_aset_register_assign()
RETURNS jsonb
LANGUAGE plpgsql SECURITY DEFINER SET search_path = ''
AS $function$
DECLARE
    actor simbar.users%ROWTYPE := simbar.require_profile(ARRAY['admin', 'admin_gudang']);
    kategori record;
    aset record;
    next_number integer;
    assigned integer := 0;
    units integer := 0;
BEGIN
    LOCK TABLE simbar.barang_aset IN SHARE ROW EXCLUSIVE MODE;
    FOR kategori IN SELECT DISTINCT kategori_aset_id AS id FROM simbar.barang_aset
                    WHERE coalesce(btrim(nomor_register), '') = '' AND jumlah BETWEEN 1 AND 100 LOOP
        next_number := simbar.register_last(kategori.id) + 1;
        FOR aset IN SELECT id, jumlah FROM simbar.barang_aset
                    WHERE kategori_aset_id IS NOT DISTINCT FROM kategori.id AND coalesce(btrim(nomor_register), '') = '' AND jumlah BETWEEN 1 AND 100
                    ORDER BY tahun_perolehan NULLS LAST, tanggal_masuk NULLS LAST, id LOOP
            UPDATE simbar.barang_aset SET
                nomor_register = CASE WHEN aset.jumlah = 1 THEN lpad(next_number::text, 4, '0')
                    ELSE lpad(next_number::text, 4, '0') || '-' || lpad((next_number + aset.jumlah - 1)::text, 4, '0') END,
                updated_at = simbar.local_now()
            WHERE id = aset.id;
            next_number := next_number + aset.jumlah;
            assigned := assigned + 1;
            units := units + aset.jumlah;
        END LOOP;
    END LOOP;
    IF assigned > 0 THEN
        PERFORM simbar.write_log(actor.id, 'aset_register_assigned', actor.name || ' memberi nomor register untuk ' || assigned
            || ' aset (' || units || ' unit)');
    END IF;
    RETURN jsonb_build_object('aset', assigned, 'unit', units);
END;
$function$;

-- Assets for the label sheet. filters: {ruangan, kategori, search}. Every unit of an asset becomes one label on the page.
CREATE OR REPLACE FUNCTION public.simbar_aset_labels(filters jsonb DEFAULT '{}')
RETURNS jsonb
LANGUAGE plpgsql STABLE SECURITY DEFINER SET search_path = ''
AS $function$
DECLARE
    search_text text := btrim(coalesce(filters->>'search', ''));
    pattern text;
BEGIN
    PERFORM simbar.require_profile(ARRAY['admin', 'admin_gudang']);
    IF char_length(search_text) > 100 THEN
        RAISE invalid_parameter_value USING MESSAGE = 'Pencarian terlalu panjang.';
    END IF;
    pattern := '%' || replace(replace(replace(search_text, '!', '!!'), '%', '!%'), '_', '!_') || '%';
    RETURN (SELECT coalesce(jsonb_agg(jsonb_build_object('id', a.id, 'kode', a.kode_aset, 'nama', a.nama_aset, 'merk', a.merk,
            'jumlah', a.jumlah, 'register', a.nomor_register, 'tahun', a.tahun_perolehan, 'ruangan', r.nama_ruangan,
            'kategori', k.nama_kategori) ORDER BY r.nama_ruangan NULLS LAST, a.nama_aset, a.id), '[]'::jsonb)
        FROM simbar.barang_aset a
        LEFT JOIN simbar.ruangan r ON r.id = a.ruangan_id
        LEFT JOIN simbar.kategori_aset k ON k.id = a.kategori_aset_id
        WHERE a.jumlah <= 100
          AND (nullif(filters->>'ruangan', '') IS NULL OR a.ruangan_id = (filters->>'ruangan')::bigint)
          AND (nullif(filters->>'kategori', '') IS NULL OR a.kategori_aset_id = (filters->>'kategori')::bigint)
          AND (search_text = '' OR a.nama_aset ILIKE pattern ESCAPE '!' OR a.kode_aset ILIKE pattern ESCAPE '!'));
END;
$function$;

DO $grants$
DECLARE
    signature text;
BEGIN
    FOREACH signature IN ARRAY ARRAY[
        'public.simbar_item_pairs_ignored()',
        'public.simbar_item_pair_ignore(bigint, bigint)',
        'public.simbar_item_merge(bigint, bigint)',
        'public.simbar_aset_suspicious()',
        'public.simbar_aset_fix_jumlah(bigint, integer, integer)',
        'public.simbar_aset_register_status()',
        'public.simbar_aset_register_assign()',
        'public.simbar_aset_labels(jsonb)'
    ] LOOP
        EXECUTE 'REVOKE ALL ON FUNCTION ' || signature || ' FROM PUBLIC, anon, authenticated, service_role';
        EXECUTE 'GRANT EXECUTE ON FUNCTION ' || signature || ' TO authenticated';
    END LOOP;
END;
$grants$;
REVOKE ALL ON FUNCTION simbar.register_last(bigint) FROM PUBLIC, anon, authenticated, service_role;
REVOKE ALL ON FUNCTION simbar.jumlah_alasan(simbar.barang_aset) FROM PUBLIC, anon, authenticated, service_role;
REVOKE ALL ON FUNCTION public.simbar_aset_jumlah_ok(bigint) FROM PUBLIC, anon, authenticated, service_role;
GRANT EXECUTE ON FUNCTION public.simbar_aset_jumlah_ok(bigint) TO authenticated;

COMMIT;
