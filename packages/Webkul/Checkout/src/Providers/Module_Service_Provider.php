<?php

declare (strict_types=1);
namespace Webkul\Checkout\Providers;

use Webkul\Core\Providers\Core_Module_Service_Provider;
class Module_Service_Provider extends Core_Module_Service_Provider
{
    /**
     * Models.
     *
     * @var array
     */
    protected $models = [\Webkul\Checkout\Models\Cart::class, \Webkul\Checkout\Models\Cart_Address::class, \Webkul\Checkout\Models\Cart_Item::class, \Webkul\Checkout\Models\Cart_Payment::class, \Webkul\Checkout\Models\Cart_Shipping_Rate::class];
}