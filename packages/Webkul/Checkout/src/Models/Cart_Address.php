<?php

declare (strict_types=1);
namespace Webkul\Checkout\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\Has_Factory;
use Illuminate\Database\Eloquent\Relations\Belongs_To;
use Illuminate\Database\Eloquent\Relations\Has_Many;
use Webkul\Checkout\Contracts\Cart_Address as CartAddressContract;
use Webkul\Checkout\Database\Factories\Cart_Address_Factory;
use Webkul\Core\Models\Address;
/**
 * Class CartAddress
 *
 *
 * @property int $cart_id
 * @property Cart $cart
 */
class Cart_Address extends Address implements Cart_Address_Contract
{
    use Has_Factory;
    /**
     * Define the address type shipping.
     */
    public const ADDRESS_TYPE_SHIPPING = 'cart_shipping';
    /**
     * Define the address type billing.
     */
    public const ADDRESS_TYPE_BILLING = 'cart_billing';
    /**
     * @var array default values
     */
    protected $attributes = ['address_type' => self::ADDRESS_TYPE_BILLING];
    /**
     * The "booted" method of the model.
     */
    protected static function boot(): void
    {
        static::add_global_scope('address_type', static function (Builder $builder) {
            $builder->where_in('address_type', [self::ADDRESS_TYPE_BILLING, self::ADDRESS_TYPE_SHIPPING]);
        });
        parent::boot();
    }
    /**
     * Get the shipping rates for the cart address.
     */
    public function shipping_rates(): Has_Many
    {
        return $this->has_many(Cart_Shipping_Rate_Proxy::model_class());
    }
    /**
     * Get the cart record associated with the address.
     */
    public function cart(): Belongs_To
    {
        return $this->belongs_to(Cart_Proxy::model_class());
    }
    /**
     * Create a new factory instance for the model
     */
    protected static function new_factory(): Factory
    {
        return Cart_Address_Factory::new();
    }
}