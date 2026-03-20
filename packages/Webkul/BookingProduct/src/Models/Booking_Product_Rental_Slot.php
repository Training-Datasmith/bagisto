<?php

declare (strict_types=1);
namespace Webkul\Booking_Product\Models;

use Illuminate\Database\Eloquent\Model;
use Webkul\Booking_Product\Contracts\Booking_Product_Rental_Slot as BookingProductRentalSlotContract;
class Booking_Product_Rental_Slot extends Model implements Booking_Product_Rental_Slot_Contract
{
    /**
     * Indicates if the model should be timestamped.
     */
    public $timestamps = false;
    /**
     * The attributes that should be cast.
     */
    protected $casts = ['slots' => 'array'];
    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = ['renting_type', 'daily_price', 'hourly_price', 'same_slot_all_days', 'slots', 'booking_product_id'];
}