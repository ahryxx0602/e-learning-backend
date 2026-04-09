<?php

namespace Modules\Lessons\Providers;

use Illuminate\Support\ServiceProvider;

class LessonServiceProvider extends ServiceProvider
{
    protected string $name = 'Lessons';
    protected string $nameLower = 'lessons';

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
            \Modules\Lessons\Repositories\LessonRepositoryInterface::class,
            \Modules\Lessons\Repositories\LessonRepository::class
        );

        $this->app->bind(
            \Modules\Lessons\Repositories\SectionRepositoryInterface::class,
            \Modules\Lessons\Repositories\SectionRepository::class
        );

        $this->app->register(RouteServiceProvider::class);
    }
}
