<?php

declare (strict_types=1);
namespace Webkul\Booking_Product\Models;

use Webkul\Booking_Product\Contracts\Booking_Product_Event_Ticket as BookingProductEventTicketContract;
use Webkul\Core\Eloquent\Translatable_Model;
class Booking_Product_Event_Ticket extends Translatable_Model implements Booking_Product_Event_Ticket_Contract
{
    /**
     * Indicates if the model should be timestamped.
     */
    public $timestamps = false;
    /**
     * Summary of translatedAttributes
     */
    public $translated_attributes = ['name', 'description'];
    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = ['price', 'qty', 'special_price', 'special_price_from', 'special_price_to', 'booking_product_id'];
}