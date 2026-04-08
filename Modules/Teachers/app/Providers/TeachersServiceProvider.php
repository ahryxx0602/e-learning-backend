<?php

namespace Modules\Teachers\Providers;

use Illuminate\Support\ServiceProvider;

class TeachersServiceProvider extends ServiceProvider
{
    protected string $name = 'Teachers';
    protected string $nameLower = 'teachers';

    /**
     * Boot the application events.
     */
    public function boot(): void
    {
        $this->loadMigrationsFrom(module_path($this->name, 'database/migrations'));
    }

    /**
     * Register the service provider.
     */
    public function register(): void
    {
        // ── Repository Binding ──
        $this->app->bind(
            \Modules\Teachers\Repositories\TeachersRepositoryInterface::class,
            \Modules\Teachers\Repositories\TeachersRepository::class
        );

        // ── Helper Binding ──
        $this->app->singleton('TeachersHelper', function () {
            return new \Modules\Teachers\Helpers\TeachersHelper();
        });

        $this->app->register(\Modules\Teachers\Providers\RouteServiceProvider::class);
    }
}
