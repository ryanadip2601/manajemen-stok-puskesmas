<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class WarehouseAccess
{
    public function handle(Request $request, Closure $next): Response
    {
        if (!auth()->check() || !auth()->user()->canManageWarehouse()) {
            if ($request->wantsJson()) {
                return response()->json(['error' => 'Unauthorized. Warehouse access required.'], 403);
            }
            return redirect()->route('dashboard')->with('error', 'Akses ditolak. Hanya Admin dan Admin Gudang yang bisa mengakses halaman ini.');
        }

        return $next($request);
    }
}
