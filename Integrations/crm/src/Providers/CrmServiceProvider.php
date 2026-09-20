<?php

namespace Integrations\Crm\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Routing\Router;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Integrations\Crm\Http\Middleware\AuthenticateCrm;

class CrmServiceProvider extends ServiceProvider
{
    protected string $moduleName = 'crm';

    public function register(): void
    {
        $this->mergeConfigFrom($this->path('config/crm.php'), $this->moduleName);
    }

    public function boot(Router $router): void
    {
        $router->aliasMiddleware('crm.auth', AuthenticateCrm::class);

        $this->configureRateLimiting();
        $this->registerRoutes();

        $this->publishes([
            $this->path('config/crm.php') => config_path('crm.php'),
        ], 'crm-config');
    }

    protected function registerRoutes(): void
    {
        $routes = config($this->moduleName.'.routes', []);

        Route::middleware($routes['middleware'] ?? ['api', 'crm.auth'])
            ->prefix($routes['prefix'] ?? 'api/crm')
            ->name($routes['name'] ?? 'CRM::')
            ->group($this->path('routes/api.php'));
    }

    protected function configureRateLimiting(): void
    {
        RateLimiter::for('crm', fn (Request $request) => Limit::perMinute(
            (int) config($this->moduleName.'.rate_limit', 120)
        )->by($request->ip()));
    }

    protected function path(string $file): string
    {
        return base_path('Integrations/'.$this->moduleName.'/'.$file);
    }
}
