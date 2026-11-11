-- Barang, jenis barang, barang masuk, and barang keluar for admin and admin_gudang. Safe to re-run.
-- Ports ItemController, CategoryController, StockInController, StockOutController and the
-- StockIn/StockOut model events: every movement changes items.stock in the same transaction.

BEGIN;

CREATE OR REPLACE FUNCTION public.simbar_stock_options()
RETURNS jsonb
LANGUAGE plpgsql STABLE SECURITY DEFINER SET search_path = ''
AS $function$
BEGIN
    PERFORM simbar.require_menu(ARRAY['catalog', 'in', 'out', 'verifikasi', 'laporan']);
    RETURN jsonb_build_object(
        'categories', (SELECT coalesce(jsonb_agg(jsonb_build_object('id', id, 'name', name,
                'description', description, 'items', (SELECT count(*) FROM simbar.items i WHERE i.category_id = c.id))
            ORDER BY lower(name)), '[]') FROM simbar.categories c),
        'units', (SELECT coalesce(jsonb_agg(jsonb_build_object('id', id, 'name', name, 'symbol', symbol)
            ORDER BY lower(name)), '[]') FROM simbar.units),
        'items', (SELECT coalesce(jsonb_agg(jsonb_build_object('id', i.id, 'code', i.code, 'name', i.name,
                'stock', i.stock, 'unit', u.symbol) ORDER BY lower(i.name), i.id), '[]')
            FROM simbar.items i JOIN simbar.units u ON u.id = i.unit_id)
    );
END;
$function$;

CREATE OR REPLACE FUNCTION public.simbar_item_get(item_id bigint)
RETURNS jsonb
LANGUAGE plpgsql STABLE SECURITY DEFINER SET search_path = ''
AS $function$
BEGIN
    PERFORM simbar.require_menu(ARRAY['catalog']);
    RETURN (SELECT jsonb_build_object('id', id, 'code', code, 'name', name, 'categoryId', category_id,
            'unitId', unit_id, 'description', description, 'perolehan', perolehan, 'price', price,
            'stock', stock, 'minimumStock', minimum_stock, 'barcode', barcode)
        FROM simbar.items WHERE id = item_id);
END;
$function$;

-- item_id NULL creates a barang; initial_stock only applies on create, like Laravel.
CREATE OR REPLACE FUNCTION public.simbar_item_save(
    item_id bigint, item_code text, item_name text, item_category bigint, item_unit bigint,
    item_perolehan text, item_price numeric, item_minimum integer,
    item_description text DEFAULT NULL, item_barcode text DEFAULT NULL, initial_stock integer DEFAULT 0
) RETURNS bigint
LANGUAGE plpgsql SECURITY DEFINER SET search_path = ''
AS $function$
DECLARE
    actor simbar.users%ROWTYPE := simbar.require_menu(CASE WHEN current_setting('simbar.via', true) = 'verifikasi'
        THEN ARRAY['catalog', 'verifikasi'] ELSE ARRAY['catalog'] END);
    saved bigint;
