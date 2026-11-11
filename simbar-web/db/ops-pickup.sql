-- Pengambilan barang (every approved role) and the warehouse checklist. Safe to re-run.
-- Ports StockOutRequestController: approving a request records barang keluar and completes it at once.
-- Requires ops-core.sql and ops-stock.sql.

BEGIN;

CREATE OR REPLACE FUNCTION public.simbar_pickup_options()
RETURNS jsonb
LANGUAGE plpgsql STABLE SECURITY DEFINER SET search_path = ''
AS $function$
BEGIN
    PERFORM simbar.require_profile(ARRAY['admin', 'admin_gudang', 'staff']);
    RETURN (SELECT coalesce(jsonb_agg(jsonb_build_object('id', i.id, 'code', i.code, 'name', i.name, 'photo', i.photo,
            'stock', i.stock, 'unit', u.symbol, 'category', c.name) ORDER BY lower(i.name), i.id), '[]')
        FROM simbar.items i
        JOIN simbar.units u ON u.id = i.unit_id
        JOIN simbar.categories c ON c.id = i.category_id
        WHERE i.stock > 0);
END;
$function$;

-- One list for both sides: mine_only shows the caller's requests; otherwise the warehouse checklist.
CREATE OR REPLACE FUNCTION public.simbar_pickup_list(mine_only boolean, status_filter text DEFAULT 'all', page_number integer DEFAULT 1)
RETURNS jsonb
LANGUAGE plpgsql STABLE SECURITY DEFINER SET search_path = ''
AS $function$
DECLARE
    actor simbar.users%ROWTYPE := CASE WHEN mine_only
        THEN simbar.require_profile(ARRAY['admin', 'admin_gudang', 'staff']) ELSE simbar.require_menu(ARRAY['checklist']) END;
    result jsonb;
BEGIN
    IF status_filter IS NULL OR status_filter NOT IN ('all', 'pending', 'approved', 'completed', 'rejected', 'cancelled') OR
       page_number IS NULL OR page_number NOT BETWEEN 1 AND 10000 THEN
        RAISE invalid_parameter_value USING MESSAGE = 'Filter pengambilan tidak valid.';
    END IF;

    WITH scoped AS MATERIALIZED (
        SELECT r.* FROM simbar.stock_out_requests r WHERE NOT mine_only OR r.user_id = actor.id
    ), matched AS MATERIALIZED (
        SELECT r.id, r.status, r.quantity, r.notes, r.request_date, r.pickup_date, r.processed_at, r.reject_reason,
               r.created_at, i.name AS item, i.code, i.stock, i.photo, u.symbol AS unit, req.name AS requested_by, p.name AS processed_by
        FROM scoped r
        JOIN simbar.items i ON i.id = r.item_id
        JOIN simbar.units u ON u.id = i.unit_id
        JOIN simbar.users req ON req.id = r.user_id
        LEFT JOIN simbar.users p ON p.id = r.processed_by
        WHERE status_filter = 'all' OR r.status = status_filter
    )
    SELECT jsonb_build_object(
        'counts', (SELECT jsonb_object_agg(s, (SELECT count(*) FROM scoped WHERE status = s))
                   FROM unnest(ARRAY['pending', 'approved', 'completed', 'rejected', 'cancelled']) AS s),
        'total', (SELECT count(*) FROM matched),
        'rows', coalesce((SELECT jsonb_agg(to_jsonb(m) ORDER BY m.created_at DESC NULLS LAST, m.id DESC)
            FROM (SELECT * FROM matched ORDER BY created_at DESC NULLS LAST, id DESC
                  LIMIT 15 OFFSET (page_number - 1) * 15) m), '[]')
    ) INTO result;
    RETURN result;
END;
$function$;

-- lines: [{"item_id": 1, "quantity": 2, "notes": "..."}], each barang at most once.
CREATE OR REPLACE FUNCTION public.simbar_pickup_submit(lines jsonb)
RETURNS integer
LANGUAGE plpgsql SECURITY DEFINER SET search_path = ''
AS $function$
DECLARE
    actor simbar.users%ROWTYPE := simbar.require_profile(ARRAY['admin', 'admin_gudang', 'staff']);
    line record;
    item simbar.items%ROWTYPE;
    unit_symbol text;
    created integer := 0;
