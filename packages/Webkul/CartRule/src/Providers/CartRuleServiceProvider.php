<?php

declare (strict_types=1);
namespace Webkul\Cart_Rule\Providers;

use Illuminate\Support\Service_Provider;
class Cart_Rule_Service_Provider extends Service_Provider
{
    /**
     * Bootstrap services.
     *
     * @return void
     */
    public function boot()
    {
        $this->load_migrations_from(__DIR__ . '/../Database/Migrations');
        $this->app->register(Event_Service_Provider::class);
    }
}