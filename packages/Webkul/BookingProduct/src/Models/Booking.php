<?php

declare (strict_types=1);
namespace Webkul\Booking_Product\Models;

use Illuminate\Database\Eloquent\Model;
use Webkul\Booking_Product\Contracts\Booking as BookingContract;
use Webkul\Sales\Models\Order_Item_Proxy;
use Webkul\Sales\Models\Order_Proxy;
class Booking extends Model implements Booking_Contract
{
    /**
     * Indicates if the model should be timestamped.
     */
    public $timestamps = false;
    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = ['qty', 'from', 'to', 'order_item_id', 'booking_product_event_ticket_id', 'product_id', 'order_id'];
    /**
     * Get the order record associated with the order item.
     */
    public function order()
    {
        return $this->belongs_to(Order_Proxy::model_class());
    }
    /**
     * Get the child item record associated with the order item.
     */
    public function order_item()
    {
        return $this->has_one(Order_Item_Proxy::model_class());
    }
}