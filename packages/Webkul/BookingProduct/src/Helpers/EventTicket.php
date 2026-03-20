<?php

declare (strict_types=1);
namespace Webkul\Booking_Product\Helpers;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Webkul\Checkout\Models\Cart_Item;
use Webkul\Product\Data_Types\Cart_Item_Validation_Result;
class Event_Ticket extends Booking
{
    /**
     * Returns event date
     *
     * @param  \Webkul\BookingProduct\Contracts\BookingProduct  $bookingProduct
     */
    public function get_event_date($booking_product): string
    {
        $from = Carbon::create_from_time_string($booking_product->available_from)->format('d F, Y h:i A');
        $to = Carbon::create_from_time_string($booking_product->available_to)->format('d F, Y h:i A');
        return $from . ' - ' . $to;
    }
    /**
     * Returns tickets
     *
     * @param  \Webkul\BookingProduct\Contracts\BookingProduct  $bookingProduct
     */
    public function get_tickets($booking_product)
    {
        if (!$booking_product->event_tickets()->count()) {
            return [];
        }
        return $this->format_price($booking_product->event_tickets);
    }
    /**
     * Format ticket price.
     *
     * @param  array  $tickets
     */
    public function format_price($tickets)
    {
        foreach ($tickets as $index => $ticket) {
            $price = $ticket->price;
            if ($this->is_in_sale($ticket)) {
                $price = $ticket->special_price;
                $tickets[$index]['original_converted_price'] = core()->convert_price($ticket->price);
                $tickets[$index]['original_formatted_price'] = core()->currency($ticket->price);
            }
            $tickets[$index]['id'] = $ticket->id;
            $tickets[$index]['converted_price'] = core()->convert_price($price);
            $tickets[$index]['formatted_price'] = $formatted_price = core()->currency($price);
            $tickets[$index]['formatted_price_text'] = trans('shop::app.products.booking.per-ticket-price', ['price' => $formatted_price]);
        }
        return $tickets;
    }
    /**
     * Return the item if it has a quantity.
     *
     * @param  \Webkul\Checkout\Contracts\CartItem|array  $cartItem
     */
    public function is_item_have_quantity($cart_item): bool
    {
        $booking_product = $this->booking_product_repository->find_one_by_field('product_id', $cart_item['product_id']);
        $ticket = $booking_product->event_tickets()->find($cart_item['additional']['booking']['ticket_id']);
        if ($ticket->qty - $this->get_booked_quantity($cart_item) < $cart_item['quantity']) {
            return false;
        }
        return true;
    }
    /**
     * Returns the quantity of booked product.
     *
     * @param  array  $data
     */
    public function get_booked_quantity($data): int
    {
        $result = $this->booking_repository->get_model()->left_join('order_items', 'bookings.order_item_id', '=', 'order_items.id')->add_select(DB::raw('SUM(qty_ordered - qty_canceled - qty_refunded) as total_qty_booked'))->where('bookings.product_id', $data['product_id'])->where('bookings.booking_product_event_ticket_id', $data['additional']['booking']['ticket_id'])->first();
        return !is_null($result->total_qty_booked) ? $result->total_qty_booked : 0;
    }
    /**
     * Add booking additional prices to cart item.
     */
    public function add_additional_prices(array $products): array
    {
        foreach ($products as $key => $product) {
            $booking_product = $this->booking_product_repository->find_one_by_field('product_id', $product['product_id']);
            $ticket = $booking_product->event_tickets()->find($product['additional']['booking']['ticket_id']);
            $price = $ticket->price;
            if ($this->is_in_sale($ticket)) {
                $price = $ticket->special_price;
            }
            $products[$key]['price'] += core()->convert_price($price);
            $products[$key]['base_price'] += $price;
            $products[$key]['total'] += core()->convert_price($price) * $products[$key]['quantity'];
            $products[$key]['base_total'] += $price * $products[$key]['quantity'];
        }
        return $products;
    }
    /**
     * Validate cart item product price.
     */
    public function validate_cart_item(Cart_Item $item): Cart_Item_Validation_Result
    {
        $result = new Cart_Item_Validation_Result();
        if (parent::is_cart_item_inactive($item)) {
            $result->item_is_inactive();
            return $result;
        }
        $price = $item->product->get_type_instance()->get_final_price($item->quantity);
        $booking_product = $this->booking_product_repository->find_one_by_field('product_id', $item->product_id);
        $ticket = $booking_product->event_tickets()->find($item->additional['booking']['ticket_id']);
        if (!$ticket) {
            $result->item_is_inactive();
            return $result;
        }
        if ($this->is_in_sale($ticket)) {
            $price += $ticket->special_price;
        } else {
            $price += $ticket->price;
        }
        if ($price === $item->base_price) {
            return $result;
        }
        $item->base_price = $price;
        $item->price = core()->convert_price($price);
        $item->base_total = $price * $item->quantity;
        $item->total = core()->convert_price($price * $item->quantity);
        $item->save();
        return $result;
    }
    /**
     * Determines whether a single ticket is in Sale, i.e. has a valid sale price.
     */
    public function is_in_sale($ticket): bool
    {
        return $ticket->special_price !== null && $ticket->special_price > 0.0 && ($ticket->special_price_from === null || $ticket->special_price_from === '0000-00-00 00:00:00' || $ticket->special_price_from <= Carbon::now()) && ($ticket->special_price_to === null || $ticket->special_price_to === '0000-00-00 00:00:00' || $ticket->special_price_to > Carbon::now());
    }
}