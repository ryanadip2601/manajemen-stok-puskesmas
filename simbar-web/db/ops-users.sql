-- Accounts: self registration, profile, login history, and admin user management. Safe to re-run.
-- Ports AuthController (register/profile/approval) and UserMonitorController on top of Supabase Auth.
-- Every new Supabase Auth account gets a pending staff profile; an admin approves it, as in Laravel.
-- Legacy users are linked by scripts/import-auth-users.mjs, never by self registration.

BEGIN;

CREATE OR REPLACE FUNCTION simbar.handle_new_auth_user()
RETURNS trigger
LANGUAGE plpgsql SECURITY DEFINER SET search_path = ''
AS $function$
DECLARE
    profile_id bigint;
    display_name text := left(nullif(btrim(NEW.raw_user_meta_data->>'name'), ''), 255);
BEGIN
    -- An existing profile with this email is a legacy account: leave linking to the admin import.
    IF NEW.email IS NULL OR EXISTS (SELECT 1 FROM simbar.users WHERE lower(email) = lower(NEW.email)) THEN
        RETURN NEW;
    END IF;
    INSERT INTO simbar.users (name, email, phone, role, is_approved, auth_user_id, created_at, updated_at)
    VALUES (coalesce(display_name, split_part(NEW.email, '@', 1)), lower(NEW.email),
        left(nullif(btrim(NEW.raw_user_meta_data->>'phone'), ''), 20), 'staff', false, NEW.id, simbar.local_now(), simbar.local_now())
    RETURNING id INTO profile_id;
    PERFORM simbar.write_log(profile_id, 'register', 'User baru ' || coalesce(display_name, NEW.email) || ' mendaftar, menunggu persetujuan admin');
    RETURN NEW;
END;
$function$;

CREATE OR REPLACE FUNCTION simbar.handle_deleted_auth_user()
RETURNS trigger
LANGUAGE plpgsql SECURITY DEFINER SET search_path = ''
AS $function$
BEGIN
    UPDATE simbar.users SET auth_user_id = NULL WHERE auth_user_id = OLD.id;
    RETURN OLD;
END;
$function$;

DROP TRIGGER IF EXISTS simbar_new_auth_user ON auth.users;
CREATE TRIGGER simbar_new_auth_user AFTER INSERT ON auth.users FOR EACH ROW EXECUTE FUNCTION simbar.handle_new_auth_user();
DROP TRIGGER IF EXISTS simbar_deleted_auth_user ON auth.users;
CREATE TRIGGER simbar_deleted_auth_user AFTER DELETE ON auth.users FOR EACH ROW EXECUTE FUNCTION simbar.handle_deleted_auth_user();

-- Works for unapproved accounts too, so the login screen can explain why access is not yet possible.
CREATE OR REPLACE FUNCTION public.simbar_my_status()
RETURNS jsonb
LANGUAGE sql STABLE SECURITY DEFINER SET search_path = ''
AS $function$
    SELECT coalesce((SELECT jsonb_build_object('linked', true, 'approved', is_approved, 'id', id, 'name', name, 'role', role)
        FROM simbar.users WHERE auth_user_id = auth.uid()), jsonb_build_object('linked', false, 'approved', false));
$function$;

-- event: login, logout, or ping (keeps last_activity_at fresh for the "online" view).
CREATE OR REPLACE FUNCTION public.simbar_session_event(event text, agent text DEFAULT NULL)
RETURNS void
LANGUAGE plpgsql SECURITY DEFINER SET search_path = ''
AS $function$
DECLARE
    actor simbar.users%ROWTYPE := simbar.require_profile(ARRAY['admin', 'admin_gudang', 'staff']);
    ip text := left(btrim(split_part(coalesce(current_setting('request.headers', true)::json->>'x-forwarded-for', ''), ',', 1)), 45);
    ua text := left(coalesce(agent, ''), 255);
