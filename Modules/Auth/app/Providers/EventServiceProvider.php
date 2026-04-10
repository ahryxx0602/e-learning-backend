<?php

namespace Modules\Auth\Providers;

use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;
use Modules\Auth\Events\StudentRegistered;
use Modules\Auth\Listeners\SendVerificationEmail;

class EventServiceProvider extends ServiceProvider
{
    /**
     * The event handler mappings for the application.
     *
     * @var array<string, array<int, string>>
     */
    protected $listen = [
        StudentRegistered::class => [
            SendVerificationEmail::class,
        ],
    ];

    /**
     * Indicates if events should be discovered automatically.
     * Tắt auto-discovery để dùng explicit mapping ở trên — rõ ràng hơn cho đồ án.
     *
     * @var bool
     */
    protected static $shouldDiscoverEvents = false;

    /**
     * Configure the proper event listeners for email verification.
     */
    protected function configureEmailVerification(): void {}
}
