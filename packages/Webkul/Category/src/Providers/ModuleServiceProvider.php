<?php

declare (strict_types=1);
namespace Webkul\Category\Providers;

use Webkul\Core\Providers\Core_Module_Service_Provider;
class Module_Service_Provider extends Core_Module_Service_Provider
{
    /**
     * Models.
     *
     * @var array
     */
    protected $models = [\Webkul\Category\Models\Category::class, \Webkul\Category\Models\Category_Translation::class];
}