BEGIN
    IF event IS NULL OR event NOT IN ('login', 'logout', 'ping') THEN
        RAISE invalid_parameter_value USING MESSAGE = 'Peristiwa sesi tidak valid.';
    END IF;
    IF event IN ('login', 'logout') THEN
        INSERT INTO simbar.login_histories (user_id, action, ip_address, user_agent, device, browser, platform, created_at)
        VALUES (actor.id, event, nullif(ip, ''), nullif(ua, ''),
            CASE WHEN ua ~* 'mobile|android|iphone|ipad|ipod' THEN 'Mobile' WHEN ua ~* 'tablet' THEN 'Tablet' WHEN ua = '' THEN 'Unknown' ELSE 'Desktop' END,
            CASE WHEN ua ~* 'MSIE|Trident' THEN 'Internet Explorer' WHEN ua ~* 'Firefox' THEN 'Firefox' WHEN ua ~* 'Edg' THEN 'Edge'
                 WHEN ua ~* 'OPR|Opera' THEN 'Opera' WHEN ua ~* 'Chrome' THEN 'Chrome' WHEN ua ~* 'Safari' THEN 'Safari' ELSE 'Unknown' END,
            CASE WHEN ua ~* 'android' THEN 'Android' WHEN ua ~* 'iphone|ipad|ipod' THEN 'iOS' WHEN ua ~* 'windows' THEN 'Windows'
                 WHEN ua ~* 'macintosh|mac os x' THEN 'Mac OS' WHEN ua ~* 'linux' THEN 'Linux' ELSE 'Unknown' END,
            simbar.local_now());
        PERFORM simbar.write_log(actor.id, event, 'User ' || actor.name || CASE event WHEN 'login' THEN ' berhasil login' ELSE ' logout' END);
    END IF;
    UPDATE simbar.users SET last_activity_at = simbar.local_now(),
        last_login_at = CASE WHEN event = 'login' THEN simbar.local_now() ELSE last_login_at END,
        last_ip_address = CASE WHEN event = 'login' AND ip <> '' THEN ip ELSE last_ip_address END
    WHERE id = actor.id;
END;
$function$;

CREATE OR REPLACE FUNCTION public.simbar_profile_get()
RETURNS jsonb
LANGUAGE plpgsql STABLE SECURITY DEFINER SET search_path = ''
AS $function$
DECLARE
    actor simbar.users%ROWTYPE := simbar.require_profile(ARRAY['admin', 'admin_gudang', 'staff']);
BEGIN
    RETURN jsonb_build_object('id', actor.id, 'name', actor.name, 'email', actor.email, 'phone', actor.phone, 'role', actor.role,
        'created_at', actor.created_at, 'approved_at', actor.approved_at, 'last_login_at', actor.last_login_at);
END;
$function$;

CREATE OR REPLACE FUNCTION public.simbar_profile_update(profile_name text, profile_phone text DEFAULT NULL)
RETURNS void
LANGUAGE plpgsql SECURITY DEFINER SET search_path = ''
AS $function$
DECLARE
    actor simbar.users%ROWTYPE := simbar.require_profile(ARRAY['admin', 'admin_gudang', 'staff']);
BEGIN
    profile_name := btrim(profile_name);
    IF coalesce(profile_name, '') = '' OR char_length(profile_name) > 255 THEN
        RAISE check_violation USING MESSAGE = 'Nama harus diisi.';
    ELSIF char_length(coalesce(profile_phone, '')) > 20 THEN
        RAISE check_violation USING MESSAGE = 'Nomor HP maksimal 20 karakter.';
    END IF;
    UPDATE simbar.users SET name = profile_name, phone = nullif(btrim(profile_phone), ''), updated_at = simbar.local_now() WHERE id = actor.id;
    PERFORM simbar.write_log(actor.id, 'profile_updated', 'User ' || profile_name || ' mengupdate profil');
END;
$function$;

-- Activity the browser completes with Supabase Auth itself (password changes) is logged here.
CREATE OR REPLACE FUNCTION public.simbar_log_self(log_action text)
RETURNS void
LANGUAGE plpgsql SECURITY DEFINER SET search_path = ''
AS $function$
DECLARE
    actor simbar.users%ROWTYPE := simbar.require_profile(ARRAY['admin', 'admin_gudang', 'staff']);
BEGIN
    IF log_action IS NULL OR log_action NOT IN ('password_changed', 'password_reset_success') THEN
        RAISE invalid_parameter_value USING MESSAGE = 'Aktivitas tidak dikenal.';
    END IF;
    PERFORM simbar.write_log(actor.id, log_action, 'User ' || actor.name || CASE log_action
        WHEN 'password_changed' THEN ' mengubah password' ELSE ' berhasil reset password' END);
