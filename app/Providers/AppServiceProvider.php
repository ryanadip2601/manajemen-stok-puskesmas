<?php

namespace App\Providers;

use App\Models\Log;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\View;
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

        View::composer('layouts.app', function ($view) {
            if (!Auth::check()) {
                $view->with('runningActivities', collect());
                return;
            }

            $runningActivities = Log::with('user:id,name')
                ->latest('created_at')
                ->take(12)
                ->get()
                ->map(function ($log) {
                    $actor = $log->user?->name ?? 'Sistem';
                    $description = trim((string) ($log->description ?: $log->action));

                    if ($description === '') {
                        return null;
                    }

                    $timeLabel = $log->created_at ? $log->created_at->diffForHumans() : null;

                    return $timeLabel
                        ? $actor . ' - ' . $description . ' (' . $timeLabel . ')'
                        : $actor . ' - ' . $description;
                })
                ->filter()
                ->values();

            $view->with('runningActivities', $runningActivities);
        });
    }
}
