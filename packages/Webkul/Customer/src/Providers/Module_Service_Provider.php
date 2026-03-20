<?php

declare (strict_types=1);
namespace Webkul\Customer\Providers;

use Webkul\Core\Providers\Core_Module_Service_Provider;
class Module_Service_Provider extends Core_Module_Service_Provider
{
    /**
     * Models.
     *
     * @var array
     */
    protected $models = [\Webkul\Customer\Models\Compare_Item::class, \Webkul\Customer\Models\Customer::class, \Webkul\Customer\Models\Customer_Address::class, \Webkul\Customer\Models\Customer_Group::class, \Webkul\Customer\Models\Customer_Note::class, \Webkul\Customer\Models\Wishlist::class];
}