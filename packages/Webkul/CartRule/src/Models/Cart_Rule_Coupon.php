<?php

declare (strict_types=1);
namespace Webkul\Cart_Rule\Models;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\Has_Factory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Belongs_To;
use Webkul\Cart_Rule\Contracts\Cart_Rule_Coupon as CartRuleCouponContract;
use Webkul\Core\Database\Factories\Cart_Rule_Coupon_Factory;
class Cart_Rule_Coupon extends Model implements Cart_Rule_Coupon_Contract
{
    use Has_Factory;
    protected $fillable = ['code', 'usage_limit', 'usage_per_customer', 'times_used', 'type', 'cart_rule_id', 'expired_at', 'is_primary'];
    /**
     * Get the cart rule that owns the cart rule coupon.
     */
    public function cart_rule(): Belongs_To
    {
        return $this->belongs_to(Cart_Rule_Proxy::model_class());
    }
    /**
     * Get the cart rule that owns the cart rule coupon.
     */
    public function coupon_usage()
    {
        return $this->has_many(Cart_Rule_Coupon_Usage_Proxy::model_class());
    }
    /**
     * Create a new factory instance for the model
     */
    protected static function new_factory(): Factory
    {
        return Cart_Rule_Coupon_Factory::new();
    }
}