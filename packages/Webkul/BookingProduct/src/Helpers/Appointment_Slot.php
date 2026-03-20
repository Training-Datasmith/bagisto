<?php

declare (strict_types=1);
namespace Webkul\Booking_Product\Helpers;

class Appointment_Slot extends Booking
{
    /**
     * @param  \Webkul\BookingProduct\Contracts\BookingProduct  $bookingProduct
     */
    public function have_sufficient_quantity(int $qty, $booking_product): bool
    {
        return true;
    }
}