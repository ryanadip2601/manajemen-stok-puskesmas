<?php

namespace App\Providers;

use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        // Force HTTPS in production (Railway, Vercel, etc.)
        if (config('app.env') === 'production' || str_contains(request()->getHost(), 'railway.app')) {
            URL::forceScheme('https');
        }
    }
}
