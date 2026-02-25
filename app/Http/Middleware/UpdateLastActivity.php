<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class UpdateLastActivity
{
    public function handle(Request $request, Closure $next): Response
    {
        if (auth()->check()) {
            $user = auth()->user();
            
            // Only update if last activity was more than 1 minute ago (to reduce DB writes)
            if (!$user->last_activity_at || $user->last_activity_at->diffInMinutes(now()) >= 1) {
                $user->update([
                    'last_activity_at' => now(),
                ]);
            }
        }

        return $next($request);
    }
}
