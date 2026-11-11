-- Which menus the Admin Gudang and Staf roles get. The administrator changes this in "Atur Menu";
-- a menu also unlocks the database functions behind it (simbar.require_menu). Safe to re-run.
-- Administrator always has every menu; Pengguna and Atur Menu stay administrator-only.

BEGIN;

CREATE TABLE IF NOT EXISTS simbar.role_menus (
    role text NOT NULL CHECK (role IN ('admin_gudang', 'staff')),
    menu text NOT NULL,
    PRIMARY KEY (role, menu)
);
ALTER TABLE simbar.role_menus ENABLE ROW LEVEL SECURITY;
REVOKE ALL ON TABLE simbar.role_menus FROM PUBLIC, anon, authenticated;

-- Menus the administrator can hand out, in sidebar order.
CREATE OR REPLACE FUNCTION simbar.menu_keys()
RETURNS text[]
LANGUAGE sql IMMUTABLE SET search_path = ''
AS $function$ SELECT ARRAY['items', 'catalog', 'in', 'out', 'checklist', 'verifikasi', 'usulan-admin',
    'aset', 'pemeliharaan', 'penyusutan', 'pickup', 'permintaan', 'usulan', 'laporan']; $function$;

-- First run only: the menus each role had before menus became configurable.
INSERT INTO simbar.role_menus (role, menu)
SELECT d.r, m FROM (VALUES
    ('admin_gudang', ARRAY['items', 'catalog', 'in', 'out', 'checklist', 'verifikasi', 'usulan-admin',
        'aset', 'pemeliharaan', 'penyusutan', 'pickup', 'permintaan', 'usulan']),
    ('staff', ARRAY['items', 'aset', 'pemeliharaan', 'pickup', 'permintaan', 'usulan'])) AS d(r, menus),
    unnest(d.menus) AS m
WHERE NOT EXISTS (SELECT 1 FROM simbar.role_menus);

-- True when the role has at least one of the menus (the administrator has them all).
CREATE OR REPLACE FUNCTION simbar.has_menu(person_role text, wanted text[])
RETURNS boolean
LANGUAGE sql STABLE SECURITY DEFINER SET search_path = ''
AS $function$
    SELECT person_role = 'admin'
        OR EXISTS (SELECT 1 FROM simbar.role_menus rm WHERE rm.role = person_role AND rm.menu = ANY (wanted));
$function$;

-- Like require_profile, but the caller's role must have one of the menus.
CREATE OR REPLACE FUNCTION simbar.require_menu(wanted text[])
RETURNS simbar.users
LANGUAGE plpgsql STABLE SECURITY DEFINER SET search_path = ''
AS $function$
DECLARE
    profile simbar.users%ROWTYPE := simbar.require_profile(ARRAY['admin', 'admin_gudang', 'staff']);
BEGIN
    IF NOT simbar.has_menu(profile.role, wanted) THEN
        RAISE insufficient_privilege USING MESSAGE = 'Akses ditolak untuk peran ini.';
    END IF;
    RETURN profile;
END;
$function$;

-- Menus per role, for building the sidebar (every signed-in user).
CREATE OR REPLACE FUNCTION public.simbar_role_menus()
RETURNS jsonb
LANGUAGE plpgsql STABLE SECURITY DEFINER SET search_path = ''
AS $function$
DECLARE
    actor simbar.users%ROWTYPE := simbar.require_profile(ARRAY['admin', 'admin_gudang', 'staff']);
BEGIN
    RETURN jsonb_build_object(
        'admin_gudang', (SELECT coalesce(jsonb_agg(menu ORDER BY menu), '[]') FROM simbar.role_menus WHERE role = 'admin_gudang'),
        'staff', (SELECT coalesce(jsonb_agg(menu ORDER BY menu), '[]') FROM simbar.role_menus WHERE role = 'staff'));
END;
$function$;

-- Administrator only: replace the menus of one role.
CREATE OR REPLACE FUNCTION public.simbar_role_menus_save(target_role text, menus text[])
RETURNS void
LANGUAGE plpgsql SECURITY DEFINER SET search_path = ''
AS $function$
DECLARE
    actor simbar.users%ROWTYPE := simbar.require_profile(ARRAY['admin']);
    unknown text;
BEGIN
    IF target_role IS NULL OR target_role NOT IN ('admin_gudang', 'staff') THEN
        RAISE invalid_parameter_value USING MESSAGE = 'Peran tidak valid.';
    END IF;
    IF menus IS NULL THEN
        RAISE invalid_parameter_value USING MESSAGE = 'Daftar menu tidak valid.';
    END IF;
    SELECT m INTO unknown FROM unnest(menus) AS m WHERE m IS NULL OR NOT m = ANY (simbar.menu_keys()) LIMIT 1;
    IF FOUND THEN
        RAISE invalid_parameter_value USING MESSAGE = 'Menu tidak dikenal: ' || coalesce(unknown, '(kosong)');
    END IF;
    DELETE FROM simbar.role_menus WHERE role = target_role;
    INSERT INTO simbar.role_menus (role, menu) SELECT DISTINCT target_role, m FROM unnest(menus) AS m;
    PERFORM simbar.write_log(actor.id, 'role_menus_updated', actor.name || ' mengatur menu '
        || CASE target_role WHEN 'staff' THEN 'Staf' ELSE 'Admin Gudang' END || ': '
        || coalesce((SELECT string_agg(m, ', ' ORDER BY m) FROM unnest(menus) AS m), '(tidak ada)'));
END;
$function$;

REVOKE ALL ON FUNCTION simbar.menu_keys() FROM PUBLIC, anon, authenticated, service_role;
REVOKE ALL ON FUNCTION simbar.has_menu(text, text[]) FROM PUBLIC, anon, authenticated, service_role;
REVOKE ALL ON FUNCTION simbar.require_menu(text[]) FROM PUBLIC, anon, authenticated, service_role;
REVOKE ALL ON FUNCTION public.simbar_role_menus() FROM PUBLIC, anon, authenticated, service_role;
REVOKE ALL ON FUNCTION public.simbar_role_menus_save(text, text[]) FROM PUBLIC, anon, authenticated, service_role;
GRANT EXECUTE ON FUNCTION public.simbar_role_menus() TO authenticated;
GRANT EXECUTE ON FUNCTION public.simbar_role_menus_save(text, text[]) TO authenticated;

COMMIT;
