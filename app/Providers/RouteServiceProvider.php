<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Foundation\Support\Providers\RouteServiceProvider as ServiceProvider;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;

class RouteServiceProvider extends ServiceProvider
{
    public const HOME = '/';

    public const Login = '/login';

    public function boot()
    {
        $this->configureRateLimiting();
        $this->routes(function () {
            // API routes (Vue SPA data layer)
            Route::middleware('api')->prefix('api')->group(base_path('routes/api.php'));

            // Web routes (auth + SPA catch-all)
            Route::middleware('web')->group(base_path('routes/web.php'));
        });
    }

    protected function configureRateLimiting()
    {
        RateLimiter::for('api', function (Request $request) {
            return Limit::perMinute(60)->by($request->user()?->id ?: $request->ip());
        });

        // Credential stuffing guard: keyed on email AND ip so one attacker cannot
        // lock a real user out by hammering their address from elsewhere.
        RateLimiter::for('login', function (Request $request) {
            return Limit::perMinute(5)->by(
                Str::lower((string) $request->input('email')).'|'.$request->ip()
            );
        });
    }
}
