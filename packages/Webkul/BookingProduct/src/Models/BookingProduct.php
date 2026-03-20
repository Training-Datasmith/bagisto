<?php

declare (strict_types=1);
namespace Webkul\Booking_Product\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Belongs_To;
use Illuminate\Database\Eloquent\Relations\Has_Many;
use Illuminate\Database\Eloquent\Relations\Has_One;
use Webkul\Booking_Product\Contracts\Booking_Product as BookingProductContract;
use Webkul\Product\Models\Product_Proxy;
class Booking_Product extends Model implements Booking_Product_Contract
{
    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = ['location', 'show_location', 'type', 'qty', 'available_every_week', 'available_from', 'available_to', 'product_id'];
    /**
     * The relations to eager load on every query.
     */
    protected $with = ['default_slot', 'appointment_slot', 'event_tickets', 'rental_slot', 'table_slot'];
    /**
     * The attributes that should be cast.
     */
    protected $casts = ['available_from' => 'datetime', 'available_to' => 'datetime'];
    /**
     * The Product Default Booking that belong to the product booking.
     */
    public function default_slot(): Has_One
    {
        return $this->has_one(Booking_Product_Default_Slot_Proxy::model_class());
    }
    /**
     * The Product Appointment Booking that belong to the product booking.
     */
    public function appointment_slot(): Has_One
    {
        return $this->has_one(Booking_Product_Appointment_Slot_Proxy::model_class());
    }
    /**
     * The Product Event Booking that belong to the product booking.
     */
    public function event_tickets(): Has_Many
    {
        return $this->has_many(Booking_Product_Event_Ticket_Proxy::model_class());
    }
    /**
     * The Product Rental Booking that belong to the product booking.
     */
    public function rental_slot(): Has_One
    {
        return $this->has_one(Booking_Product_Rental_Slot_Proxy::model_class());
    }
    /**
     * The Product Table Booking that belong to the product booking.
     */
    public function table_slot(): Has_One
    {
        return $this->has_one(Booking_Product_Table_Slot_Proxy::model_class());
    }
    /**
     * The Product belong to the product booking.
     */
    public function product(): Belongs_To
    {
        return $this->belongs_to(Product_Proxy::model_class());
    }
}