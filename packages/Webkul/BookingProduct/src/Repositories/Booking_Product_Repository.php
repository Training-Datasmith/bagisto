<?php

declare (strict_types=1);
namespace Webkul\Booking_Product\Repositories;

use Carbon\Carbon;
use Illuminate\Container\Container;
use Webkul\Booking_Product\Contracts\Booking_Product;
use Webkul\Core\Eloquent\Repository;
class Booking_Product_Repository extends Repository
{
    /**
     * @return array
     */
    protected $type_repositories = [];
    /**
     * Create a new repository instance.
     *
     * @return void
     */
    public function __construct(protected Booking_Product_Default_Slot_Repository $booking_product_default_slot_repository, protected Booking_Product_Appointment_Slot_Repository $booking_product_appointment_slot_repository, protected Booking_Product_Event_Ticket_Repository $booking_product_event_ticket_repository, protected Booking_Product_Rental_Slot_Repository $booking_product_rental_slot_repository, protected Booking_Product_Table_Slot_Repository $booking_product_table_slot_repository, Container $container)
    {
        parent::__construct($container);
        $this->type_repositories = ['default' => $booking_product_default_slot_repository, 'appointment' => $booking_product_appointment_slot_repository, 'event' => $booking_product_event_ticket_repository, 'rental' => $booking_product_rental_slot_repository, 'table' => $booking_product_table_slot_repository];
    }
    /**
     * Specify Model class name
     */
    public function model(): string
    {
        return Booking_Product::class;
    }
    /**
     * @return BookingProduct
     */
    public function create(array $data)
    {
        if (isset($data['slots'])) {
            $data['slots'] = $this->validate_slots($data);
        }
        $booking_product = parent::create($data);
        if ($booking_product->type == 'event') {
            $this->type_repositories[$data['type']]->save_event_tickets($data, $booking_product);
        } else {
            $this->type_repositories[$data['type']]->create(array_merge($data, ['booking_product_id' => $booking_product->id]));
        }
        return $booking_product;
    }
    /**
     * Update method.
     *
     * @param  int  $id
     * @param  string  $attribute
     * @return BookingProduct|void
     */
    public function update(array $data, $id, $attribute = 'id')
    {
        if (isset($data['slots'])) {
            $data['slots'] = $this->skip_over_lapping_slots($data['slots']);
        }
        $booking_product = parent::update($data, $id, $attribute);
        foreach ($this->type_repositories as $type => $repository) {
            if ($type == $data['type']) {
                continue;
            }
            $repository->delete_where(['booking_product_id' => $id]);
        }
        if ($booking_product->type == 'event') {
            $this->type_repositories[$data['type']]->save_event_tickets($data, $booking_product);
        } else {
            $booking_product_type_slot = $this->type_repositories[$data['type']]->find_one_by_field('booking_product_id', $id);
            if (isset($data['slots'])) {
                $data['slots'] = $this->format_slots($data);
                $data['slots'] = $this->validate_slots($data);
            } else {
                $data['slots'] = $this->add_slots($data);
            }
            if (!$booking_product_type_slot) {
                $this->type_repositories[$data['type']]->create(array_merge($data, ['booking_product_id' => $id]));
            } else {
                $this->type_repositories[$data['type']]->update($data, $booking_product_type_slot->id);
            }
        }
    }
    /**
     * Format Slots data.
     */
    public function format_slots(array $data): array
    {
        if (isset($data['same_slot_all_days']) && !$data['same_slot_all_days']) {
            for ($i = 0; $i < 7; $i++) {
                if (!isset($data['slots'][$i])) {
                    $data['slots'][$i] = [];
                } else {
                    $count = 0;
                    $slots = [];
                    foreach ($data['slots'][$i] as $slot) {
                        $slots[] = array_merge($slot, ['id' => $i . '_slot_' . $count]);
                        $count++;
                    }
                    $data['slots'][$i] = $slots;
                }
            }
            ksort($data['slots']);
        }
        return $data['slots'];
    }
    /**
     * Add blank array where slots key in available.
     */
    public function add_slots(array $data): array
    {
        if (isset($data['same_slot_all_days']) && !$data['same_slot_all_days']) {
            return [[], [], [], [], [], [], []];
        } else {
            return $data['type'] == 'default' && $data['booking_type'] == 'many' ? [[], [], [], [], [], [], []] : [];
        }
    }
    /**
     * Validate Slots data.
     */
    public function validate_slots(array $data): array
    {
        if (!isset($data['same_slot_all_days'])) {
            return $data['slots'];
        }
        if (!$data['same_slot_all_days']) {
            foreach ($data['slots'] as $day => $slots) {
                $data['slots'][$day] = $this->skip_over_lapping_slots($slots);
            }
        } else {
            $data['slots'] = $this->skip_over_lapping_slots($data['slots']);
        }
        return $data['slots'];
    }
    /**
     * Filters out overlapping time slots from a given array.
     * Supports both flat arrays and nested arrays of time slots.
     */
    public function skip_over_lapping_slots(array $slots): array
    {
        $filtered_slots = [];
        foreach ($slots as $key => $slot) {
            if (isset($slot[0]) && is_array($slot[0])) {
                $filtered_slots[$key] = $this->process_slots($slot);
            } else {
                $filtered_slots = array_merge($filtered_slots, $this->process_slots([$slot]));
            }
        }
        return $filtered_slots;
    }
    /**
     * Processes a list of time slots to remove overlapping intervals.
     */
    private function process_slots(array $slots): array
    {
        $temp_slots = [];
        $valid_slots = [];
        foreach ($slots as $key => $time_interval) {
            $from = Carbon::create_from_time_string($time_interval['from'])->get_timestamp();
            $to = Carbon::create_from_time_string($time_interval['to'])->get_timestamp();
            if ($from > $to) {
                continue;
            }
            $is_over_lapping = false;
            foreach ($temp_slots as $slot) {
                if ($slot['from'] <= $from && $slot['to'] >= $from || $slot['from'] <= $to && $slot['to'] >= $to) {
                    $is_over_lapping = true;
                    break;
                }
            }
            if (!$is_over_lapping) {
                $temp_slots[] = ['from' => $from, 'to' => $to];
                $valid_slots[] = $time_interval;
            }
        }
        return $valid_slots;
    }
}