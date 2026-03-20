<?php

declare (strict_types=1);
namespace Webkul\Booking_Product\Helpers;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Webkul\Product\Data_Types\Cart_Item_Validation_Result;
class Rental_Slot extends Booking
{
    /**
     * Returns slots for a particular day.
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
        return $this->slots_calculation($booking_product, $requested_date, $booking_product_slot);
    }
    /**
     * Returns get booked quantity.
     *
     * @param  array  $data
     */
    public function get_booked_quantity($data): int
    {
        $booking_product = $this->booking_product_repository->find_one_by_field('product_id', $data['product_id']);
        $renting_type = $data['additional']['booking']['renting_type'] ?? $booking_product->rental_slot->renting_type;
        if ($renting_type == 'daily') {
            $from = Carbon::create_from_time_string($data['additional']['booking']['date_from'] . ' 00:00:01')->get_timestamp();
            $to = Carbon::create_from_time_string($data['additional']['booking']['date_to'] . ' 23:59:59')->get_timestamp();
        } else {
            $from = Carbon::create_from_timestamp($data['additional']['booking']['slot']['from'])->get_timestamp();
            $to = Carbon::create_from_timestamp($data['additional']['booking']['slot']['to'])->get_timestamp();
        }
        $result = $this->booking_repository->get_model()->left_join('order_items', 'bookings.order_item_id', '=', 'order_items.id')->add_select(DB::raw('SUM(qty_ordered - qty_canceled - qty_refunded) as total_qty_booked'))->where('bookings.product_id', $data['product_id'])->where(function ($query) use ($from, $to) {
            $query->where_between('bookings.from', [$from, $to])->or_where_between('bookings.to', [$from, $to]);
        })->first();
        return $result->total_qty_booked ?? 0;
    }
    /**
     * Returns slots that are going to expire.
     *
     * @param  \Webkul\Checkout\Contracts\CartItem  $cartItem
     */
    public function is_slot_expired($cart_item): bool
    {
        $booking_product = $this->booking_product_repository->find_one_by_field('product_id', $cart_item['product_id']);
        if (isset($cart_item['additional']['booking']['date'])) {
            $time_intervals = $this->get_slots_by_date($booking_product, $cart_item['additional']['booking']['date']);
            foreach ($time_intervals as $time_interval) {
                foreach ($time_interval['slots'] as $slot) {
                    if ($slot['from_timestamp'] == $cart_item['additional']['booking']['slot']['from'] && $slot['to_timestamp'] == $cart_item['additional']['booking']['slot']['to']) {
                        return false;
                    }
                }
            }
            return true;
        } else {
            $requested_from_date = Carbon::create_from_time_string($cart_item['additional']['booking']['date_from'] . ' 00:00:00');
            $requested_to_date = Carbon::create_from_time_string($cart_item['additional']['booking']['date_to'] . ' 23:59:59');
            $available_from = !$booking_product->available_every_week && $booking_product->available_from ? Carbon::create_from_time_string($booking_product->available_from->format('Y-m-d') . ' 00:00:00') : Carbon::now()->copy()->start_of_day();
            $available_to = !$booking_product->available_every_week && $booking_product->available_from ? Carbon::create_from_time_string($booking_product->available_to->format('Y-m-d') . ' 23:59:59') : Carbon::create_from_time_string('2080-01-01 00:00:00');
            return $requested_from_date < $available_from || $requested_from_date > $available_to || $requested_to_date < $available_from || $requested_to_date > $available_to;
        }
    }
    /**
     * Add booking additional prices to cart item.
     */
    public function add_additional_prices(array $products): array
    {
        $booking_product = $this->booking_product_repository->find_one_by_field('product_id', $products[0]['product_id']);
        $renting_type = $products[0]['additional']['booking']['renting_type'] ?? $booking_product->rental_slot->renting_type;
        if ($renting_type == 'daily') {
            $from = Carbon::create_from_time_string($products[0]['additional']['booking']['date_from'] . ' 00:00:00');
            $to = Carbon::create_from_time_string($products[0]['additional']['booking']['date_to'] . ' 24:00:00');
            $price = $booking_product->rental_slot->daily_price * $to->diff_in_days($from);
        } else {
            $from = Carbon::create_from_timestamp($products[0]['additional']['booking']['slot']['from']);
            $to = Carbon::create_from_timestamp($products[0]['additional']['booking']['slot']['to']);
            $price = $booking_product->rental_slot->hourly_price * $to->diff_in_hours($from);
        }
        $price = core()->convert_price($price);
        $quantity = $products[0]['quantity'];
        $products[0]['price'] += $price;
        $products[0]['base_price'] += $price;
        $products[0]['total'] += $price * $quantity;
        $products[0]['base_total'] += $price * $quantity;
        return $products;
    }
    /**
     * Validate cart item product price.
     *
     * @param  \Webkul\Checkout\Models\CartItem  $item
     */
    public function validate_cart_item($item): Cart_Item_Validation_Result
    {
        $result = new Cart_Item_Validation_Result();
        if (parent::is_cart_item_inactive($item)) {
            $result->item_is_inactive();
            return $result;
        }
        $price = $item->product->get_type_instance()->get_final_price($item->quantity);
        $booking_product = $this->booking_product_repository->find_one_by_field('product_id', $item->product_id);
        $booking_info = $item->additional['booking'] ?? null;
        $renting_type = $booking_info['renting_type'] ?? $booking_product->rental_slot->renting_type;
        if ($renting_type == 'daily') {
            if (!isset($booking_info['date_from']) || !isset($booking_info['date_to'])) {
                $result->item_is_inactive();
                return $result;
            }
            $from = Carbon::create_from_time_string($booking_info['date_from'] . ' 00:00:00');
            $to = Carbon::create_from_time_string($booking_info['date_to'] . ' 24:00:00');
            $price += $booking_product->rental_slot->daily_price * $to->diff_in_days($from);
        } else {
            if (!isset($item->additional['booking']['slot']['from']) || !isset($item->additional['booking']['slot']['to'])) {
                $result->item_is_inactive();
                return $result;
            }
            $from = Carbon::create_from_timestamp($item->additional['booking']['slot']['from']);
            $to = Carbon::create_from_timestamp($item->additional['booking']['slot']['to']);
            $price += $booking_product->rental_slot->hourly_price * $to->diff_in_hours($from);
        }
        if ($price == $item->base_price) {
            return $result;
        }
        $item->base_price = $price;
        $item->price = core()->convert_price($price);
        $item->base_total = $price * $item->quantity;
        $item->total = core()->convert_price($price * $item->quantity);
        $item->save();
        return $result;
    }
}