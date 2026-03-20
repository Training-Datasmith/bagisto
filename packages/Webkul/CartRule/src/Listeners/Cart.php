<?php

declare (strict_types=1);
namespace Webkul\Cart_Rule\Listeners;

use Webkul\Cart_Rule\Helpers\Cart_Rule;
class Cart
{
    /**
     * Create a new listener instance.
     *
     * @param  \Webkul\CartRule\Repositories\CartRule  $cartRuleHelper
     * @return void
     */
    public function __construct(protected Cart_Rule $cart_rule_helper)
    {
    }
    /**
     * Apply valid cart rules to cart
     *
     * @param  \Webkul\Checkout\Contracts\Cart  $cart
     * @return void
     */
    public function apply_cart_rules($cart)
    {
        $this->cart_rule_helper->collect($cart);
    }
}