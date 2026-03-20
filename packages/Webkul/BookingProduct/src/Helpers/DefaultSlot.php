<?php

declare (strict_types=1);
namespace Webkul\Booking_Product\Helpers;

use Carbon\Carbon;
class Default_Slot extends Booking
{
    /**
     * @return array
     */
    protected $days_of_week = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];
    /**
     * Returns slots for a particular day
     *
     * @param  \Webkul\BookingProduct\Contracts\BookingProduct  $bookingProduct
     */
    public function get_slots_by_date($booking_product, string $date): array
    {
        $booking_product_slot = $this->type_repositories[$booking_product->type]->find_one_by_field('booking_product_id', $booking_product->id);
        if (empty($booking_product_slot->slots)) {
            return [];
        }
        $requested_date = Carbon::create_from_time_string($date . ' 00:00:00');
        $available_from = !$booking_product->available_every_week && $booking_product->available_from ? Carbon::create_from_time_string($booking_product->available_from) : Carbon::now()->copy()->start_of_day();
        $available_to = !$booking_product->available_every_week && $booking_product->available_from ? Carbon::create_from_time_string($booking_product->available_to) : Carbon::create_from_time_string('2080-01-01 00:00:00');
        if ($requested_date < $available_from || $requested_date > $available_to) {
            return [];
        }
        return $booking_product_slot->booking_type == 'one' ? $this->get_one_booking_for_many_days_slots($booking_product_slot, $requested_date) : $this->get_many_bookings_for_one_day_slots($booking_product_slot, $requested_date);
    }
    /**
     * Returns slots for One Booking For Many Days
     *
     * @param  \Webkul\BookingProduct\Contracts\BookingProductTableSlot  $bookingProductSlot
     */
    public function get_one_booking_for_many_days_slots($booking_product_slot, object $requested_date)
    {
        $slots = [];
        foreach ($booking_product_slot->slots as $key => $time_duration) {
            if ($requested_date->day_of_week != $time_duration['from_day']) {
                continue;
            }
            $start_date = (clone $requested_date)->modify('this ' . $this->days_of_week[$time_duration['from_day']]);
            $end_date = (clone $requested_date)->modify('this ' . $this->days_of_week[$time_duration['to_day']]);
            $start_date = Carbon::create_from_time_string($start_date->format('Y-m-d') . ' ' . $time_duration['from'] . ':00');
            $end_date = Carbon::create_from_time_string($end_date->format('Y-m-d') . ' ' . $time_duration['to'] . ':00');
            $slots[] = ['from' => $start_date->format('h:i A'), 'to' => $end_date->format('h:i A'), 'timestamp' => $start_date->get_timestamp() . '-' . $end_date->get_timestamp()];
        }
        return $slots;
    }
    /**
     * Returns slots for Many Bookings for One Day
     *
     * @param  \Webkul\BookingProduct\Contracts\BookingProductTableSlot  $bookingProductSlot
     */
    public function get_many_bookings_for_one_day_slots($booking_product_slot, object $requested_date)
    {
        return $this->slots_calculation($booking_product_slot->booking_product, $requested_date, $booking_product_slot);
    }
}