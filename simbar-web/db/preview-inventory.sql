BEGIN;

CREATE OR REPLACE FUNCTION public.simbar_preview_inventory(
    list_kind text,
    search_text text DEFAULT '',
    page_number integer DEFAULT 1
) RETURNS jsonb
LANGUAGE plpgsql STABLE SECURITY DEFINER SET search_path = ''
AS $function$
DECLARE
    search_pattern text;
    result jsonb;
BEGIN
    IF NOT EXISTS (
        SELECT 1 FROM simbar.users
        WHERE auth_user_id = auth.uid()
          AND is_approved
          AND role IN ('admin', 'admin_gudang', 'staff')
    ) THEN
        RAISE insufficient_privilege USING MESSAGE = 'SIMBAR profile is not approved or linked';
    END IF;

    IF list_kind IS NULL OR list_kind NOT IN ('items', 'assets') OR
       search_text IS NULL OR char_length(search_text) > 100 OR
       page_number IS NULL OR page_number NOT BETWEEN 1 AND 10000 THEN
        RAISE invalid_parameter_value USING MESSAGE = 'Invalid SIMBAR inventory filter';
    END IF;

    search_pattern := '%' || replace(replace(replace(btrim(search_text), '!', '!!'), '%', '!%'), '_', '!_') || '%';

    WITH matched AS MATERIALIZED (
        SELECT i.id, i.code, i.name, c.name AS category, i.stock AS quantity,
               u.symbol AS unit, i.minimum_stock AS minimum, null::text AS room,
               null::text AS condition, null::text AS status, i.photo, i.photo_source
        FROM simbar.items i
        JOIN simbar.categories c ON c.id = i.category_id
        JOIN simbar.units u ON u.id = i.unit_id
        WHERE list_kind = 'items' AND (
            i.name ILIKE search_pattern ESCAPE '!' OR i.code ILIKE search_pattern ESCAPE '!'
            OR c.name ILIKE search_pattern ESCAPE '!'
        )
        UNION ALL
        SELECT a.id, a.kode_aset, a.nama_aset, k.nama_kategori, a.jumlah,
               null, null, r.nama_ruangan, a.kondisi, a.status_aset, null, null
        FROM simbar.barang_aset a
        JOIN simbar.kategori_aset k ON k.id = a.kategori_aset_id
        JOIN simbar.ruangan r ON r.id = a.ruangan_id
        WHERE list_kind = 'assets' AND (
            a.nama_aset ILIKE search_pattern ESCAPE '!' OR a.kode_aset ILIKE search_pattern ESCAPE '!'
            OR k.nama_kategori ILIKE search_pattern ESCAPE '!' OR r.nama_ruangan ILIKE search_pattern ESCAPE '!'
        )
    )
    SELECT jsonb_build_object(
        'total', (SELECT count(*) FROM matched),
        'rows', coalesce(
            (SELECT jsonb_agg(row_data ORDER BY lower(row_data.name), row_data.id)
             FROM (
                 SELECT * FROM matched
                 ORDER BY lower(name), id
                 LIMIT 15 OFFSET (page_number - 1) * 15
             ) AS row_data),
            '[]'::jsonb
        )
    ) INTO result;

    RETURN result;
END;
$function$;

REVOKE ALL ON FUNCTION public.simbar_preview_inventory(text, text, integer) FROM PUBLIC, anon, authenticated, service_role;
GRANT EXECUTE ON FUNCTION public.simbar_preview_inventory(text, text, integer) TO authenticated;

COMMIT;
