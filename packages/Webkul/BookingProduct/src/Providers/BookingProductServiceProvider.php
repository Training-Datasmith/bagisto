<?php

declare (strict_types=1);
namespace Webkul\Booking_Product\Providers;

use Illuminate\Support\Service_Provider;
class Booking_Product_Service_Provider extends Service_Provider
{
    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        $this->load_migrations_from(__DIR__ . '/../Database/Migrations');
        $this->app->register(Event_Service_Provider::class);
    }
}