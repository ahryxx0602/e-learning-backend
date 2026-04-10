<?php

namespace Modules\Payment\Providers;

use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;
use Modules\Payment\Events\OrderPlaced;
use Modules\Payment\Listeners\SendOrderConfirmationEmail;

class EventServiceProvider extends ServiceProvider
{
    /**
     * The event handler mappings for the application.
     *
     * @var array<string, array<int, string>>
     */
    protected $listen = [
        OrderPlaced::class => [
            SendOrderConfirmationEmail::class,
        ],
    ];

    /**
     * Tắt auto-discovery để dùng explicit mapping ở trên.
     *
     * @var bool
     */
    protected static $shouldDiscoverEvents = false;
}
