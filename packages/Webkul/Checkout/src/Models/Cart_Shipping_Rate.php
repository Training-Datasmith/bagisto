<?php

declare (strict_types=1);
namespace Webkul\Checkout\Models;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\Has_Factory;
use Illuminate\Database\Eloquent\Model;
use Webkul\Checkout\Contracts\Cart_Shipping_Rate as CartShippingRateContract;
use Webkul\Checkout\Database\Factories\Cart_Shipping_Rate_Factory;
class Cart_Shipping_Rate extends Model implements Cart_Shipping_Rate_Contract
{
    use Has_Factory;
    /**
     * Fillable property of the model.
     *
     * @var array
     */
    protected $fillable = ['carrier', 'carrier_title', 'method', 'method_title', 'method_description', 'price', 'base_price', 'discount_amount', 'base_discount_amount', 'tax_percent', 'tax_amount', 'base_tax_amount', 'price_incl_tax', 'base_price_incl_tax', 'applied_tax_rate'];
    /**
     * Get the post that owns the comment.
     */
    public function shipping_address()
    {
        return $this->belongs_to(Cart_Address_Proxy::model_class(), 'cart_address_id')->where('address_type', Cart_Address::ADDRESS_TYPE_SHIPPING);
    }
    /**
     * Create a new factory instance for the model
     */
    protected static function new_factory(): Factory
    {
        return Cart_Shipping_Rate_Factory::new();
    }
}