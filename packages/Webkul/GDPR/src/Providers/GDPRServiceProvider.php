<?php

declare(strict_types=1);

namespace Webkul\GDPR\Providers;

use Illuminate\Support\ServiceProvider;

class GDPRServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap any application services.
     */
    public function boot()
    {
        $this->loadMigrationsFrom(__DIR__.'/../Database/Migrations');
    }
}
