<?php

declare (strict_types=1);
namespace Webkul\Booking_Product\Repositories;

use Illuminate\Support\Facades\Event;
use Illuminate\Support\Str;
use Webkul\Booking_Product\Contracts\Booking_Product;
use Webkul\Booking_Product\Contracts\Booking_Product_Event_Ticket;
use Webkul\Core\Eloquent\Repository;
class Booking_Product_Event_Ticket_Repository extends Repository
{
    /**
     * Specify Model class name
     */
    public function model(): string
    {
        return Booking_Product_Event_Ticket::class;
    }
    /**
     * Summary of save Event Tickets.
     */
    public function save_event_tickets(array $data, Booking_Product $booking_product): void
    {
        Event::dispatch('booking_product.booking.event-ticket.save.before', ['data' => $data, 'bookingProduct' => $booking_product]);
        $previous_ticket_ids = $booking_product->event_tickets()->pluck('id')->to_array();
        $saved_tickets = [];
        if (!empty($data['tickets'])) {
            foreach ($data['tickets'] as $ticket_id => &$ticket_inputs) {
                $this->sanitize_input('special_price', $ticket_inputs);
                $this->sanitize_input('special_price_from', $ticket_inputs);
                $this->sanitize_input('special_price_to', $ticket_inputs);
                if (Str::contains($ticket_id, 'ticket_')) {
                    $ticket = $this->create(array_merge(['booking_product_id' => $booking_product->id], $ticket_inputs));
                } else {
                    if (($index = array_search($ticket_id, $previous_ticket_ids)) !== false) {
                        unset($previous_ticket_ids[$index]);
                    }
                    $ticket = $this->update($ticket_inputs, $ticket_id);
                }
                $saved_tickets[$ticket_id] = ['ticket' => $ticket, 'ticketInputs' => $ticket_inputs];
            }
            Event::dispatch('booking_product.booking.event-ticket.save.after', ['tickets' => $saved_tickets]);
        }
        if (!empty($previous_ticket_ids)) {
            $this->destroy($previous_ticket_ids);
        }
    }
    /**
     * Summary of sanitize Input.
     *
     * @param  string  $fieldName
     * @param  array  $inputs
     */
    private function sanitize_input($field_name, &$inputs)
    {
        $field_value = $inputs[$field_name] ?? null;
        if (!isset($field_value) || empty($field_value) || $field_value === '0.0000' || $field_value === '0000-00-00 00:00:00') {
            $inputs[$field_name] = null;
        }
    }
}