BEGIN
    item_code := btrim(item_code);
    item_name := btrim(item_name);
    IF coalesce(item_code, '') = '' OR char_length(item_code) > 50 THEN
        RAISE check_violation USING MESSAGE = 'Kode barang harus diisi (maksimal 50 karakter).';
    ELSIF coalesce(item_name, '') = '' OR char_length(item_name) > 255 THEN
        RAISE check_violation USING MESSAGE = 'Nama barang harus diisi.';
    ELSIF coalesce(btrim(item_perolehan), '') = '' THEN
        RAISE check_violation USING MESSAGE = 'Perolehan harus dipilih.';
    ELSIF item_price IS NULL OR item_price < 0 THEN
        RAISE check_violation USING MESSAGE = 'Harga satuan harus diisi.';
    ELSIF item_minimum IS NULL OR item_minimum < 0 OR coalesce(initial_stock, 0) < 0 THEN
        RAISE check_violation USING MESSAGE = 'Stok tidak boleh negatif.';
    ELSIF NOT EXISTS (SELECT 1 FROM simbar.categories WHERE id = item_category) THEN
        RAISE check_violation USING MESSAGE = 'Jenis barang harus dipilih.';
    ELSIF NOT EXISTS (SELECT 1 FROM simbar.units WHERE id = item_unit) THEN
        RAISE check_violation USING MESSAGE = 'Satuan harus dipilih.';
    ELSIF EXISTS (SELECT 1 FROM simbar.items WHERE code = item_code AND id IS DISTINCT FROM item_id) THEN
        RAISE unique_violation USING MESSAGE = 'Kode barang sudah ada.';
    END IF;

    IF item_id IS NULL THEN
        INSERT INTO simbar.items (code, name, category_id, unit_id, perolehan, price, minimum_stock,
            description, barcode, stock, created_at, updated_at)
        VALUES (item_code, item_name, item_category, item_unit, btrim(item_perolehan), item_price, item_minimum,
            nullif(btrim(item_description), ''), nullif(btrim(item_barcode), ''), coalesce(initial_stock, 0),
            simbar.local_now(), simbar.local_now())
        RETURNING id INTO saved;
        PERFORM simbar.write_log(actor.id, 'item_created', 'Barang "' || item_name || '" dibuat');
    ELSE
        UPDATE simbar.items SET code = item_code, name = item_name, category_id = item_category,
            unit_id = item_unit, perolehan = btrim(item_perolehan), price = item_price,
            minimum_stock = item_minimum, description = nullif(btrim(item_description), ''),
            barcode = nullif(btrim(item_barcode), ''), updated_at = simbar.local_now()
        WHERE id = item_id
        RETURNING id INTO saved;
        IF saved IS NULL THEN
            RAISE no_data_found USING MESSAGE = 'Barang tidak ditemukan.';
        END IF;
        PERFORM simbar.write_log(actor.id, 'item_updated', 'Barang "' || item_name || '" diupdate');
    END IF;
    RETURN saved;
END;
$function$;

CREATE OR REPLACE FUNCTION public.simbar_item_delete(item_id bigint)
RETURNS void
LANGUAGE plpgsql SECURITY DEFINER SET search_path = ''
AS $function$
DECLARE
    actor simbar.users%ROWTYPE := simbar.require_menu(ARRAY['catalog']);
    removed text;
BEGIN
    -- Laravel cascades movements away with the barang; refuse instead so history is never lost silently.
    IF EXISTS (SELECT 1 FROM simbar.stock_in WHERE stock_in.item_id = simbar_item_delete.item_id)
       OR EXISTS (SELECT 1 FROM simbar.stock_out WHERE stock_out.item_id = simbar_item_delete.item_id) THEN
        RAISE foreign_key_violation USING MESSAGE = 'Barang masih punya riwayat barang masuk/keluar.';
    END IF;
    DELETE FROM simbar.items WHERE id = item_id RETURNING name INTO removed;
    IF removed IS NULL THEN
        RAISE no_data_found USING MESSAGE = 'Barang tidak ditemukan.';
    END IF;
    PERFORM simbar.write_log(actor.id, 'item_deleted', 'Barang "' || removed || '" dihapus');
END;
$function$;

CREATE OR REPLACE FUNCTION public.simbar_category_save(category_id bigint, category_name text, category_description text DEFAULT NULL)
RETURNS bigint
LANGUAGE plpgsql SECURITY DEFINER SET search_path = ''
AS $function$
DECLARE
    actor simbar.users%ROWTYPE := simbar.require_menu(ARRAY['catalog']);
    saved bigint;