END;
$function$;

-- filter: all, online (5 min), active (30 min), today, approved, pending. page_number 0 returns everyone.
CREATE OR REPLACE FUNCTION public.simbar_users_list(user_filter text DEFAULT 'all', role_filter text DEFAULT NULL,
    search_text text DEFAULT '', page_number integer DEFAULT 1)
RETURNS jsonb
LANGUAGE plpgsql STABLE SECURITY DEFINER SET search_path = ''
AS $function$
DECLARE
    now_local timestamp := simbar.local_now();
    pattern text;
    result jsonb;
BEGIN
    PERFORM simbar.require_profile(ARRAY['admin']);
    IF user_filter IS NULL OR user_filter NOT IN ('all', 'online', 'active', 'today', 'approved', 'pending') OR
       (role_filter IS NOT NULL AND role_filter NOT IN ('admin', 'admin_gudang', 'staff')) OR
       search_text IS NULL OR char_length(search_text) > 100 OR page_number IS NULL OR page_number NOT BETWEEN 0 AND 10000 THEN
        RAISE invalid_parameter_value USING MESSAGE = 'Filter pengguna tidak valid.';
    END IF;
    pattern := '%' || replace(replace(replace(btrim(search_text), '!', '!!'), '%', '!%'), '_', '!_') || '%';
    WITH matched AS MATERIALIZED (
        SELECT u.id, u.name, u.email, u.phone, u.role, u.is_approved AS approved, u.created_at, u.approved_at,
               u.last_login_at, u.last_activity_at, u.auth_user_id IS NOT NULL AS linked,
               u.last_activity_at >= now_local - interval '5 minutes' AS online
        FROM simbar.users u
        WHERE (user_filter = 'all'
               OR (user_filter = 'online' AND u.is_approved AND u.last_activity_at >= now_local - interval '5 minutes')
               OR (user_filter = 'active' AND u.is_approved AND u.last_activity_at >= now_local - interval '30 minutes')
               OR (user_filter = 'today' AND u.is_approved AND u.last_login_at::date = now_local::date)
               OR (user_filter = 'approved' AND u.is_approved) OR (user_filter = 'pending' AND NOT u.is_approved))
          AND (role_filter IS NULL OR u.role = role_filter)
          AND (u.name ILIKE pattern ESCAPE '!' OR u.email ILIKE pattern ESCAPE '!' OR coalesce(u.phone, '') ILIKE pattern ESCAPE '!')
          AND u.id > 0
    )
    SELECT jsonb_build_object(
        'stats', (SELECT jsonb_build_object('total', count(*), 'approved', count(*) FILTER (WHERE is_approved),
                'pending', count(*) FILTER (WHERE NOT is_approved),
                'online', count(*) FILTER (WHERE is_approved AND last_activity_at >= now_local - interval '5 minutes'),
                'admin', count(*) FILTER (WHERE is_approved AND role = 'admin'),
                'admin_gudang', count(*) FILTER (WHERE is_approved AND role = 'admin_gudang'),
                'staff', count(*) FILTER (WHERE is_approved AND role = 'staff')) FROM simbar.users WHERE id > 0),
        'total', (SELECT count(*) FROM matched),
        'rows', coalesce((SELECT jsonb_agg(to_jsonb(m) ORDER BY m.created_at DESC NULLS LAST, m.id DESC)
            FROM (SELECT * FROM matched ORDER BY created_at DESC NULLS LAST, id DESC
                  LIMIT CASE WHEN page_number = 0 THEN NULL ELSE 15 END
                  OFFSET CASE WHEN page_number = 0 THEN 0 ELSE (page_number - 1) * 15 END) m), '[]')
    ) INTO result;
    RETURN result;
END;
$function$;

CREATE OR REPLACE FUNCTION public.simbar_user_detail(target_id bigint)
RETURNS jsonb
LANGUAGE plpgsql STABLE SECURITY DEFINER SET search_path = ''
AS $function$
DECLARE
    result jsonb;
