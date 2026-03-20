<?php

declare (strict_types=1);
namespace Webkul\Booking_Product\Models;

use Illuminate\Database\Eloquent\Model;
use Webkul\Booking_Product\Contracts\Booking_Product_Event_Ticket_Translation as BookingProductEventTicketTranslationContract;
class Booking_Product_Event_Ticket_Translation extends Model implements Booking_Product_Event_Ticket_Translation_Contract
{
    /**
     * Indicates if the model should be timestamped.
     */
    public $timestamps = false;
    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = ['name', 'description'];
}