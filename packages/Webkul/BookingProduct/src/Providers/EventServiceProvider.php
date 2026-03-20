<?php

declare (strict_types=1);
namespace Webkul\Booking_Product\Providers;

use Illuminate\Foundation\Support\Providers\Event_Service_Provider as ServiceProvider;
class Event_Service_Provider extends Service_Provider
{
    /**
     * The event handler mappings for the application.
     *
     * @var array
     */
    protected $listen = ['checkout.order.save.after' => ['Webkul\BookingProduct\Listeners\Order@afterPlaceOrder']];
}