BEGIN
    category_name := btrim(category_name);
    IF coalesce(category_name, '') = '' OR char_length(category_name) > 255 THEN
        RAISE check_violation USING MESSAGE = 'Nama jenis barang harus diisi.';
    ELSIF EXISTS (SELECT 1 FROM simbar.categories WHERE lower(name) = lower(category_name) AND id IS DISTINCT FROM category_id) THEN
        RAISE unique_violation USING MESSAGE = 'Jenis barang sudah ada.';
    END IF;
    IF category_id IS NULL THEN
        INSERT INTO simbar.categories (name, description, created_at, updated_at)
        VALUES (category_name, nullif(btrim(category_description), ''), simbar.local_now(), simbar.local_now())
        RETURNING id INTO saved;
    ELSE
        UPDATE simbar.categories SET name = category_name, description = nullif(btrim(category_description), ''),
            updated_at = simbar.local_now()
        WHERE id = category_id RETURNING id INTO saved;
        IF saved IS NULL THEN
            RAISE no_data_found USING MESSAGE = 'Jenis barang tidak ditemukan.';
        END IF;
    END IF;
    PERFORM simbar.write_log(actor.id, CASE WHEN category_id IS NULL THEN 'category_created' ELSE 'category_updated' END,
        'Jenis barang "' || category_name || '" disimpan');
    RETURN saved;
END;
$function$;

CREATE OR REPLACE FUNCTION public.simbar_category_delete(category_id bigint)
RETURNS void
LANGUAGE plpgsql SECURITY DEFINER SET search_path = ''
AS $function$
DECLARE
    actor simbar.users%ROWTYPE := simbar.require_menu(ARRAY['catalog']);
    removed text;
BEGIN
    IF EXISTS (SELECT 1 FROM simbar.items WHERE items.category_id = simbar_category_delete.category_id) THEN
        RAISE foreign_key_violation USING MESSAGE = 'Jenis barang masih dipakai oleh barang.';
    END IF;
    DELETE FROM simbar.categories WHERE id = category_id RETURNING name INTO removed;
    IF removed IS NULL THEN
        RAISE no_data_found USING MESSAGE = 'Jenis barang tidak ditemukan.';
    END IF;
    PERFORM simbar.write_log(actor.id, 'category_deleted', 'Jenis barang "' || removed || '" dihapus');
END;
$function$;

CREATE OR REPLACE FUNCTION public.simbar_stock_list(
    movement_kind text, item_filter bigint DEFAULT NULL, date_from date DEFAULT NULL,
    date_to date DEFAULT NULL, page_number integer DEFAULT 1
) RETURNS jsonb
LANGUAGE plpgsql STABLE SECURITY DEFINER SET search_path = ''
AS $function$
DECLARE
    result jsonb;
BEGIN
    PERFORM simbar.require_menu(CASE movement_kind WHEN 'in' THEN ARRAY['in'] ELSE ARRAY['out'] END);
    IF movement_kind IS NULL OR movement_kind NOT IN ('in', 'out') OR
       page_number IS NULL OR page_number NOT BETWEEN 1 AND 10000 THEN
        RAISE invalid_parameter_value USING MESSAGE = 'Filter barang masuk/keluar tidak valid.';
    END IF;

    WITH movements AS MATERIALIZED (
        SELECT id, item_id, user_id, quantity, date, notes, created_at FROM simbar.stock_in WHERE movement_kind = 'in'
        UNION ALL
        SELECT id, item_id, user_id, quantity, date, notes, created_at FROM simbar.stock_out WHERE movement_kind = 'out'
    ), matched AS MATERIALIZED (
        SELECT m.id, m.date, m.quantity, m.notes, i.id AS item_id, i.code, i.name, u.symbol AS unit,
               p.name AS recorded_by, m.created_at
        FROM movements m
        JOIN simbar.items i ON i.id = m.item_id
        JOIN simbar.units u ON u.id = i.unit_id
        LEFT JOIN simbar.users p ON p.id = m.user_id
        WHERE (item_filter IS NULL OR m.item_id = item_filter)
          AND (date_from IS NULL OR m.date >= date_from)
          AND (date_to IS NULL OR m.date <= date_to)
    )
    SELECT jsonb_build_object(
        'total', (SELECT count(*) FROM matched),
        'quantity', (SELECT coalesce(sum(quantity), 0) FROM matched),
        'rows', coalesce((SELECT jsonb_agg(to_jsonb(r) - 'created_at' ORDER BY r.date DESC, r.created_at DESC NULLS LAST, r.id DESC)
            FROM (SELECT * FROM matched ORDER BY date DESC, created_at DESC NULLS LAST, id DESC
                  LIMIT 15 OFFSET (page_number - 1) * 15) r), '[]')
    ) INTO result;
    RETURN result;
