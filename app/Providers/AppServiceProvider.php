<?php

namespace App\Providers;

use App\Services\PaymentService;
use App\Services\NotificationService;
use App\Services\SubscriptionService;
use Illuminate\Support\ServiceProvider;
use App\Interfaces\PaymentServiceInterface;
use App\Interfaces\NotificationServiceInterface;
use App\Interfaces\SubscriptionServiceInterface;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(SubscriptionServiceInterface::class, SubscriptionService::class);
        $this->app->bind(PaymentServiceInterface::class, PaymentService::class);
        $this->app->bind(NotificationServiceInterface::class, NotificationService::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
