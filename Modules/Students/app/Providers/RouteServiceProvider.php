<?php

namespace Modules\Students\Providers;

use Illuminate\Foundation\Support\Providers\RouteServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Route;

class RouteServiceProvider extends ServiceProvider
{
    protected string $name = 'Students';

    /**
     * Called before routes are registered.
     *
     * Register any model bindings or pattern based filters.
     */
    public function boot(): void
    {
        parent::boot();
    }

    /**
     * Define the routes for the application.
     */
    public function map(): void
    {
        $this->mapApiRoutes();
    }

    /**
     * Define the "api" routes for the application.
     *
     * Prefix: api/v1 → routes sẽ là /api/v1/auth/login, etc.
     */
    protected function mapApiRoutes(): void
    {
        Route::middleware('api')
            ->prefix('api/v1')
            ->group(module_path($this->name, '/routes/api.php'));
    }
}
