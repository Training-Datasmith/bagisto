<?php

declare (strict_types=1);
namespace Webkul\Booking_Product\Helpers;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Webkul\Booking_Product\Contracts\Booking_Product;
use Webkul\Booking_Product\Repositories\Booking_Product_Appointment_Slot_Repository;
use Webkul\Booking_Product\Repositories\Booking_Product_Default_Slot_Repository;
use Webkul\Booking_Product\Repositories\Booking_Product_Event_Ticket_Repository;
use Webkul\Booking_Product\Repositories\Booking_Product_Rental_Slot_Repository;
use Webkul\Booking_Product\Repositories\Booking_Product_Repository;
use Webkul\Booking_Product\Repositories\Booking_Product_Table_Slot_Repository;
use Webkul\Booking_Product\Repositories\Booking_Repository;
use Webkul\Checkout\Models\Cart_Item;
use Webkul\Product\Data_Types\Cart_Item_Validation_Result;
class Booking
{
    /**
     * Summary of typeRepositories
     *
     * @var array
     */
    protected $type_repositories = [];
    /**
     * Summary of typeHelpers
     *
     * @var array
     */
    protected $type_helpers = ['default' => Default_Slot::class, 'appointment' => Appointment_Slot::class, 'event' => Event_Ticket::class, 'rental' => Rental_Slot::class, 'table' => Table_Slot::class];
    /**
     * Summary of daysOfWeek
     *
     * @var array
     */
    protected $days_of_week = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];
    /**
     * Create a new helper instance.
     *
     * @return void
     */
    public function __construct(protected Booking_Product_Repository $booking_product_repository, protected Booking_Repository $booking_repository, protected Booking_Product_Default_Slot_Repository $booking_product_default_slot_repository, protected Booking_Product_Appointment_Slot_Repository $booking_product_appointment_slot_repository, protected Booking_Product_Event_Ticket_Repository $booking_product_event_ticket_repository, protected Booking_Product_Rental_Slot_Repository $booking_product_rental_slot_repository, protected Booking_Product_Table_Slot_Repository $booking_product_table_slot_repository)
    {
        $this->type_repositories = ['default' => $this->booking_product_default_slot_repository, 'event' => $this->booking_product_event_ticket_repository, 'appointment' => $this->booking_product_appointment_slot_repository, 'table' => $this->booking_product_table_slot_repository, 'rental' => $this->booking_product_rental_slot_repository];
    }
    /**
     * Returns the booking type helper instance.
     *
     * @return mixed
     */
    public function get_type_helper(string $type)
    {
        return $this->type_helpers[$type];
    }
    /**
     * Returns the booking information.
     */
    public function get_week_slot_durations(Booking_Product $booking_product): array
    {
        $slots_by_days = [];
        $booking_product_slot = $this->type_repositories[$booking_product->type]->find_one_by_field('booking_product_id', $booking_product->id);
        $available_days = $this->get_available_week_days($booking_product);
        foreach ($this->days_of_week as $index => $is_open) {
            $slots = [];
            if ($is_open) {
                $slots = $booking_product_slot->same_slot_all_days ? $booking_product_slot->slots ?? [] : $booking_product_slot->slots[$index] ?? [];
            }
            $slots_by_days[] = ['name' => trans($this->days_of_week[$index]), 'slots' => isset($available_days[$index]) ? $this->convert24To12Hours($slots) : []];
        }
        return $slots_by_days;
    }
    /**
     * Returns html of slots for a current day.
     */
    public function get_today_slots_html(Booking_Product $booking_product)
    {
        $slots = [];
        $week_slots = $this->get_week_slot_durations($booking_product);
        foreach ($week_slots[Carbon::now()->format('w')]['slots'] as $slot) {
            $slots[] = $slot['from'] . ' - ' . $slot['to'];
        }
        return count($slots) ? implode(' | ', $slots) : '<span class="text-danger">' . trans('shop::app.products.booking.closed') . '</span>';
    }
    /**
     * Sort days.
     */
    public function sort_days_of_week(array $days): array
    {
        $days_aux = array_intersect($this->days_of_week, $days);
        usort($days_aux, function ($a, $b) {
            return array_search($a, $this->days_of_week) - array_search($b, $this->days_of_week);
        });
        return $days_aux;
    }
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
     * Returns is item have quantity.
     *
     * @param  \Webkul\Checkout\Contracts\CartItem|array  $cartItem
     */
    public function is_item_have_quantity($cart_item)
    {
        $booking_product = $this->booking_product_repository->find_one_by_field('product_id', $cart_item['product_id']);
        if ($booking_product->qty - $this->get_booked_quantity($cart_item) < $cart_item['quantity'] || $this->is_slot_expired($cart_item)) {
            return false;
        }
        return true;
    }
    /**
     * Return slot if it is available.
     */
    public function is_slot_available(array $cart_products): bool
    {
        foreach ($cart_products as $cart_product) {
            if (!$this->is_item_have_quantity($cart_product)) {
                return false;
            }
        }
        return true;
    }
    /**
     * Returns slots that are going to expire.
     *
     * @param  \Webkul\Checkout\Contracts\CartItem|array  $cartItem
     */
    public function is_slot_expired($cart_item): bool
    {
        $booking_product = $this->booking_product_repository->find_one_by_field('product_id', $cart_item['product_id']);
        $type_helper = app($this->get_type_helper($booking_product->type));
        $slots = $type_helper->get_slots_by_date($booking_product, $cart_item['additional']['booking']['date']);
        $slot_exists = collect($slots)->contains(function ($slot) use ($cart_item) {
            return $slot['timestamp'] == $cart_item['additional']['booking']['slot'];
        });
        return !$slot_exists;
    }
    /**
     * Returns get booked quantity.
     *
     * @param  array  $data
     */
    public function get_booked_quantity($data): int
    {
        $timestamps = explode('-', $data['additional']['booking']['slot']);
        $result = $this->booking_repository->get_model()->left_join('order_items', 'bookings.order_item_id', '=', 'order_items.id')->add_select(DB::raw('SUM(qty_ordered - qty_canceled - qty_refunded) as total_qty_booked'))->where('bookings.product_id', $data['product_id'])->where('bookings.from', $timestamps[0])->where('bookings.to', $timestamps[1])->first();
        return $result->total_qty_booked ?? 0;
    }
    /**
     * Returns additional cart item information.
     */
    public function get_cart_item_options(array $data): array
    {
        $booking_product = $this->booking_product_repository->find_one_by_field('product_id', $data['product_id']);
        if ($booking_product) {
            $data['attributes'] = $this->get_booking_attributes($booking_product, $data);
        }
        return $data;
    }
    /**
     * Get booking attributes based on booking type.
     */
    protected function get_booking_attributes($booking_product, $data): array
    {
        switch ($booking_product->type) {
            case 'event':
                return $this->get_event_attributes($booking_product, $data);
            case 'rental':
                return $this->get_rental_attributes($booking_product, $data);
            case 'table':
                return $this->get_table_attributes($data);
            default:
                return $this->get_default_attributes($data);
        }
    }
    /**
     * Returns the available week days.
     */
    private function get_available_week_days(Booking_Product $booking_product)
    {
        if ($booking_product->available_every_week ?? true) {
            return $this->days_of_week;
        }
        $available_from = $booking_product->available_from ? Carbon::create_from_time_string($booking_product->available_from) : Carbon::now()->start_of_day();
        $available_to = $booking_product->available_to ? Carbon::create_from_time_string($booking_product->available_to) : Carbon::create_from_time_string('2080-01-01 00:00:00');
        $days = collect(range(0, 6))->map(function ($i) use ($available_from, $available_to) {
            $date = Carbon::now()->add_days($i);
            return $date >= $available_from && $date <= $available_to ? $date->format('l') : null;
        })->filter()->values()->to_array();
        return $this->sort_days_of_week($days);
    }
    /**
     * Add booking additional prices to cart item.
     */
    public function add_additional_prices(array $products): array
    {
        return $products;
    }
    /**
     * Validate cart item product price.
     */
    public function validate_cart_item(Cart_Item $item): Cart_Item_Validation_Result
    {
        $result = new Cart_Item_Validation_Result();
        if ($this->is_cart_item_inactive($item)) {
            $result->item_is_inactive();
            return $result;
        }
        $this->update_cart_item_price($item);
        return $result;
    }
    /**
     * Returns true if the cart item is inactive.
     *
     * @param  \Webkul\Checkout\Contracts\CartItem|array  $cartItem
     */
    public function is_cart_item_inactive($item): bool
    {
        return !$item->product->status;
    }
    /**
     * Slots Calculation for all types of booking products.
     */
    public function slots_calculation(object $booking_product, object $requested_date, object $booking_product_slot): array
    {
        $slots = [];
        if ($booking_product->type == 'default') {
            [$available_from, $available_to, $time_durations] = $this->get_default_slot_details($booking_product, $booking_product_slot, $requested_date);
            if (!count($time_durations)) {
                return [];
            }
            if (isset($time_durations[0]['status']) && $time_durations[0]['status'] == 0) {
                $start = $requested_date->copy()->start_of_day();
                $end = $requested_date->copy()->end_of_day();
                $current_time = clone $start;
                while ($current_time < $end) {
                    $to = (clone $current_time)->add_minutes($booking_product_slot->duration);
                    $is_closed = false;
                    foreach ($time_durations as $slot) {
                        if (!($slot['status'] ?? 1)) {
                            $closed_from = Carbon::parse($requested_date->format('Y-m-d') . ' ' . $slot['from']);
                            $closed_to = Carbon::parse($requested_date->format('Y-m-d') . ' ' . $slot['to']);
                            if ($current_time < $closed_to && $to > $closed_from) {
                                $is_closed = true;
                                break;
                            }
                        }
                    }
                    if (!$is_closed && Carbon::now() <= $current_time) {
                        $slots[] = ['from' => $current_time->format('h:i A'), 'to' => $to->format('h:i A'), 'timestamp' => $current_time->get_timestamp() . '-' . $to->get_timestamp(), 'qty' => $time_duration['qty'] ?? 1];
                    }
                    $current_time->add_minutes($booking_product_slot->duration + $booking_product_slot->break_time);
                }
                return $slots;
            }
        } else {
            [$available_from, $available_to, $time_durations] = $this->get_slot_details($booking_product, $booking_product_slot, $requested_date);
            if ($requested_date < $available_from || $requested_date > $available_to) {
                return [];
            }
        }
        foreach ($time_durations as $index => $time_duration) {
            $from_chunks = explode(':', $time_duration['from']);
            $to_chunks = explode(':', $time_duration['to']);
            $start_day_time = Carbon::create_from_time_string($requested_date->format('Y-m-d') . ' 00:00:00')->add_minutes($from_chunks[0] * 60 + $from_chunks[1]);
            $temp_start_day_time = clone $start_day_time;
            $end_day_time = Carbon::create_from_time_string($requested_date->format('Y-m-d') . ' 00:00:00')->add_minutes($to_chunks[0] * 60 + $to_chunks[1]);
            $is_first_iteration = true;
            while (1) {
                $from = clone $temp_start_day_time;
                if ($booking_product->type == 'rental') {
                    $temp_start_day_time->add_minutes(60);
                } else {
                    $temp_start_day_time->add_minutes($booking_product_slot->duration);
                    if ($is_first_iteration) {
                        $is_first_iteration = false;
                    } else {
                        $from->modify('+' . $booking_product_slot->break_time . ' minutes');
                        $temp_start_day_time->modify('+' . $booking_product_slot->break_time . ' minutes');
                    }
                }
                $to = clone $temp_start_day_time;
                if ($start_day_time <= $from && $from <= $available_to && $available_to >= $to && $to >= $start_day_time && $start_day_time <= $from && $from <= $end_day_time && $end_day_time >= $to && $to >= $start_day_time) {
                    if ($qty = $time_duration['qty'] ?? 1 && Carbon::now() <= $from) {
                        if ($booking_product->type == 'rental') {
                            if (!isset($slots[$index])) {
                                $slots[$index]['time'] = $start_day_time->format('h:i A') . ' - ' . $end_day_time->format('h:i A');
                            }
                            $slots[$index]['slots'][] = ['from' => $from->format('h:i A'), 'to' => $to->format('h:i A'), 'from_timestamp' => $from->get_timestamp(), 'to_timestamp' => $to->get_timestamp(), 'qty' => $qty];
                        } else {
                            $slots[] = ['from' => $from->format('h:i A'), 'to' => $to->format('h:i A'), 'timestamp' => $from->get_timestamp() . '-' . $to->get_timestamp(), 'qty' => $qty];
                            usort($slots, fn($first, $second) => strtotime($first['from']) <=> strtotime($second['from']));
                        }
                    }
                } else {
                    break;
                }
            }
        }
        return $slots;
    }
    /**
     * Convert time from 24 to 12 hour format
     */
    private function convert24To12Hours(array $slots): array
    {
        return array_map(function ($slot) {
            return ['from' => Carbon::create_from_time_string($slot['from'])->format('h:i a'), 'to' => Carbon::create_from_time_string($slot['to'])->format('h:i a')];
        }, $slots);
    }
    /**
     * Update the cart item price.
     */
    private function update_cart_item_price(Cart_Item $item): void
    {
        $price = $item->product->get_type_instance()->get_final_price($item->quantity);
        if ($price != $item->base_price) {
            $item->base_price = $price;
            $item->price = core()->convert_price($price);
            $item->base_total = $price * $item->quantity;
            $item->total = core()->convert_price($price * $item->quantity);
            $item->save();
        }
    }
    /**
     * Get default slot details.
     */
    private function get_default_slot_details($booking_product, $booking_product_slot, $requested_date): array
    {
        $available_from = $booking_product_slot->available_from ? Carbon::create_from_time_string($booking_product_slot->available_from) : Carbon::now()->start_of_day();
        $available_to = $booking_product_slot->available_to ? Carbon::create_from_time_string($booking_product_slot->available_to) : Carbon::create_from_time_string('2080-01-01 00:00:00');
        $time_durations = $booking_product_slot->same_slot_all_days ? $booking_product_slot->slots : $booking_product_slot->slots[$requested_date->format('w')] ?? [];
        return [$available_from, $available_to, $time_durations];
    }
    /**
     * Get slot details based on booking type.
     */
    private function get_slot_details($booking_product, $booking_product_slot, $requested_date): array
    {
        if ($booking_product->type == 'default') {
            return $this->get_default_slot_details($booking_product, $booking_product_slot, $requested_date);
        }
        $available_from = !$booking_product->available_every_week && $booking_product->available_from ? Carbon::create_from_time_string($booking_product->available_from) : Carbon::now()->copy()->start_of_day();
        $available_to = !$booking_product->available_every_week && $booking_product->available_from ? Carbon::create_from_time_string($booking_product->available_to) : Carbon::create_from_time_string('2080-01-01 00:00:00');
        $time_durations = $booking_product_slot->same_slot_all_days ? $booking_product_slot->slots : $booking_product_slot->slots[$requested_date->format('w')] ?? [];
        return [$available_from, $available_to, $time_durations];
    }
    /**
     * Get event booking attributes.
     */
    private function get_event_attributes($booking_product, $data): array
    {
        $ticket = $booking_product->event_tickets()->find($data['booking']['ticket_id']);
        return [['attribute_name' => trans('shop::app.products.booking.cart.event-ticket'), 'option_id' => 0, 'option_label' => $ticket->name], ['attribute_name' => trans('shop::app.products.booking.cart.event-from'), 'option_id' => 0, 'option_label' => Carbon::create_from_time_string($booking_product->available_from)->format('d F, Y')], ['attribute_name' => trans('shop::app.products.booking.cart.event-till'), 'option_id' => 0, 'option_label' => Carbon::create_from_time_string($booking_product->available_to)->format('d F, Y')]];
    }
    /**
     * Get rental booking attributes.
     */
    private function get_rental_attributes($booking_product, $data): array
    {
        $renting_type = $data['booking']['renting_type'] ?? $booking_product->rental_slot->renting_type;
        if ($renting_type == 'daily') {
            $from = Carbon::create_from_time_string($data['booking']['date_from'] . ' 00:00:01')->format('d F, Y');
            $to = Carbon::create_from_time_string($data['booking']['date_to'] . ' 23:59:59')->format('d F, Y');
        } else {
            $from = Carbon::create_from_timestamp($data['booking']['slot']['from'])->format('d F, Y h:i A');
            $to = Carbon::create_from_timestamp($data['booking']['slot']['to'])->format('d F, Y h:i A');
        }
        return [['attribute_name' => trans('shop::app.products.booking.cart.rent-type'), 'option_id' => 0, 'option_label' => trans('shop::app.products.booking.cart.' . $renting_type)], ['attribute_name' => trans('shop::app.products.booking.cart.rent-from'), 'option_id' => 0, 'option_label' => $from], ['attribute_name' => trans('shop::app.products.booking.cart.rent-till'), 'option_id' => 0, 'option_label' => $to]];
    }
    /**
     * Get table booking attributes.
     */
    private function get_table_attributes($data): array
    {
        $timestamps = explode('-', $data['booking']['slot']);
        $attributes = [['attribute_name' => trans('shop::app.products.booking.cart.booking-from'), 'option_id' => 0, 'option_label' => Carbon::create_from_timestamp($timestamps[0])->iso_format('Do MMM, YYYY h:mm A')], ['attribute_name' => trans('shop::app.products.booking.cart.booking-till'), 'option_id' => 0, 'option_label' => Carbon::create_from_timestamp($timestamps[1])->iso_format('Do MMM, YYYY h:mm A')]];
        if ($data['booking']['note'] !== '') {
            $attributes[] = ['attribute_name' => trans('shop::app.products.booking.cart.special-note'), 'option_id' => 0, 'option_label' => $data['booking']['note']];
        }
        return $attributes;
    }
    /**
     * Get default booking attributes.
     */
    private function get_default_attributes($data): array
    {
        $timestamps = explode('-', $data['booking']['slot']);
        return [['attribute_name' => trans('shop::app.products.booking.cart.booking-from'), 'option_id' => 0, 'option_label' => Carbon::create_from_timestamp($timestamps[0])->format('d F, Y h:i A')], ['attribute_name' => trans('shop::app.products.booking.cart.booking-till'), 'option_id' => 0, 'option_label' => Carbon::create_from_timestamp($timestamps[1])->format('d F, Y h:i A')]];
    }
}