BEGIN
    IF jsonb_typeof(lines) IS DISTINCT FROM 'array' OR jsonb_array_length(lines) NOT BETWEEN 1 AND 50 THEN
        RAISE check_violation USING MESSAGE = 'Tambahkan minimal 1 barang untuk diajukan.';
    END IF;
    IF (SELECT count(DISTINCT value->>'item_id') FROM jsonb_array_elements(lines)) <> jsonb_array_length(lines) THEN
        RAISE check_violation USING MESSAGE = 'Setiap barang hanya boleh dipilih satu kali dalam satu pengajuan.';
    END IF;

    FOR line IN
        SELECT (value->>'item_id')::bigint AS item_id, (value->>'quantity')::integer AS quantity,
               nullif(btrim(value->>'notes'), '') AS notes, ordinality
        FROM jsonb_array_elements(lines) WITH ORDINALITY
    LOOP
        SELECT * INTO item FROM simbar.items WHERE id = line.item_id;
        IF NOT FOUND THEN
            RAISE check_violation USING MESSAGE = 'Data barang pada baris ke-' || line.ordinality || ' tidak ditemukan.';
        ELSIF line.quantity IS NULL OR line.quantity < 1 THEN
            RAISE check_violation USING MESSAGE = 'Jumlah minimal 1.';
        ELSIF char_length(coalesce(line.notes, '')) > 500 THEN
            RAISE check_violation USING MESSAGE = 'Keterangan maksimal 500 karakter.';
        END IF;
        SELECT symbol INTO unit_symbol FROM simbar.units WHERE id = item.unit_id;
        IF line.quantity > item.stock THEN
            RAISE check_violation USING MESSAGE = 'Jumlah permintaan untuk "' || item.name
                || '" melebihi stok yang tersedia (' || item.stock || ' ' || coalesce(unit_symbol, '') || ').';
        END IF;

        INSERT INTO simbar.stock_out_requests (user_id, item_id, quantity, notes, status, request_date, created_at, updated_at)
        VALUES (actor.id, item.id, line.quantity, line.notes, 'pending', simbar.local_today(), simbar.local_now(), simbar.local_now());
        PERFORM simbar.write_log(actor.id, 'stock_request_created', 'User ' || actor.name
            || ' mengajukan permintaan pengambilan ' || item.name || ' sebanyak ' || line.quantity);
        created := created + 1;
    END LOOP;
    RETURN created;
END;
$function$;

CREATE OR REPLACE FUNCTION public.simbar_pickup_cancel(request_id bigint)
RETURNS void
LANGUAGE plpgsql SECURITY DEFINER SET search_path = ''
AS $function$
DECLARE
    actor simbar.users%ROWTYPE := simbar.require_profile(ARRAY['admin', 'admin_gudang', 'staff']);
    request simbar.stock_out_requests%ROWTYPE;
BEGIN
    SELECT * INTO request FROM simbar.stock_out_requests WHERE id = request_id AND user_id = actor.id FOR UPDATE;
    IF NOT FOUND THEN
        RAISE no_data_found USING MESSAGE = 'Permintaan tidak ditemukan.';
    ELSIF request.status <> 'pending' THEN
        RAISE check_violation USING MESSAGE = 'Hanya permintaan dengan status "Menunggu" yang dapat dibatalkan.';
    END IF;
    UPDATE simbar.stock_out_requests SET status = 'cancelled', updated_at = simbar.local_now() WHERE id = request_id;
    PERFORM simbar.write_log(actor.id, 'stock_request_cancelled', 'User ' || actor.name || ' membatalkan permintaan pengambilan '
        || (SELECT name FROM simbar.items WHERE id = request.item_id));
END;
$function$;

-- request_action: approve (records barang keluar and completes), reject, complete (legacy approved rows), undo.
CREATE OR REPLACE FUNCTION public.simbar_checklist_act(request_id bigint, request_action text, reason text DEFAULT NULL)
RETURNS void
LANGUAGE plpgsql SECURITY DEFINER SET search_path = ''
AS $function$
DECLARE
    actor simbar.users%ROWTYPE := simbar.require_menu(ARRAY['checklist']);
    request simbar.stock_out_requests%ROWTYPE;
    item_name text;
    requester text;
