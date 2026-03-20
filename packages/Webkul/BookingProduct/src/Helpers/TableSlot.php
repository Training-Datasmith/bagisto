<?php

declare (strict_types=1);
namespace Webkul\Booking_Product\Helpers;

use Carbon\Carbon;
class Table_Slot extends Booking
{
    /**
     * Return the item if it has a quantity.
     *
     * @param  \Webkul\Checkout\Contracts\CartItem  $cartItem
     */
    public function is_item_have_quantity($cart_item): bool
    {
        $booking_product = $this->booking_product_repository->find_one_by_field('product_id', $cart_item['product_id']);
        if (!$booking_product) {
            return false;
        }
        $table_slot = $booking_product->table_slot;
        $prevent_days = $table_slot->prevent_scheduling_before ?? 0;
        $min_allowed_date = Carbon::now()->add_days($prevent_days)->format('Y-m-d');
        $booking_date = $cart_item['additional']['booking']['date'] ?? null;
        if ($booking_date && $booking_date < $min_allowed_date) {
            return false;
        }
        $booked_qty = $this->get_booked_quantity($cart_item);
        $requested_qty = $cart_item['quantity'];
        if ($table_slot->price_type == 'table') {
            $multiplier = $table_slot->guest_limit;
            $requested_qty *= $multiplier;
            $booked_qty *= $multiplier;
        }
        return $booking_product->qty - $booked_qty >= $requested_qty && !$this->is_slot_expired($cart_item);
    }
}