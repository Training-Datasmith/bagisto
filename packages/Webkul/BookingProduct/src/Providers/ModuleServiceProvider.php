<?php

declare (strict_types=1);
namespace Webkul\Booking_Product\Providers;

use Webkul\Core\Providers\Core_Module_Service_Provider;
class Module_Service_Provider extends Core_Module_Service_Provider
{
    /**
     * Models.
     *
     * @var array
     */
    protected $models = [\Webkul\Booking_Product\Models\Booking_Product::class, \Webkul\Booking_Product\Models\Booking_Product_Default_Slot::class, \Webkul\Booking_Product\Models\Booking_Product_Appointment_Slot::class, \Webkul\Booking_Product\Models\Booking_Product_Event_Ticket::class, \Webkul\Booking_Product\Models\Booking_Product_Event_Ticket_Translation::class, \Webkul\Booking_Product\Models\Booking_Product_Rental_Slot::class, \Webkul\Booking_Product\Models\Booking_Product_Table_Slot::class, \Webkul\Booking_Product\Models\Booking::class];
}