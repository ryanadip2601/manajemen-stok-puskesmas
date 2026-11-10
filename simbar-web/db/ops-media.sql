-- Photos uploaded from the cloud app. Requires media-objects.sql and ops-core.sql. Safe to re-run.
-- /api/media uploads the file to Cloudinary (type authenticated) and registers it here as "media:<hex>";
-- records then store that reference in foto / lampiran_foto / foto_barang just like the legacy paths.

BEGIN;

ALTER TABLE simbar.media_objects ADD COLUMN IF NOT EXISTS uploaded_by bigint REFERENCES simbar.users(id) ON DELETE SET NULL;
ALTER TABLE simbar.media_objects ADD COLUMN IF NOT EXISTS uploaded_at timestamp without time zone;

CREATE OR REPLACE FUNCTION public.simbar_whoami()
RETURNS jsonb
LANGUAGE plpgsql STABLE SECURITY DEFINER SET search_path = ''
AS $function$
DECLARE
    actor simbar.users%ROWTYPE := simbar.require_profile(ARRAY['admin', 'admin_gudang', 'staff']);
BEGIN
    RETURN jsonb_build_object('id', actor.id, 'name', actor.name, 'role', actor.role);
END;
$function$;

CREATE OR REPLACE FUNCTION public.simbar_media_register(
    public_id text, asset_id text, asset_version bigint, file_size integer, file_sha256 text
) RETURNS text
LANGUAGE plpgsql SECURITY DEFINER SET search_path = ''
AS $function$
DECLARE
    actor simbar.users%ROWTYPE := simbar.require_profile(ARRAY['admin', 'admin_gudang', 'staff']);
    reference text := 'media:' || substr(public_id, char_length('simbar-uploads/') + 1);
BEGIN
    IF public_id !~ '^simbar-uploads/[0-9a-f]{64}$' OR asset_id !~ '^[0-9a-f]{32}$' OR asset_version IS NULL OR asset_version < 1
       OR file_size IS NULL OR file_size NOT BETWEEN 1 AND 4194304 OR file_sha256 !~ '^[0-9a-f]{64}$' THEN
        RAISE invalid_parameter_value USING MESSAGE = 'Data foto tidak valid.';
    END IF;
    INSERT INTO simbar.media_objects (legacy_path, legacy_reference, cloudinary_asset_id, cloudinary_public_id,
        cloudinary_version, format, file_size, sha256, uploaded_by, uploaded_at)
    VALUES ('cloudinary/' || public_id, reference, asset_id, public_id, asset_version, 'jpg', file_size, file_sha256,
        actor.id, simbar.local_now());
    RETURN reference;
END;
$function$;

-- A photo reference is usable in a record only if it is a legacy path already stored, or an upload of the caller.
CREATE OR REPLACE FUNCTION simbar.check_media_reference(actor_id bigint, reference text)
RETURNS void
LANGUAGE plpgsql STABLE SECURITY DEFINER SET search_path = ''
AS $function$
BEGIN
    IF reference IS NOT NULL AND reference LIKE 'media:%' AND NOT EXISTS (
        SELECT 1 FROM simbar.media_objects WHERE legacy_reference = reference AND uploaded_by = actor_id) THEN
        RAISE check_violation USING MESSAGE = 'Foto tidak ditemukan. Unggah ulang fotonya.';
    END IF;
END;
$function$;

-- Returns the Cloudinary object for a reference the caller may see, or NULL.
CREATE OR REPLACE FUNCTION public.simbar_media_lookup(reference text)
RETURNS jsonb
LANGUAGE plpgsql STABLE SECURITY DEFINER SET search_path = ''
AS $function$
DECLARE
    actor simbar.users%ROWTYPE := simbar.require_profile(ARRAY['admin', 'admin_gudang', 'staff']);
    warehouse boolean := actor.role IN ('admin', 'admin_gudang');
BEGIN
    IF reference IS NULL OR char_length(reference) > 300 THEN
        RETURN NULL;
    END IF;
    IF NOT (
        EXISTS (SELECT 1 FROM simbar.barang_aset WHERE foto = reference)
        OR EXISTS (SELECT 1 FROM simbar.pemeliharaan_aset WHERE lampiran_foto = reference AND (warehouse OR created_by = actor.id))
        OR EXISTS (SELECT 1 FROM simbar.permintaan_barang WHERE foto_barang = reference AND (warehouse OR user_id = actor.id))
        OR EXISTS (SELECT 1 FROM simbar.usulan_penerimaan_barang WHERE foto_barang ~ '^\s*\[' AND foto_barang::jsonb ? reference
                   AND (warehouse OR user_id = actor.id))
        OR EXISTS (SELECT 1 FROM simbar.media_objects WHERE legacy_reference = reference AND uploaded_by = actor.id)
    ) THEN
        RETURN NULL;
    END IF;
    RETURN (SELECT jsonb_build_object('publicId', cloudinary_public_id, 'format', format, 'size', file_size)
        FROM simbar.media_objects WHERE legacy_reference = reference);
END;
$function$;

REVOKE ALL ON FUNCTION simbar.check_media_reference(bigint, text) FROM PUBLIC, anon, authenticated, service_role;

DO $grants$
DECLARE
    signature text;
BEGIN
    FOREACH signature IN ARRAY ARRAY[
        'public.simbar_whoami()',
        'public.simbar_media_register(text, text, bigint, integer, text)',
        'public.simbar_media_lookup(text)'
    ] LOOP
        EXECUTE 'REVOKE ALL ON FUNCTION ' || signature || ' FROM PUBLIC, anon, authenticated, service_role';
        EXECUTE 'GRANT EXECUTE ON FUNCTION ' || signature || ' TO authenticated';
    END LOOP;
END;
$grants$;

COMMIT;
