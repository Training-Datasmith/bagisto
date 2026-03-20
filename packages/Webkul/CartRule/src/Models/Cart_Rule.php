<?php

declare (strict_types=1);
namespace Webkul\Cart_Rule\Models;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\Has_Factory;
use Illuminate\Database\Eloquent\Model;
use Webkul\Cart_Rule\Contracts\Cart_Rule as CartRuleContract;
use Webkul\Core\Database\Factories\Cart_Rule_Factory;
use Webkul\Core\Models\Channel_Proxy;
use Webkul\Customer\Models\Customer_Group_Proxy;
class Cart_Rule extends Model implements Cart_Rule_Contract
{
    use Has_Factory;
    /**
     * Add fillable property to the model.
     *
     * @var array
     */
    protected $fillable = ['name', 'description', 'starts_from', 'ends_till', 'status', 'coupon_type', 'use_auto_generation', 'usage_per_customer', 'uses_per_coupon', 'times_used', 'condition_type', 'conditions', 'actions', 'end_other_rules', 'uses_attribute_conditions', 'action_type', 'discount_amount', 'discount_quantity', 'discount_step', 'apply_to_shipping', 'free_shipping', 'sort_order'];
    /**
     * Cast the conditions to the array.
     *
     * @var array
     */
    protected $casts = ['conditions' => 'array'];
    /**
     * Get the channels that owns the cart rule.
     */
    public function cart_rule_channels(): \Illuminate\Database\Eloquent\Relations\Belongs_To_Many
    {
        return $this->belongs_to_many(Channel_Proxy::model_class(), 'cart_rule_channels');
    }
    /**
     * @deprecated laravel standard should be used
     */
    public function channels(): \Illuminate\Database\Eloquent\Relations\Belongs_To_Many
    {
        return $this->cart_rule_channels();
    }
    /**
     * Get the customer groups that owns the cart rule.
     */
    public function cart_rule_customer_groups(): \Illuminate\Database\Eloquent\Relations\Belongs_To_Many
    {
        return $this->belongs_to_many(Customer_Group_Proxy::model_class(), 'cart_rule_customer_groups');
    }
    /**
     * @deprecated laravel standard should be used
     */
    public function customer_groups(): \Illuminate\Database\Eloquent\Relations\Belongs_To_Many
    {
        return $this->cart_rule_customer_groups();
    }
    /**
     * Get the coupons that owns the cart rule.
     */
    public function cart_rule_coupon(): \Illuminate\Database\Eloquent\Relations\Has_One
    {
        return $this->has_one(Cart_Rule_Coupon_Proxy::model_class());
    }
    /**
     * @deprecated laravel standard should be used
     */
    public function coupons(): \Illuminate\Database\Eloquent\Relations\Has_One
    {
        return $this->cart_rule_coupon();
    }
    /**
     * Get primary coupon code for cart rule.
     */
    public function coupon_code(): \Illuminate\Database\Eloquent\Relations\Has_One
    {
        return $this->cart_rule_coupon()->where('is_primary', 1);
    }
    /**
     * Get primary coupon code for cart rule.
     *
     * @return string|void
     */
    public function get_coupon_code_attribute()
    {
        $coupon = $this->coupon_code()->first();
        if (!$coupon) {
            return;
        }
        return $coupon->code;
    }
    /**
     * Create a new factory instance for the model.
     */
    protected static function new_factory(): Factory
    {
        return Cart_Rule_Factory::new();
    }
}