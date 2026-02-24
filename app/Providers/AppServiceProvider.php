<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\RateLimiter;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        RateLimiter::for('auth-token', function (Request $request) {
            $ip = (string) $request->ip();
            $email = strtolower((string) $request->input('email', ''));

            return Limit::perMinute(10)->by("auth-token:{$ip}:{$email}");
        });

        RateLimiter::for('auth-api', function (Request $request) {
            $userId = (string) ($request->user()?->id ?? 'guest');
            $ip = (string) $request->ip();
            $route = $request->route()?->uri() ?? $request->path();

            return Limit::perMinute(60)->by("auth-api:{$userId}:{$ip}:{$route}");
        });
    }
}
