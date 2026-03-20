<?php

declare (strict_types=1);
namespace Webkul\Cart_Rule\Listeners;

use Webkul\Cart_Rule\Repositories\Cart_Rule_Coupon_Repository;
use Webkul\Cart_Rule\Repositories\Cart_Rule_Coupon_Usage_Repository;
use Webkul\Cart_Rule\Repositories\Cart_Rule_Customer_Repository;
use Webkul\Cart_Rule\Repositories\Cart_Rule_Repository;
class Order
{
    /**
     * Create a new listener instance.
     *
     * @return void
     */
    public function __construct(protected Cart_Rule_Repository $cart_rule_repository, protected Cart_Rule_Customer_Repository $cart_rule_customer_repository, protected Cart_Rule_Coupon_Repository $cart_rule_coupon_repository, protected Cart_Rule_Coupon_Usage_Repository $cart_rule_coupon_usage_repository)
    {
    }
    /**
     * Save cart rule and cart rule coupon properties after place order
     *
     * @param  \Webkul\Sales\Contracts\Order  $order
     * @return void
     */
    public function manage_cart_rule($order)
    {
        if (!$order->discount_amount) {
            return;
        }
        $cart_rule_ids = explode(',', $order->applied_cart_rule_ids);
        $cart_rule_ids = array_unique($cart_rule_ids);
        foreach ($cart_rule_ids as $rule_id) {
            $rule = $this->cart_rule_repository->find($rule_id);
            if (!$rule) {
                continue;
            }
            $rule->update(['times_used' => $rule->times_used + 1]);
            if (!$order->customer_id) {
                continue;
            }
            $rule_customer = $this->cart_rule_customer_repository->find_one_where(['customer_id' => $order->customer_id, 'cart_rule_id' => $rule_id]);
            if ($rule_customer) {
                $this->cart_rule_customer_repository->update(['times_used' => $rule_customer->times_used + 1], $rule_customer->id);
            } else {
                $this->cart_rule_customer_repository->create(['customer_id' => $order->customer_id, 'cart_rule_id' => $rule_id, 'times_used' => 1]);
            }
        }
        if (!$order->coupon_code) {
            return;
        }
        $coupon = $this->cart_rule_coupon_repository->find_one_by_field('code', $order->coupon_code);
        if ($coupon) {
            $this->cart_rule_coupon_repository->update(['times_used' => $coupon->times_used + 1], $coupon->id);
            if ($order->customer_id) {
                $coupon_usage = $this->cart_rule_coupon_usage_repository->find_one_where(['customer_id' => $order->customer_id, 'cart_rule_coupon_id' => $coupon->id]);
                if ($coupon_usage) {
                    $this->cart_rule_coupon_usage_repository->update(['times_used' => $coupon_usage->times_used + 1], $coupon_usage->id);
                } else {
                    $this->cart_rule_coupon_usage_repository->create(['customer_id' => $order->customer_id, 'cart_rule_coupon_id' => $coupon->id, 'times_used' => 1]);
                }
            }
        }
    }
}