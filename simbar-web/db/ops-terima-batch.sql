-- Terima barang (batch): several barang habis pakai received in one go, each recognised by scanning the factory
-- barcode on its package. One barcode per barang (items.barcode); a different merk is a different barang with its
-- own barcode. Requires ops-core.sql, ops-menus.sql and ops-stock.sql. Safe to re-run.

BEGIN;

CREATE INDEX IF NOT EXISTS items_barcode_idx ON simbar.items (barcode) WHERE barcode IS NOT NULL;

-- The barang that owns a scanned barcode, or NULL when no barang has it yet.
CREATE OR REPLACE FUNCTION public.simbar_item_by_barcode(scanned text)
RETURNS jsonb
LANGUAGE plpgsql STABLE SECURITY DEFINER SET search_path = ''
AS $function$
DECLARE
    wanted text := btrim(coalesce(scanned, ''));
BEGIN
    PERFORM simbar.require_menu(ARRAY['in']);
    IF char_length(wanted) NOT BETWEEN 4 AND 100 THEN
        RAISE invalid_parameter_value USING MESSAGE = 'Barcode tidak valid (4 sampai 100 karakter).';
    END IF;
    RETURN (SELECT jsonb_build_object('id', i.id, 'code', i.code, 'name', i.name, 'category', c.name, 'photo', i.photo,
            'photoSource', i.photo_source, 'stock', i.stock, 'unit', u.symbol)
        FROM simbar.items i JOIN simbar.units u ON u.id = i.unit_id LEFT JOIN simbar.categories c ON c.id = i.category_id
        WHERE btrim(i.barcode) = wanted ORDER BY i.id LIMIT 1);
END;
$function$;

-- Remember a scanned barcode on a barang that has none yet, so the next scan finds it.
CREATE OR REPLACE FUNCTION public.simbar_item_link_barcode(item_ref bigint, scanned text)
RETURNS void
LANGUAGE plpgsql SECURITY DEFINER SET search_path = ''
AS $function$
DECLARE
    actor simbar.users%ROWTYPE := simbar.require_menu(ARRAY['in']);
    wanted text := btrim(coalesce(scanned, ''));
    item simbar.items%ROWTYPE;
    other text;
BEGIN
    IF char_length(wanted) NOT BETWEEN 4 AND 100 OR wanted ~ '[[:cntrl:]]' THEN
        RAISE invalid_parameter_value USING MESSAGE = 'Barcode tidak valid (4 sampai 100 karakter).';
    END IF;
    SELECT * INTO item FROM simbar.items WHERE id = item_ref FOR UPDATE;
    IF NOT FOUND THEN
        RAISE no_data_found USING MESSAGE = 'Barang tidak ditemukan.';
    END IF;
    SELECT name INTO other FROM simbar.items WHERE btrim(barcode) = wanted AND id <> item_ref ORDER BY id LIMIT 1;
    IF other IS NOT NULL THEN
        RAISE unique_violation USING MESSAGE = 'Barcode ini sudah dipakai barang "' || other || '".';
    ELSIF nullif(btrim(item.barcode), '') IS NOT NULL AND btrim(item.barcode) <> wanted THEN
        RAISE check_violation USING MESSAGE = 'Barang "' || item.name || '" sudah punya barcode lain (' || btrim(item.barcode)
            || '). Merk berbeda dicatat sebagai barang baru, atau ubah barcode lewat Data Barang.';
    END IF;
    UPDATE simbar.items SET barcode = wanted, updated_at = simbar.local_now() WHERE id = item_ref;
    PERFORM simbar.write_log(actor.id, 'item_barcode_linked', 'Barcode ' || wanted || ' dihubungkan ke barang "' || item.name || '"');
END;
$function$;

-- lines: [{"item": <id>, "quantity": <n>}, ...]. Every line becomes a normal barang masuk; all or nothing.
CREATE OR REPLACE FUNCTION public.simbar_stock_in_batch(movement_date date, movement_notes text, lines jsonb)
RETURNS jsonb
LANGUAGE plpgsql SECURITY DEFINER SET search_path = ''
AS $function$
DECLARE
    line jsonb;
    seen bigint[] := '{}';
    item_ref bigint;
    quantity integer;
    saved integer := 0;
    units bigint := 0;
BEGIN
    PERFORM simbar.require_menu(ARRAY['in']);
    IF lines IS NULL OR jsonb_typeof(lines) <> 'array' OR jsonb_array_length(lines) NOT BETWEEN 1 AND 200 THEN
        RAISE check_violation USING MESSAGE = 'Daftar barang kosong atau lebih dari 200 baris.';
    ELSIF movement_date IS NULL OR movement_date > simbar.local_today() THEN
        RAISE check_violation USING MESSAGE = 'Tanggal harus diisi dan tidak melebihi hari ini.';
    END IF;
    FOR line IN SELECT * FROM jsonb_array_elements(lines) LOOP
        IF coalesce(line->>'item', '') !~ '^[0-9]{1,15}$' OR coalesce(line->>'quantity', '') !~ '^[0-9]{1,7}$' THEN
            RAISE check_violation USING MESSAGE = 'Ada baris dengan barang atau jumlah yang tidak valid.';
        END IF;
        item_ref := (line->>'item')::bigint;
        quantity := (line->>'quantity')::integer;
        IF item_ref = ANY (seen) THEN
            RAISE check_violation USING MESSAGE = 'Ada barang yang tercatat dua kali. Gabungkan jumlahnya dalam satu baris.';
        END IF;
        seen := seen || item_ref;
        PERFORM public.simbar_stock_save('in', NULL, item_ref, quantity, movement_date, movement_notes);
        saved := saved + 1;
        units := units + quantity;
    END LOOP;
    RETURN jsonb_build_object('lines', saved, 'units', units);
END;
$function$;

DO $grants$
DECLARE
    signature text;
BEGIN
    FOREACH signature IN ARRAY ARRAY[
        'public.simbar_item_by_barcode(text)',
        'public.simbar_item_link_barcode(bigint, text)',
        'public.simbar_stock_in_batch(date, text, jsonb)'
    ] LOOP
        EXECUTE 'REVOKE ALL ON FUNCTION ' || signature || ' FROM PUBLIC, anon, authenticated, service_role';
        EXECUTE 'GRANT EXECUTE ON FUNCTION ' || signature || ' TO authenticated';
    END LOOP;
END;
$grants$;

COMMIT;
