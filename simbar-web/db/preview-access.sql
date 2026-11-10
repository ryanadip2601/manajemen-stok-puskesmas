BEGIN;

CREATE OR REPLACE FUNCTION public.simbar_preview(
    attachment_kind text DEFAULT NULL,
    attachment_id bigint DEFAULT NULL
) RETURNS jsonb
LANGUAGE plpgsql STABLE SECURITY DEFINER SET search_path = ''
AS $function$
DECLARE
    profile simbar.users%ROWTYPE;
BEGIN
    SELECT * INTO profile
    FROM simbar.users
    WHERE auth_user_id = auth.uid()
      AND is_approved
      AND role IN ('admin', 'admin_gudang', 'staff');

    IF NOT FOUND THEN
        RAISE insufficient_privilege USING MESSAGE = 'SIMBAR profile is not approved or linked';
    END IF;

    IF attachment_kind IS NULL AND attachment_id IS NULL THEN
        RETURN jsonb_build_object(
            'profile', jsonb_build_object('name', profile.name, 'role', profile.role),
            'stats', jsonb_build_object(
                'items', (SELECT count(*) FROM simbar.items),
                'stock', (SELECT coalesce(sum(stock), 0) FROM simbar.items),
                'assets', (SELECT count(*) FROM simbar.barang_aset),
                'photos', (SELECT count(legacy_reference) FROM simbar.media_objects)
            ),
            'photos', (
                SELECT coalesce(jsonb_agg(photo ORDER BY kind, id), '[]'::jsonb)
                FROM (
                    SELECT 'asset' AS kind, b.id, b.nama_aset AS title, b.kode_aset AS detail
                    FROM simbar.barang_aset b
                    JOIN simbar.media_objects m ON m.legacy_reference = b.foto
                    UNION ALL
                    SELECT 'maintenance', p.id, b.nama_aset, p.tanggal_pemeliharaan::text
                    FROM simbar.pemeliharaan_aset p
                    JOIN simbar.barang_aset b ON b.id = p.barang_aset_id
                    JOIN simbar.media_objects m ON m.legacy_reference = p.lampiran_foto
                ) AS photo
            )
        );
    END IF;

    RETURN (
        SELECT jsonb_build_object(
            'publicId', m.cloudinary_public_id,
            'format', m.format,
            'size', m.file_size
        )
        FROM simbar.media_objects m
        WHERE m.legacy_reference = CASE attachment_kind
            WHEN 'asset' THEN (SELECT foto FROM simbar.barang_aset WHERE id = attachment_id)
            WHEN 'maintenance' THEN (SELECT lampiran_foto FROM simbar.pemeliharaan_aset WHERE id = attachment_id)
        END
    );
END;
$function$;

REVOKE ALL ON FUNCTION public.simbar_preview(text, bigint) FROM PUBLIC, anon, authenticated, service_role;
GRANT EXECUTE ON FUNCTION public.simbar_preview(text, bigint) TO authenticated;

COMMIT;
