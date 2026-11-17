-- Pengaturan cetak (kop dan tanda tangan laporan), shared by every device instead of kept per browser.
-- Everyone signed in reads it; admin and admin_gudang change it. Requires ops-core.sql. Safe to re-run.

BEGIN;

CREATE TABLE IF NOT EXISTS simbar.app_settings (
    key text PRIMARY KEY,
    value jsonb NOT NULL,
    updated_by bigint REFERENCES simbar.users(id) ON DELETE SET NULL,
    updated_at timestamp without time zone NOT NULL
);
ALTER TABLE simbar.app_settings ENABLE ROW LEVEL SECURITY;
REVOKE ALL ON simbar.app_settings FROM PUBLIC, anon, authenticated, service_role;

CREATE OR REPLACE FUNCTION public.simbar_cetak_settings()
RETURNS jsonb
LANGUAGE plpgsql STABLE SECURITY DEFINER SET search_path = ''
AS $function$
BEGIN
    PERFORM simbar.require_profile(ARRAY['admin', 'admin_gudang', 'staff']);
    RETURN (SELECT value FROM simbar.app_settings WHERE key = 'cetak');
END;
$function$;

CREATE OR REPLACE FUNCTION public.simbar_cetak_settings_save(settings jsonb)
RETURNS void
LANGUAGE plpgsql SECURITY DEFINER SET search_path = ''
AS $function$
DECLARE
    actor simbar.users%ROWTYPE := simbar.require_profile(ARRAY['admin', 'admin_gudang']);
    allowed text[] := ARRAY['kop', 'logo', 'kota', 'kiriJabatan', 'kiriNama', 'kiriNip', 'kananJabatan', 'kananNama', 'kananNip'];
    entry record;
BEGIN
    IF settings IS NULL OR jsonb_typeof(settings) <> 'object' THEN
        RAISE invalid_parameter_value USING MESSAGE = 'Pengaturan cetak tidak valid.';
    END IF;
    FOR entry IN SELECT * FROM jsonb_each(settings) LOOP
        IF NOT entry.key = ANY (allowed) OR jsonb_typeof(entry.value) <> 'string' OR char_length(entry.value #>> '{}') > 800 THEN
            RAISE invalid_parameter_value USING MESSAGE = 'Pengaturan cetak tidak valid atau terlalu panjang.';
        END IF;
    END LOOP;
    INSERT INTO simbar.app_settings (key, value, updated_by, updated_at) VALUES ('cetak', settings, actor.id, simbar.local_now())
    ON CONFLICT (key) DO UPDATE SET value = EXCLUDED.value, updated_by = EXCLUDED.updated_by, updated_at = EXCLUDED.updated_at;
    PERFORM simbar.write_log(actor.id, 'cetak_settings_updated', 'Pengaturan kop dan tanda tangan laporan diubah');
END;
$function$;

REVOKE ALL ON FUNCTION public.simbar_cetak_settings() FROM PUBLIC, anon, authenticated, service_role;
REVOKE ALL ON FUNCTION public.simbar_cetak_settings_save(jsonb) FROM PUBLIC, anon, authenticated, service_role;
GRANT EXECUTE ON FUNCTION public.simbar_cetak_settings() TO authenticated;
GRANT EXECUTE ON FUNCTION public.simbar_cetak_settings_save(jsonb) TO authenticated;

COMMIT;