BEGIN
    PERFORM simbar.require_profile(ARRAY['admin']);
    SELECT jsonb_build_object('id', u.id, 'name', u.name, 'email', u.email, 'phone', u.phone, 'role', u.role, 'approved', u.is_approved,
            'created_at', u.created_at, 'approved_at', u.approved_at, 'approved_by', a.name, 'last_login_at', u.last_login_at,
            'last_activity_at', u.last_activity_at, 'last_ip_address', u.last_ip_address, 'linked', u.auth_user_id IS NOT NULL,
            'stats', jsonb_build_object(
                'total_logins', (SELECT count(*) FROM simbar.login_histories WHERE user_id = u.id AND action = 'login'),
                'total_activities', (SELECT count(*) FROM simbar.logs WHERE user_id = u.id),
                'total_requests', (SELECT count(*) FROM simbar.stock_out_requests WHERE user_id = u.id),
                'completed_requests', (SELECT count(*) FROM simbar.stock_out_requests WHERE user_id = u.id AND status = 'completed')),
            'logins', (SELECT coalesce(jsonb_agg(to_jsonb(h) ORDER BY h.created_at DESC), '[]') FROM (
                SELECT action, ip_address, device, browser, platform, created_at FROM simbar.login_histories
                WHERE user_id = u.id ORDER BY created_at DESC LIMIT 10) h),
            'activities', (SELECT coalesce(jsonb_agg(to_jsonb(l) ORDER BY l.created_at DESC), '[]') FROM (
                SELECT action, description, created_at FROM simbar.logs WHERE user_id = u.id ORDER BY created_at DESC LIMIT 10) l))
    INTO result
    FROM simbar.users u LEFT JOIN simbar.users a ON a.id = u.approved_by
    WHERE u.id = target_id AND u.id > 0;
    IF result IS NULL THEN
        RAISE no_data_found USING MESSAGE = 'Pengguna tidak ditemukan.';
    END IF;
    RETURN result;
END;
$function$;

-- user_action: approve, reject (pending only, removes the account), role (new_role), delete.
CREATE OR REPLACE FUNCTION public.simbar_user_act(target_id bigint, user_action text, new_role text DEFAULT NULL)
RETURNS void
LANGUAGE plpgsql SECURITY DEFINER SET search_path = ''
AS $function$
DECLARE
    actor simbar.users%ROWTYPE := simbar.require_profile(ARRAY['admin']);
    target simbar.users%ROWTYPE;
BEGIN
    SELECT * INTO target FROM simbar.users WHERE id = target_id AND id > 0 FOR UPDATE;
    IF NOT FOUND THEN
        RAISE no_data_found USING MESSAGE = 'Pengguna tidak ditemukan.';
    ELSIF user_action IS NULL OR user_action NOT IN ('approve', 'reject', 'role', 'delete') THEN
        RAISE invalid_parameter_value USING MESSAGE = 'Aksi pengguna tidak valid.';
    ELSIF target.id = actor.id AND user_action IN ('role', 'delete', 'reject') THEN
        RAISE check_violation USING MESSAGE = 'Anda tidak dapat mengubah atau menghapus akun sendiri.';
    ELSIF user_action = 'approve' AND target.is_approved THEN
        RAISE check_violation USING MESSAGE = 'User sudah disetujui sebelumnya.';
    ELSIF user_action = 'reject' AND target.is_approved THEN
        RAISE check_violation USING MESSAGE = 'Tidak dapat menolak user yang sudah disetujui.';
    ELSIF user_action = 'role' AND (new_role IS NULL OR new_role NOT IN ('admin', 'admin_gudang', 'staff')) THEN
        RAISE check_violation USING MESSAGE = 'Role tidak valid.';
    END IF;

    IF user_action = 'approve' THEN
        UPDATE simbar.users SET is_approved = true, approved_at = simbar.local_now(), approved_by = actor.id,
            role = coalesce(new_role, role), updated_at = simbar.local_now() WHERE id = target.id;
        PERFORM simbar.write_log(actor.id, 'user_approved', 'Admin ' || actor.name || ' menyetujui akun ' || target.name);
    ELSIF user_action = 'role' THEN
        UPDATE simbar.users SET role = new_role, updated_at = simbar.local_now() WHERE id = target.id;
        PERFORM simbar.write_log(actor.id, 'user_role_changed', 'Admin ' || actor.name || ' mengubah role ' || target.name
            || ' dari ' || target.role || ' menjadi ' || new_role);
    ELSE
        BEGIN
            DELETE FROM simbar.users WHERE id = target.id;
        EXCEPTION WHEN foreign_key_violation THEN
            RAISE foreign_key_violation USING MESSAGE = 'Akun ini masih tercatat pada transaksi stok, jadi tidak dapat dihapus. Ubah role atau biarkan nonaktif.';
        END;
        IF target.auth_user_id IS NOT NULL THEN
            DELETE FROM auth.users WHERE id = target.auth_user_id;
        END IF;
        PERFORM simbar.write_log(actor.id, CASE user_action WHEN 'reject' THEN 'user_rejected' ELSE 'user_deleted' END,
            'Admin ' || actor.name || CASE user_action WHEN 'reject' THEN ' menolak pendaftaran ' ELSE ' menghapus akun ' END || target.name);
    END IF;
