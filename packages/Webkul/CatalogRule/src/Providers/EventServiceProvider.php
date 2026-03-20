<?php

declare (strict_types=1);
namespace Webkul\Catalog_Rule\Providers;

use Illuminate\Foundation\Support\Providers\Event_Service_Provider as ServiceProvider;
class Event_Service_Provider extends Service_Provider
{
    /**
     * The event handler mappings for the application.
     *
     * @var array
     */
    protected $listen = ['promotions.catalog_rule.create.after' => ['Webkul\CatalogRule\Listeners\CatalogRule@afterUpdateCreate'], 'promotions.catalog_rule.update.after' => ['Webkul\CatalogRule\Listeners\CatalogRule@afterUpdateCreate'], 'promotions.catalog_rule.update.before' => ['Webkul\CatalogRule\Listeners\CatalogRule@beforeUpdate'], 'promotions.catalog_rule.delete.before' => ['Webkul\CatalogRule\Listeners\CatalogRule@beforeDelete'], 'catalog.product.update.after' => ['Webkul\CatalogRule\Listeners\Product@afterUpdate']];
}