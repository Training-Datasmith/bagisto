<?php

declare (strict_types=1);
namespace Webkul\Core\Providers;

class Module_Service_Provider extends Core_Module_Service_Provider
{
    /**
     * Models.
     *
     * @var array
     */
    protected $models = [\Webkul\Core\Models\Channel::class, \Webkul\Core\Models\Core_Config::class, \Webkul\Core\Models\Country::class, \Webkul\Core\Models\Country_State::class, \Webkul\Core\Models\Country_State_Translation::class, \Webkul\Core\Models\Country_Translation::class, \Webkul\Core\Models\Currency::class, \Webkul\Core\Models\Currency_Exchange_Rate::class, \Webkul\Core\Models\Locale::class, \Webkul\Core\Models\Subscribers_List::class, \Webkul\Core\Models\Visit::class];
}