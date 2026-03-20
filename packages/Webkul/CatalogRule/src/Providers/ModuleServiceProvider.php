<?php

declare (strict_types=1);
namespace Webkul\Catalog_Rule\Providers;

use Webkul\Core\Providers\Core_Module_Service_Provider;
class Module_Service_Provider extends Core_Module_Service_Provider
{
    /**
     * Models.
     *
     * @var array
     */
    protected $models = [\Webkul\Catalog_Rule\Models\Catalog_Rule::class, \Webkul\Catalog_Rule\Models\Catalog_Rule_Product::class, \Webkul\Catalog_Rule\Models\Catalog_Rule_Product_Price::class];
}