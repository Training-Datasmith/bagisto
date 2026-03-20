<?php

declare (strict_types=1);
namespace Webkul\Checkout\Providers;

use Illuminate\Support\Service_Provider;
class Checkout_Service_Provider extends Service_Provider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        include __DIR__ . '/../Http/helpers.php';
    }
    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        $this->load_migrations_from(__DIR__ . '/../Database/Migrations');
        $this->app->register(Event_Service_Provider::class);
        $this->app->register(Module_Service_Provider::class);
    }
}