BEGIN
    SELECT * INTO request FROM simbar.stock_out_requests WHERE id = request_id FOR UPDATE;
    IF NOT FOUND THEN
        RAISE no_data_found USING MESSAGE = 'Permintaan tidak ditemukan.';
    END IF;
    SELECT name INTO item_name FROM simbar.items WHERE id = request.item_id;
    SELECT name INTO requester FROM simbar.users WHERE id = request.user_id;

    IF request_action IN ('approve', 'reject') AND request.status <> 'pending' THEN
        RAISE check_violation USING MESSAGE = 'Permintaan ini sudah diproses sebelumnya.';
    ELSIF request_action IN ('complete', 'undo') AND request.status <> 'approved' THEN
        RAISE check_violation USING MESSAGE = 'Hanya permintaan yang sudah disetujui yang dapat diproses.';
    ELSIF request_action NOT IN ('approve', 'reject', 'complete', 'undo') OR request_action IS NULL THEN
        RAISE invalid_parameter_value USING MESSAGE = 'Aksi checklist tidak valid.';
    ELSIF char_length(coalesce(reason, '')) > 500 THEN
        RAISE check_violation USING MESSAGE = 'Alasan maksimal 500 karakter.';
    END IF;

    IF request_action IN ('approve', 'complete') THEN
        -- simbar_stock_save locks the barang, checks the stock, and logs the barang keluar.
        PERFORM set_config('simbar.via', 'checklist', true);
        PERFORM public.simbar_stock_save('out', NULL, request.item_id, request.quantity, simbar.local_today(),
            CASE request_action WHEN 'approve' THEN 'Auto selesai dari checklist oleh ' || actor.name || ' untuk ' || requester
                ELSE 'Pengambilan oleh ' || requester END
            || coalesce(' - ' || request.notes, ''));
        PERFORM set_config('simbar.via', '', true);
        UPDATE simbar.stock_out_requests SET status = 'completed', pickup_date = simbar.local_today(),
            processed_by = coalesce(processed_by, actor.id), processed_at = coalesce(processed_at, simbar.local_now()),
            updated_at = simbar.local_now()
        WHERE id = request_id;
        PERFORM simbar.write_log(actor.id, CASE request_action WHEN 'approve' THEN 'stock_request_approved_completed' ELSE 'stock_request_completed' END,
            actor.name || ' menyelesaikan pengambilan ' || item_name || ' oleh ' || requester || ' (Qty: ' || request.quantity || ')');
    ELSIF request_action = 'reject' THEN
        UPDATE simbar.stock_out_requests SET status = 'rejected', processed_by = actor.id, processed_at = simbar.local_now(),
            reject_reason = nullif(btrim(reason), ''), updated_at = simbar.local_now()
        WHERE id = request_id;
        PERFORM simbar.write_log(actor.id, 'stock_request_rejected',
            actor.name || ' menolak permintaan pengambilan ' || item_name || ' oleh ' || requester);
    ELSE
        UPDATE simbar.stock_out_requests SET status = 'pending', processed_by = NULL, processed_at = NULL,
            updated_at = simbar.local_now()
        WHERE id = request_id;
        PERFORM simbar.write_log(actor.id, 'stock_request_undo_approval',
            actor.name || ' membatalkan persetujuan pengambilan ' || item_name);
    END IF;
END;
$function$;

DO $grants$
DECLARE
    signature text;
BEGIN
    FOREACH signature IN ARRAY ARRAY[
        'public.simbar_pickup_options()',
        'public.simbar_pickup_list(boolean, text, integer)',
        'public.simbar_pickup_submit(jsonb)',
        'public.simbar_pickup_cancel(bigint)',
        'public.simbar_checklist_act(bigint, text, text)'
    ] LOOP
        EXECUTE 'REVOKE ALL ON FUNCTION ' || signature || ' FROM PUBLIC, anon, authenticated, service_role';
        EXECUTE 'GRANT EXECUTE ON FUNCTION ' || signature || ' TO authenticated';
    END LOOP;
END;
$grants$;

COMMIT;
