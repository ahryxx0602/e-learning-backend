<?php

namespace Modules\Course\Providers;

use Illuminate\Support\ServiceProvider;

class CourseServiceProvider extends ServiceProvider
{
    protected string $name = 'Course';
    protected string $nameLower = 'course';

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
            \Modules\Course\Repositories\CourseRepositoryInterface::class,
            \Modules\Course\Repositories\CourseRepository::class
        );

        // ── Helper Binding ──
        $this->app->singleton('CourseHelper', function () {
            return new \Modules\Course\Helpers\CourseHelper();
        });

        $this->app->register(RouteServiceProvider::class);
    }
}
