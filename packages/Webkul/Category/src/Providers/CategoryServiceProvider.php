<?php

declare (strict_types=1);
namespace Webkul\Category\Providers;

use Illuminate\Support\Service_Provider;
use Webkul\Category\Models\Category_Proxy;
use Webkul\Category\Observers\Category_Observer;
class Category_Service_Provider extends Service_Provider
{
    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        $this->load_migrations_from(__DIR__ . '/../Database/Migrations');
        Category_Proxy::observe(Category_Observer::class);
    }
}