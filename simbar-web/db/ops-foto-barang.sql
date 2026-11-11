-- Foto barang habis pakai: every barang has a photo (items.photo, an https URL) and where it came from (items.photo_source),
-- so temporary pictures (Google/toko online, Wikimedia Commons, "gambar sementara") can be replaced step by step with real photos.
-- Product photos are not sensitive, so they are public Cloudinary images (fast thumbnails from the CDN). Safe to re-run.

BEGIN;

ALTER TABLE simbar.items ADD COLUMN IF NOT EXISTS photo_source text;

-- Who may change barang photos: the same people who may change barang (Atur Menu: "Boleh ubah barang").
CREATE OR REPLACE FUNCTION public.simbar_can_edit_items()
RETURNS boolean
LANGUAGE plpgsql STABLE SECURITY DEFINER SET search_path = ''
AS $function$
DECLARE
    actor simbar.users%ROWTYPE := simbar.require_profile(ARRAY['admin', 'admin_gudang', 'staff']);
BEGIN
    RETURN simbar.has_menu(actor.role, ARRAY['catalog']);
END;
$function$;

-- Set or clear one barang's photo. source_label says where it came from; an upload from the app is "Foto asli".
CREATE OR REPLACE FUNCTION public.simbar_item_set_photo(item_ref bigint, photo_url text, source_label text DEFAULT 'Foto asli')
RETURNS void
LANGUAGE plpgsql SECURITY DEFINER SET search_path = ''
AS $function$
DECLARE
    actor simbar.users%ROWTYPE := simbar.require_menu(ARRAY['catalog']);
    item simbar.items%ROWTYPE;
BEGIN
    IF photo_url IS NOT NULL AND (photo_url !~ '^https://[^\s]+$' OR char_length(photo_url) > 2000) THEN
        RAISE invalid_parameter_value USING MESSAGE = 'Link foto harus diawali https:// dan tanpa spasi.';
    END IF;
    IF char_length(coalesce(source_label, '')) > 300 THEN
        RAISE invalid_parameter_value USING MESSAGE = 'Keterangan sumber foto terlalu panjang.';
    END IF;
    SELECT * INTO item FROM simbar.items WHERE id = item_ref FOR UPDATE;
    IF NOT FOUND THEN
        RAISE no_data_found USING MESSAGE = 'Barang tidak ditemukan.';
    END IF;
    UPDATE simbar.items SET photo = photo_url,
        photo_source = CASE WHEN photo_url IS NULL THEN NULL ELSE coalesce(nullif(btrim(source_label), ''), 'Foto asli') END,
        updated_at = simbar.local_now()
    WHERE id = item_ref;
    PERFORM simbar.write_log(actor.id, 'item_photo_updated', actor.name || CASE WHEN photo_url IS NULL THEN ' menghapus foto ' ELSE ' mengganti foto ' END
        || item.name || ' (' || item.code || ')' || CASE WHEN photo_url IS NULL THEN '' ELSE ': ' || coalesce(nullif(btrim(source_label), ''), 'Foto asli') END);
END;
$function$;

REVOKE ALL ON FUNCTION public.simbar_can_edit_items() FROM PUBLIC, anon, authenticated, service_role;
REVOKE ALL ON FUNCTION public.simbar_item_set_photo(bigint, text, text) FROM PUBLIC, anon, authenticated, service_role;
GRANT EXECUTE ON FUNCTION public.simbar_can_edit_items() TO authenticated;
GRANT EXECUTE ON FUNCTION public.simbar_item_set_photo(bigint, text, text) TO authenticated;

COMMIT;
