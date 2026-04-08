<?php

namespace Modules\Upload\Providers;

use Illuminate\Support\ServiceProvider;
use Nwidart\Modules\Traits\PathNamespace;

class UploadServiceProvider extends ServiceProvider
{
    use PathNamespace;

    protected string $name = 'Upload';
    protected string $nameLower = 'upload';

    public function boot(): void
    {
        $this->loadMigrationsFrom(module_path($this->name, 'database/migrations'));
    }

    public function register(): void
    {
        $this->app->register(RouteServiceProvider::class);
    }
}
