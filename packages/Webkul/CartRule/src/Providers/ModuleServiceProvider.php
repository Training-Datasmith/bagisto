<?php

declare (strict_types=1);
namespace Webkul\Cart_Rule\Providers;

use Webkul\Core\Providers\Core_Module_Service_Provider;
class Module_Service_Provider extends Core_Module_Service_Provider
{
    /**
     * Models.
     *
     * @var array
     */
    protected $models = [\Webkul\Cart_Rule\Models\Cart_Rule::class, \Webkul\Cart_Rule\Models\Cart_Rule_Coupon::class, \Webkul\Cart_Rule\Models\Cart_Rule_Coupon_Usage::class, \Webkul\Cart_Rule\Models\Cart_Rule_Customer::class, \Webkul\Cart_Rule\Models\Cart_Rule_Translation::class];
}