<?php

declare (strict_types=1);
namespace Webkul\Customer\Providers;

use Illuminate\Support\Service_Provider;
use Webkul\Customer\Facades\Captcha;
class Customer_Service_Provider extends Service_Provider
{
    /**
     * Bootstrap application services.
     *
     * @param  \Illuminate\Routing\Router  $router
     */
    public function boot(): void
    {
        $this->load_migrations_from(__DIR__ . '/../Database/Migrations');
        $this->load_translations_from(__DIR__ . '/../Resources/lang', 'customer');
        $this->load_views_from(__DIR__ . '/../Resources/views', 'customer');
        $this->app['validator']->extend('captcha', function ($attribute, $value, $parameters) {
            return Captcha::get_facade_root()->validate_response($value);
        });
    }
}