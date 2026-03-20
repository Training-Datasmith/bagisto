<?php

declare (strict_types=1);
namespace Webkul\Booking_Product\Models;

use Illuminate\Database\Eloquent\Model;
use Webkul\Booking_Product\Contracts\Booking_Product_Table_Slot as BookingProductTableSlotContract;
class Booking_Product_Table_Slot extends Model implements Booking_Product_Table_Slot_Contract
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
    protected $fillable = ['price_type', 'guest_limit', 'duration', 'break_time', 'prevent_scheduling_before', 'same_slot_all_days', 'slots', 'booking_product_id'];
}