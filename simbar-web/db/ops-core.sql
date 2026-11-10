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

-- BarangAset::generateKodeAset: first three letters of the kategori, the year, and the next 4-digit number.
-- Callers hold the advisory lock until commit so concurrent inserts never share a code.
CREATE OR REPLACE FUNCTION simbar.next_kode_aset(kategori_ref bigint)
RETURNS text
LANGUAGE plpgsql SECURITY DEFINER SET search_path = ''
AS $function$
DECLARE
    prefix text := coalesce((SELECT upper(left(nama_kategori, 3)) FROM simbar.kategori_aset WHERE id = kategori_ref), 'AST')
        || '-' || to_char(simbar.local_today(), 'YYYY') || '-';
BEGIN
    PERFORM pg_advisory_xact_lock(hashtext('simbar.barang_aset.kode'));
    RETURN prefix || lpad((coalesce(max(right(kode_aset, 4)::integer), 0) + 1)::text, 4, '0')
        FROM simbar.barang_aset WHERE left(kode_aset, char_length(prefix)) = prefix AND right(kode_aset, 4) ~ '^[0-9]{4}$';
END;
$function$;

REVOKE ALL ON FUNCTION simbar.next_kode_aset(bigint) FROM PUBLIC, anon, authenticated, service_role;
REVOKE ALL ON FUNCTION simbar.mark_restock_available(bigint) FROM PUBLIC, anon, authenticated, service_role;
REVOKE ALL ON FUNCTION simbar.require_profile(text[]) FROM PUBLIC, anon, authenticated, service_role;
REVOKE ALL ON FUNCTION simbar.write_log(bigint, text, text) FROM PUBLIC, anon, authenticated, service_role;
REVOKE ALL ON FUNCTION simbar.local_now() FROM PUBLIC, anon, authenticated, service_role;
REVOKE ALL ON FUNCTION simbar.local_today() FROM PUBLIC, anon, authenticated, service_role;

COMMIT;