END;
$function$;

-- movement_id NULL records a new movement. The barang of an existing movement cannot change.
CREATE OR REPLACE FUNCTION public.simbar_stock_save(
    movement_kind text, movement_id bigint, item_ref bigint, movement_quantity integer,
    movement_date date, movement_notes text DEFAULT NULL
) RETURNS bigint
LANGUAGE plpgsql SECURITY DEFINER SET search_path = ''
AS $function$
DECLARE
    actor simbar.users%ROWTYPE := simbar.require_menu(CASE WHEN movement_kind = 'in' THEN ARRAY['in']
        WHEN current_setting('simbar.via', true) = 'checklist' THEN ARRAY['out', 'checklist'] ELSE ARRAY['out'] END);
    item simbar.items%ROWTYPE;
    unit_symbol text;
    previous integer := 0;
    delta integer;
    saved bigint;
    label text := CASE movement_kind WHEN 'in' THEN 'Barang masuk' ELSE 'Barang keluar' END;
BEGIN
    IF movement_kind IS NULL OR movement_kind NOT IN ('in', 'out') THEN
        RAISE invalid_parameter_value USING MESSAGE = 'Jenis transaksi tidak valid.';
    ELSIF movement_quantity IS NULL OR movement_quantity < 1 THEN
        RAISE check_violation USING MESSAGE = 'Jumlah minimal 1.';
    ELSIF movement_date IS NULL THEN
        RAISE check_violation USING MESSAGE = 'Tanggal harus diisi.';
    END IF;

    IF movement_id IS NOT NULL THEN
        IF movement_kind = 'in' THEN
            SELECT s.item_id, s.quantity INTO item_ref, previous FROM simbar.stock_in s WHERE s.id = movement_id FOR UPDATE;
        ELSE
            SELECT s.item_id, s.quantity INTO item_ref, previous FROM simbar.stock_out s WHERE s.id = movement_id FOR UPDATE;
        END IF;
        IF NOT FOUND THEN
            RAISE no_data_found USING MESSAGE = label || ' tidak ditemukan.';
        END IF;
    END IF;

    SELECT * INTO item FROM simbar.items WHERE id = item_ref FOR UPDATE;
    IF NOT FOUND THEN
        RAISE check_violation USING MESSAGE = 'Barang harus dipilih.';
    END IF;
    SELECT symbol INTO unit_symbol FROM simbar.units WHERE id = item.unit_id;

    delta := CASE movement_kind WHEN 'in' THEN movement_quantity - previous ELSE previous - movement_quantity END;
    IF item.stock + delta < 0 THEN
        RAISE check_violation USING MESSAGE = 'Stok tidak mencukupi. Stok tersedia: ' || item.stock || ' ' || unit_symbol;
    END IF;

    IF movement_kind = 'in' AND movement_id IS NULL THEN
        INSERT INTO simbar.stock_in (item_id, user_id, quantity, date, notes, created_at, updated_at)
        VALUES (item.id, actor.id, movement_quantity, movement_date, nullif(btrim(movement_notes), ''), simbar.local_now(), simbar.local_now())
        RETURNING id INTO saved;
    ELSIF movement_kind = 'out' AND movement_id IS NULL THEN
        INSERT INTO simbar.stock_out (item_id, user_id, quantity, date, notes, created_at, updated_at)
        VALUES (item.id, actor.id, movement_quantity, movement_date, nullif(btrim(movement_notes), ''), simbar.local_now(), simbar.local_now())
        RETURNING id INTO saved;
    ELSIF movement_kind = 'in' THEN
        UPDATE simbar.stock_in SET quantity = movement_quantity, date = movement_date,
            notes = nullif(btrim(movement_notes), ''), updated_at = simbar.local_now()
        WHERE id = movement_id RETURNING id INTO saved;
    ELSE
        UPDATE simbar.stock_out SET quantity = movement_quantity, date = movement_date,
            notes = nullif(btrim(movement_notes), ''), updated_at = simbar.local_now()
        WHERE id = movement_id RETURNING id INTO saved;
    END IF;

    IF delta <> 0 THEN
        UPDATE simbar.items SET stock = stock + delta, updated_at = simbar.local_now() WHERE id = item.id;
    END IF;
    IF movement_kind = 'in' THEN
        PERFORM simbar.mark_restock_available(item.id);
    END IF;
    IF movement_id IS NULL THEN
        PERFORM simbar.write_log(actor.id, 'stock_' || movement_kind || '_created',
            label || ': ' || item.name || ' sebanyak ' || movement_quantity || ' ' || unit_symbol);
    ELSIF previous <> movement_quantity THEN
        PERFORM simbar.write_log(actor.id, 'stock_' || movement_kind || '_updated',
            'Update ' || lower(label) || ': ' || item.name || ' dari ' || previous || ' menjadi ' || movement_quantity);
    END IF;
    RETURN saved;
