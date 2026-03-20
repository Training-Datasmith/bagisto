<?php

declare (strict_types=1);
namespace Webkul\CMS\Providers;

use Illuminate\Support\Service_Provider;
class Cms_Service_Provider extends Service_Provider
{
    /**
     * Bootstrap services.
     *
     * @return void
     */
    public function boot()
    {
        $this->load_migrations_from(__DIR__ . '/../Database/Migrations');
    }
}