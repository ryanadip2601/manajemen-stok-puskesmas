-- Shared helpers for the operational SIMBAR functions. Safe to re-run.
-- Roles follow Laravel: admin (everything), admin_gudang (warehouse), staff (requests only).

BEGIN;

CREATE OR REPLACE FUNCTION simbar.require_profile(allowed_roles text[])
RETURNS simbar.users
LANGUAGE plpgsql STABLE SECURITY DEFINER SET search_path = ''
AS $function$
DECLARE
    profile simbar.users%ROWTYPE;
BEGIN
    SELECT * INTO profile
    FROM simbar.users
    WHERE auth_user_id = auth.uid() AND is_approved;

    IF NOT FOUND THEN
        RAISE insufficient_privilege USING MESSAGE = 'SIMBAR profile is not approved or linked';
    END IF;
    IF NOT profile.role = ANY (allowed_roles) THEN
        RAISE insufficient_privilege USING MESSAGE = 'Akses ditolak untuk peran ini.';
    END IF;
    RETURN profile;
END;
$function$;

-- Wall-clock time in the Laravel app time zone (Asia/Jakarta), matching the imported timestamps.
CREATE OR REPLACE FUNCTION simbar.local_now()
RETURNS timestamp without time zone
LANGUAGE sql STABLE SET search_path = ''
AS $function$ SELECT (now() AT TIME ZONE 'Asia/Jakarta'); $function$;

CREATE OR REPLACE FUNCTION simbar.local_today()
RETURNS date
LANGUAGE sql STABLE SET search_path = ''
AS $function$ SELECT simbar.local_now()::date; $function$;

CREATE OR REPLACE FUNCTION simbar.write_log(actor bigint, log_action text, log_description text)
RETURNS void
LANGUAGE sql SECURITY DEFINER SET search_path = ''
AS $function$
    INSERT INTO simbar.logs (user_id, action, description, created_at)
    VALUES (actor, log_action, log_description, simbar.local_now());
$function$;

-- Approved restok requests become "tersedia" once the barang has stock again (checkAndUpdateAvailability).
CREATE OR REPLACE FUNCTION simbar.mark_restock_available(item_ref bigint)
RETURNS integer
LANGUAGE plpgsql SECURITY DEFINER SET search_path = ''
AS $function$
DECLARE
    changed integer;
BEGIN
    IF NOT EXISTS (SELECT 1 FROM simbar.items WHERE id = item_ref AND stock > 0) THEN
        RETURN 0;
    END IF;
    UPDATE simbar.permintaan_barang SET status = 'tersedia', notified_at = NULL, updated_at = simbar.local_now()
    WHERE item_id = item_ref AND tipe = 'restok' AND status = 'disetujui';
    GET DIAGNOSTICS changed = ROW_COUNT;
    RETURN changed;
END;
$function$;

REVOKE ALL ON FUNCTION simbar.mark_restock_available(bigint) FROM PUBLIC, anon, authenticated, service_role;
REVOKE ALL ON FUNCTION simbar.require_profile(text[]) FROM PUBLIC, anon, authenticated, service_role;
REVOKE ALL ON FUNCTION simbar.write_log(bigint, text, text) FROM PUBLIC, anon, authenticated, service_role;
REVOKE ALL ON FUNCTION simbar.local_now() FROM PUBLIC, anon, authenticated, service_role;
REVOKE ALL ON FUNCTION simbar.local_today() FROM PUBLIC, anon, authenticated, service_role;

COMMIT;
