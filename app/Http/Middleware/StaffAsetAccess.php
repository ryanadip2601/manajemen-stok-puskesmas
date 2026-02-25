<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class StaffAsetAccess
{
    public function handle(Request $request, Closure $next): Response
    {
        // Staff dan admin bisa akses
        if (auth()->check() && in_array(auth()->user()->role, ['staff', 'admin', 'admin_gudang'])) {
            return $next($request);
        }

        abort(403, 'Akses ditolak.');
    }
}