END;
$function$;

-- Admin sets a new password (replaces Laravel's e-mailed reset link, which needs a mail server).
CREATE OR REPLACE FUNCTION public.simbar_user_set_password(target_id bigint, new_password text)
RETURNS void
LANGUAGE plpgsql SECURITY DEFINER SET search_path = ''
AS $function$
DECLARE
    actor simbar.users%ROWTYPE := simbar.require_profile(ARRAY['admin']);
    target simbar.users%ROWTYPE;
BEGIN
    SELECT * INTO target FROM simbar.users WHERE id = target_id AND id > 0;
    IF NOT FOUND OR target.auth_user_id IS NULL THEN
        RAISE no_data_found USING MESSAGE = 'Akun login pengguna ini belum ada.';
    ELSIF new_password IS NULL OR char_length(new_password) < 8 OR char_length(new_password) > 72 THEN
        RAISE check_violation USING MESSAGE = 'Password baru minimal 8 karakter.';
    END IF;
    UPDATE auth.users SET encrypted_password = extensions.crypt(new_password, extensions.gen_salt('bf', 10)), updated_at = now()
    WHERE id = target.auth_user_id;
    PERFORM simbar.write_log(actor.id, 'password_reset_by_admin', 'Admin ' || actor.name || ' mengatur ulang password ' || target.name);
END;
$function$;

-- filters: {user, action, dari, sampai}. 25 rows per page.
CREATE OR REPLACE FUNCTION public.simbar_login_history(filters jsonb DEFAULT '{}', page_number integer DEFAULT 1)
RETURNS jsonb
LANGUAGE plpgsql STABLE SECURITY DEFINER SET search_path = ''
AS $function$
DECLARE
    result jsonb;
BEGIN
    PERFORM simbar.require_profile(ARRAY['admin']);
    IF page_number IS NULL OR page_number NOT BETWEEN 1 AND 10000 THEN
        RAISE invalid_parameter_value USING MESSAGE = 'Halaman tidak valid.';
    END IF;
    WITH matched AS MATERIALIZED (
        SELECT h.id, h.action, h.ip_address, h.device, h.browser, h.platform, h.created_at, u.name, u.role
        FROM simbar.login_histories h JOIN simbar.users u ON u.id = h.user_id
        WHERE (nullif(filters->>'user', '') IS NULL OR h.user_id = (filters->>'user')::bigint)
          AND (nullif(filters->>'action', '') IS NULL OR h.action = filters->>'action')
          AND (nullif(filters->>'dari', '') IS NULL OR h.created_at::date >= (filters->>'dari')::date)
          AND (nullif(filters->>'sampai', '') IS NULL OR h.created_at::date <= (filters->>'sampai')::date)
    )
    SELECT jsonb_build_object('total', (SELECT count(*) FROM matched),
        'stats', (SELECT jsonb_build_object('logins', count(*) FILTER (WHERE action = 'login'), 'logouts', count(*) FILTER (WHERE action = 'logout'),
            'users', count(DISTINCT name)) FROM matched),
        'rows', coalesce((SELECT jsonb_agg(to_jsonb(m) ORDER BY m.created_at DESC, m.id DESC)
            FROM (SELECT * FROM matched ORDER BY created_at DESC, id DESC LIMIT 25 OFFSET (page_number - 1) * 25) m), '[]'))
    INTO result;
    RETURN result;
END;
$function$;

CREATE OR REPLACE FUNCTION public.simbar_activity_logs(filters jsonb DEFAULT '{}', page_number integer DEFAULT 1)
RETURNS jsonb
LANGUAGE plpgsql STABLE SECURITY DEFINER SET search_path = ''
AS $function$
DECLARE
    result jsonb;
BEGIN
    PERFORM simbar.require_profile(ARRAY['admin']);
    IF page_number IS NULL OR page_number NOT BETWEEN 1 AND 10000 THEN
        RAISE invalid_parameter_value USING MESSAGE = 'Halaman tidak valid.';
    END IF;
    WITH matched AS MATERIALIZED (
        SELECT l.id, l.action, l.description, l.created_at, u.name
        FROM simbar.logs l LEFT JOIN simbar.users u ON u.id = l.user_id
        WHERE (nullif(filters->>'user', '') IS NULL OR l.user_id = (filters->>'user')::bigint)
          AND (nullif(filters->>'action', '') IS NULL OR l.action = filters->>'action')
          AND (nullif(filters->>'dari', '') IS NULL OR l.created_at::date >= (filters->>'dari')::date)
          AND (nullif(filters->>'sampai', '') IS NULL OR l.created_at::date <= (filters->>'sampai')::date)
    )
    SELECT jsonb_build_object('total', (SELECT count(*) FROM matched),
        'actions', (SELECT coalesce(jsonb_agg(DISTINCT action ORDER BY action), '[]') FROM simbar.logs),
        'rows', coalesce((SELECT jsonb_agg(to_jsonb(m) ORDER BY m.created_at DESC, m.id DESC)
            FROM (SELECT * FROM matched ORDER BY created_at DESC, id DESC LIMIT 25 OFFSET (page_number - 1) * 25) m), '[]'))
    INTO result;
    RETURN result;
END;
$function$;

-- kind: login_history or activity_logs. Deleting history is logged afterwards (like Laravel).
CREATE OR REPLACE FUNCTION public.simbar_logs_clear(kind text)
RETURNS integer
LANGUAGE plpgsql SECURITY DEFINER SET search_path = ''
AS $function$
DECLARE
    actor simbar.users%ROWTYPE := simbar.require_profile(ARRAY['admin']);
    removed integer;
BEGIN
    IF kind = 'login_history' THEN
        DELETE FROM simbar.login_histories;
    ELSIF kind = 'activity_logs' THEN
        DELETE FROM simbar.logs;
    ELSE
        RAISE invalid_parameter_value USING MESSAGE = 'Jenis riwayat tidak valid.';
    END IF;
    GET DIAGNOSTICS removed = ROW_COUNT;
    PERFORM simbar.write_log(actor.id, kind || '_cleared', 'Admin ' || actor.name || ' menghapus ' || removed
        || CASE kind WHEN 'login_history' THEN ' riwayat login' ELSE ' log aktivitas' END);
    RETURN removed;
END;
$function$;

REVOKE ALL ON FUNCTION simbar.handle_new_auth_user() FROM PUBLIC, anon, authenticated, service_role;
REVOKE ALL ON FUNCTION simbar.handle_deleted_auth_user() FROM PUBLIC, anon, authenticated, service_role;

DO $grants$
DECLARE
    signature text;
BEGIN
    FOREACH signature IN ARRAY ARRAY[
        'public.simbar_my_status()',
        'public.simbar_session_event(text, text)',
        'public.simbar_profile_get()',
        'public.simbar_profile_update(text, text)',
        'public.simbar_log_self(text)',
        'public.simbar_users_list(text, text, text, integer)',
        'public.simbar_user_detail(bigint)',
        'public.simbar_user_act(bigint, text, text)',
        'public.simbar_user_set_password(bigint, text)',
        'public.simbar_login_history(jsonb, integer)',
        'public.simbar_activity_logs(jsonb, integer)',
        'public.simbar_logs_clear(text)'
    ] LOOP
        EXECUTE 'REVOKE ALL ON FUNCTION ' || signature || ' FROM PUBLIC, anon, authenticated, service_role';
        EXECUTE 'GRANT EXECUTE ON FUNCTION ' || signature || ' TO authenticated';
    END LOOP;
END;
$grants$;

COMMIT;
