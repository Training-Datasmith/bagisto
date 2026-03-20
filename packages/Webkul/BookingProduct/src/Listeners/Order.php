<?php

declare (strict_types=1);
namespace Webkul\Booking_Product\Listeners;

use Webkul\Booking_Product\Repositories\Booking_Repository;
class Order
{
    /**
     * Create a new listener instance.
     *
     * @return void
     */
    public function __construct(protected Booking_Repository $booking_repository)
    {
    }
    /**
     * After sales order creation, add entry to bookings table
     *
     * @param  \Webkul\Sales\Contracts\Order  $order
     */
    public function after_place_order($order)
    {
        $this->booking_repository->create(['order' => $order]);
    }
}