END;
$function$;

CREATE OR REPLACE FUNCTION public.simbar_stock_delete(movement_kind text, movement_id bigint)
RETURNS void
LANGUAGE plpgsql SECURITY DEFINER SET search_path = ''
AS $function$
DECLARE
    actor simbar.users%ROWTYPE := simbar.require_menu(CASE movement_kind WHEN 'in' THEN ARRAY['in'] ELSE ARRAY['out'] END);
    item simbar.items%ROWTYPE;
    removed integer;
    item_ref bigint;
BEGIN
    IF movement_kind = 'in' THEN
        SELECT s.item_id, s.quantity INTO item_ref, removed FROM simbar.stock_in s WHERE s.id = movement_id FOR UPDATE;
    ELSIF movement_kind = 'out' THEN
        SELECT s.item_id, s.quantity INTO item_ref, removed FROM simbar.stock_out s WHERE s.id = movement_id FOR UPDATE;
    ELSE
        RAISE invalid_parameter_value USING MESSAGE = 'Jenis transaksi tidak valid.';
    END IF;
    IF removed IS NULL THEN
        RAISE no_data_found USING MESSAGE = 'Transaksi tidak ditemukan.';
    END IF;

    SELECT * INTO item FROM simbar.items WHERE id = item_ref FOR UPDATE;
    IF movement_kind = 'in' AND item.stock < removed THEN
        RAISE check_violation USING MESSAGE = 'Barang masuk ini sudah terpakai. Stok tersedia: ' || item.stock;
    END IF;

    IF movement_kind = 'in' THEN
        DELETE FROM simbar.stock_in WHERE id = movement_id;
        UPDATE simbar.items SET stock = stock - removed, updated_at = simbar.local_now() WHERE id = item.id;
    ELSE
        DELETE FROM simbar.stock_out WHERE id = movement_id;
        UPDATE simbar.items SET stock = stock + removed, updated_at = simbar.local_now() WHERE id = item.id;
    END IF;
    PERFORM simbar.write_log(actor.id, 'stock_' || movement_kind || '_deleted',
        'Hapus barang ' || CASE movement_kind WHEN 'in' THEN 'masuk' ELSE 'keluar' END || ': ' || item.name || ' sebanyak ' || removed);
END;
$function$;

DO $grants$
DECLARE
    signature text;
BEGIN
    FOREACH signature IN ARRAY ARRAY[
        'public.simbar_stock_options()',
        'public.simbar_item_get(bigint)',
        'public.simbar_item_save(bigint, text, text, bigint, bigint, text, numeric, integer, text, text, integer)',
        'public.simbar_item_delete(bigint)',
        'public.simbar_category_save(bigint, text, text)',
        'public.simbar_category_delete(bigint)',
        'public.simbar_stock_list(text, bigint, date, date, integer)',
        'public.simbar_stock_save(text, bigint, bigint, integer, date, text)',
        'public.simbar_stock_delete(text, bigint)'
    ] LOOP
        EXECUTE 'REVOKE ALL ON FUNCTION ' || signature || ' FROM PUBLIC, anon, authenticated, service_role';
        EXECUTE 'GRANT EXECUTE ON FUNCTION ' || signature || ' TO authenticated';
    END LOOP;
END;
$grants$;

COMMIT;
