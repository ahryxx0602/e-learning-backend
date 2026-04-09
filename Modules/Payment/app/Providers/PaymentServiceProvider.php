<?php

namespace Modules\Payment\Providers;

use Illuminate\Support\ServiceProvider;

class PaymentServiceProvider extends ServiceProvider
{
    protected string $name = 'Payment';
    protected string $nameLower = 'payment';

    /**
     * Boot the application events.
     */
    public function boot(): void
    {
        $this->loadMigrationsFrom(module_path($this->name, 'database/migrations'));
        $this->mergeConfigFrom(module_path($this->name, 'config/vnpay.php'), 'vnpay');
    }

    /**
     * Register the service provider.
     */
    public function register(): void
    {
        // ── Repository Binding ──
        $this->app->bind(
            \Modules\Payment\Repositories\OrderRepositoryInterface::class,
            \Modules\Payment\Repositories\OrderRepository::class
        );

        $this->app->register(RouteServiceProvider::class);
    }
}
