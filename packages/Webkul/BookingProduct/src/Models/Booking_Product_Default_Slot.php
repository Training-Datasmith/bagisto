<?php

declare (strict_types=1);
namespace Webkul\Booking_Product\Models;

use Illuminate\Database\Eloquent\Model;
use Webkul\Booking_Product\Contracts\Booking_Product_Default_Slot as BookingProductDefaultSlotContract;
class Booking_Product_Default_Slot extends Model implements Booking_Product_Default_Slot_Contract
{
    /**
     * The table associated with the model.
     */
    protected $table = 'booking_product_default_slots';
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
    protected $fillable = ['booking_type', 'duration', 'break_time', 'slots', 'booking_product_id'];
    /**
     * Get the product that owns the attribute value.
     */
    public function booking_product()
    {
        return $this->belongs_to(Booking_Product_Proxy::model_class());
    }
}