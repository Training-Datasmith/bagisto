<?php

declare (strict_types=1);
namespace Webkul\Attribute\Providers;

use Webkul\Core\Providers\Core_Module_Service_Provider;
class Module_Service_Provider extends Core_Module_Service_Provider
{
    /**
     * Models.
     *
     * @var array
     */
    protected $models = [\Webkul\Attribute\Models\Attribute::class, \Webkul\Attribute\Models\Attribute_Family::class, \Webkul\Attribute\Models\Attribute_Group::class, \Webkul\Attribute\Models\Attribute_Option::class, \Webkul\Attribute\Models\Attribute_Option_Translation::class, \Webkul\Attribute\Models\Attribute_Translation::class];
}