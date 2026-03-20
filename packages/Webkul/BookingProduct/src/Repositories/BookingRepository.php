<?php

declare (strict_types=1);
namespace Webkul\Booking_Product\Repositories;

use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Webkul\Booking_Product\Contracts\Booking;
use Webkul\Core\Eloquent\Repository;
class Booking_Repository extends Repository
{
    /**
     * Specify Model class name
     */
    public function model(): string
    {
        return Booking::class;
    }
    /**
     * Create Booking Product.
     */
    public function create(array $data): void
    {
        $order = $data['order'];
        foreach ($order->items()->get() as $item) {
            if ($item->type != 'booking') {
                continue;
            }
            Event::dispatch('booking_product.booking.save.before', $item);
            $from = $to = null;
            $booking_item = $item->additional['booking'];
            if (isset($booking_item['slot'])) {
                if (isset($booking_item['slot']['from'], $booking_item['slot']['to'])) {
                    $from = $booking_item['slot']['from'];
                    $to = $booking_item['slot']['to'];
                } else {
                    $timestamps = explode('-', $booking_item['slot']);
                    $from = $timestamps[0];
                    $to = $timestamps[1];
                }
            } elseif (isset($booking_item['date_from'], $booking_item['date_to'])) {
                $from = Carbon::create_from_time_string($booking_item['date_from'] . ' 00:00:00')->get_timestamp();
                $to = Carbon::create_from_time_string($booking_item['date_to'] . ' 23:59:59')->get_timestamp();
            }
            $booking = parent::create(['qty' => $item->qty_ordered, 'from' => $from, 'to' => $to, 'order_id' => $order->id, 'order_item_id' => $item->id, 'product_id' => $item->product_id, 'booking_product_event_ticket_id' => $booking_item['ticket_id'] ?? null]);
            Event::dispatch('booking_product.booking.save.after', $booking);
        }
    }
    /**
     * Get all bookings for the given date and time range.
     */
    public function get_bookings(array $date_range): Collection
    {
        $table_prefix = DB::get_table_prefix();
        return $this->select('bookings.id', 'bookings.order_id', 'bookings.from as start', 'bookings.to as end', 'orders.status as status', 'orders.customer_email as email', 'orders.grand_total as total', 'orders.created_at as created_at', 'addresses.address as address', 'addresses.phone as contact', 'addresses.city as city', 'addresses.state as state', 'addresses.country as country', 'addresses.postcode as postcode')->add_select(DB::raw('CONCAT(' . $table_prefix . 'orders.customer_first_name, " ", ' . $table_prefix . 'orders.customer_last_name) as full_name'))->left_join('orders', 'bookings.order_id', '=', 'orders.id')->left_join('addresses', 'bookings.order_id', '=', 'addresses.order_id')->where_between('bookings.from', $date_range)->distinct()->get();
    }
}