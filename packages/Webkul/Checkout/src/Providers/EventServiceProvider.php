<?php

declare (strict_types=1);
namespace Webkul\Checkout\Providers;

use Illuminate\Foundation\Support\Providers\Event_Service_Provider as ServiceProvider;
class Event_Service_Provider extends Service_Provider
{
    /**
     * The subscriber classes to register.
     *
     * @var array
     */
    protected $subscribe = ['Webkul\Checkout\Listeners\CustomerEventsHandler'];
}