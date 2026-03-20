<?php

declare (strict_types=1);
namespace Webkul\Cart_Rule\Helpers;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Webkul\Cart_Rule\Repositories\Cart_Rule_Coupon_Repository;
use Webkul\Cart_Rule\Repositories\Cart_Rule_Coupon_Usage_Repository;
use Webkul\Cart_Rule\Repositories\Cart_Rule_Customer_Repository;
use Webkul\Cart_Rule\Repositories\Cart_Rule_Repository;
use Webkul\Checkout\Facades\Cart;
use Webkul\Checkout\Models\Cart_Item;
use Webkul\Customer\Repositories\Customer_Repository;
use Webkul\Rule\Helpers\Validator;
class Cart_Rule
{
    /**
     * @var \Webkul\Checkout\Contracts\Cart
     */
    protected $cart = null;
    /**
     * @var array
     */
    protected $item_totals = [];
    /**
     * @var array
     */
    protected $cart_rules = null;
    /**
     * Create a new helper instance.
     *
     *
     * @return void
     */
    public function __construct(protected Customer_Repository $customer_repository, protected Cart_Rule_Repository $cart_rule_repository, protected Cart_Rule_Coupon_Repository $cart_rule_coupon_repository, protected Cart_Rule_Customer_Repository $cart_rule_customer_repository, protected Cart_Rule_Coupon_Usage_Repository $cart_rule_coupon_usage_repository, protected Validator $validator)
    {
    }
    /**
     * Collect discount on cart
     *
     * @param  \Webkul\Checkout\Contracts\Cart  $cart
     * @return void
     */
    public function collect($cart)
    {
        $this->cart = $cart;
        /**
         * If cart rules are not available then don't process further.
         */
        if (!$this->have_cart_rules() && !(float) $cart->base_discount_amount) {
            return;
        }
        $applied_cart_rule_ids = [];
        $this->calculate_cart_item_totals();
        foreach ($cart->items as $item) {
            $item_cart_rule_ids = $this->process($item);
            $applied_cart_rule_ids = array_merge($applied_cart_rule_ids, $item_cart_rule_ids);
            if ($item->children()->count() && $item->get_type_instance()->is_children_calculated()) {
                $this->divide_discount($item);
            }
        }
        $this->cart->update(['applied_cart_rule_ids' => implode(',', array_unique($applied_cart_rule_ids, SORT_REGULAR))]);
        $this->process_shipping_discount();
        $this->process_free_shipping_discount();
        if (!$this->check_coupon_code()) {
            cart()->remove_coupon_code();
        }
    }
    /**
     * Returns cart rules
     *
     * @return \Illuminate\Support\Collection
     */
    public function get_cart_rules()
    {
        if ($this->cart_rules) {
            return $this->cart_rules;
        }
        $this->cart_rules = $this->get_cart_rule_query()->with(['cart_rule_customer_groups', 'cart_rule_channels', 'cart_rule_coupon'])->get();
        return $this->cart_rules;
    }
    /**
     * Check if cart rule can be applied
     *
     * @param  \Webkul\CartRule\Contracts\CartRule  $rule
     */
    public function can_process_rule($rule): bool
    {
        if ($rule->coupon_type) {
            if (!strlen($this->cart->coupon_code)) {
                return false;
            }
            /** @var \Webkul\CartRule\Models\CartRule $rule */
            // Laravel relation is used instead of repository for performance
            // reasons (cart_rule_coupon-relation is pre-loaded by self::getCartRuleQuery())
            $coupon = $rule->cart_rule_coupon()->where('code', $this->cart->coupon_code)->first();
            if ($coupon && $coupon->code === $this->cart->coupon_code) {
                if ($coupon->usage_limit && $coupon->times_used >= $coupon->usage_limit) {
                    return false;
                }
                if ($this->cart->customer_id && $coupon->usage_per_customer) {
                    $coupon_usage = $this->cart_rule_coupon_usage_repository->find_one_where(['cart_rule_coupon_id' => $coupon->id, 'customer_id' => $this->cart->customer_id]);
                    if ($coupon_usage && $coupon_usage->times_used >= $coupon->usage_per_customer) {
                        return false;
                    }
                }
            } else {
                return false;
            }
        }
        if ($rule->usage_per_customer) {
            $rule_customer = $this->cart_rule_customer_repository->find_one_where(['cart_rule_id' => $rule->id, 'customer_id' => $this->cart->customer_id]);
            if ($rule_customer && $rule_customer->times_used >= $rule->usage_per_customer) {
                return false;
            }
        }
        return true;
    }
    /**
     * Cart item discount calculation process
     */
    public function process(Cart_Item $item): array
    {
        $item->discount_percent = 0;
        $item->discount_amount = 0;
        $item->base_discount_amount = 0;
        $applied_rule_ids = [];
        foreach ($rules = $this->get_cart_rules() as $rule) {
            if (!$this->can_process_rule($rule)) {
                continue;
            }
            if (!$this->validator->validate($rule, $item)) {
                continue;
            }
            if ($rule->coupon_code) {
                $item->coupon_code = $rule->coupon_code;
            }
            $quantity = $rule->discount_quantity ? min($item->quantity, $rule->discount_quantity) : $item->quantity;
            $discount_amount = $base_discount_amount = 0;
            switch ($rule->action_type) {
                case 'by_percent':
                    $rule_percent = min(100, $rule->discount_amount);
                    $discount_amount = ($quantity * $item->price - $item->discount_amount) * ($rule_percent / 100);
                    $base_discount_amount = ($quantity * $item->base_price - $item->base_discount_amount) * ($rule_percent / 100);
                    if (!$rule->discount_quantity || $rule->discount_quantity > $quantity) {
                        $discount_percent = min(100, $item->discount_percent + $rule_percent);
                        $item->discount_percent = $discount_percent;
                    }
                    break;
                case 'by_fixed':
                    $discount_amount = $quantity * core()->convert_price($rule->discount_amount);
                    $base_discount_amount = $quantity * $rule->discount_amount;
                    break;
                case 'cart_fixed':
                    if ($this->item_totals[$rule->id]['total_items'] <= 1) {
                        $discount_amount = core()->convert_price($rule->discount_amount);
                        $base_discount_amount = min($item->base_price * $quantity, $rule->discount_amount);
                    } else {
                        $discount_rate = $item->base_price * $quantity / $this->item_totals[$rule->id]['base_total_price'];
                        $max_discount = $rule->discount_amount * $discount_rate;
                        $discount_amount = core()->convert_price($max_discount);
                        $base_discount_amount = min($item->base_price * $quantity, $max_discount);
                    }
                    break;
                case 'buy_x_get_y':
                    if (!$rule->discount_step || $rule->discount_amount > $rule->discount_step) {
                        break;
                    }
                    $buy_and_discount_qty = $rule->discount_step + $rule->discount_amount;
                    $qty_period = floor($quantity / $buy_and_discount_qty);
                    $free_qty = $quantity - $qty_period * $buy_and_discount_qty;
                    $discount_qty = $qty_period * $rule->discount_amount;
                    if ($free_qty > $rule->discount_step) {
                        $discount_qty += $free_qty - $rule->discount_step;
                    }
                    $discount_amount = $discount_qty * $item->price;
                    $base_discount_amount = $discount_qty * $item->base_price;
                    break;
            }
            $item->discount_amount = min($item->discount_amount + $discount_amount, $item->price * $quantity);
            $item->base_discount_amount = min($item->base_discount_amount + $base_discount_amount, $item->base_price * $quantity);
            $applied_rule_ids[$rule->id] = $rule->id;
            if ($rule->end_other_rules) {
                break;
            }
        }
        $item->applied_cart_rule_ids = implode(',', $applied_rule_ids);
        $item->save();
        return $applied_rule_ids;
    }
    /**
     * Cart shipping discount calculation process
     *
     * @return self|void
     */
    public function process_shipping_discount()
    {
        if (!$selected_shipping = $this->cart->selected_shipping_rate) {
            return;
        }
        $selected_shipping->discount_amount = 0;
        $selected_shipping->base_discount_amount = 0;
        $applied_rule_ids = [];
        foreach ($this->get_cart_rules() as $rule) {
            if (!$this->can_process_rule($rule)) {
                continue;
            }
            if (!$this->validator->validate($rule, $this->cart)) {
                continue;
            }
            if (!$rule || !$rule->apply_to_shipping) {
                continue;
            }
            $discount_amount = $base_discount_amount = 0;
            switch ($rule->action_type) {
                case 'by_percent':
                    $rule_percent = min(100, $rule->discount_amount);
                    $discount_amount = ($selected_shipping->price - $selected_shipping->discount_amount) * $rule_percent / 100;
                    $base_discount_amount = ($selected_shipping->base_price - $selected_shipping->base_discount_amount) * $rule_percent / 100;
                    break;
                case 'by_fixed':
                    $discount_amount = core()->convert_price($rule->discount_amount);
                    $base_discount_amount = $rule->discount_amount;
                    break;
            }
            $selected_shipping->discount_amount = min($selected_shipping->discount_amount + $discount_amount, $selected_shipping->price);
            $selected_shipping->base_discount_amount = min($selected_shipping->base_discount_amount + $base_discount_amount, $selected_shipping->base_price);
            $selected_shipping->save();
            $applied_rule_ids[$rule->id] = $rule->id;
            if ($rule->end_other_rules) {
                break;
            }
        }
        $selected_shipping->save();
        $cart_applied_cart_rule_ids = array_merge(explode(',', $this->cart->applied_cart_rule_ids), $applied_rule_ids);
        $cart_applied_cart_rule_ids = array_filter($cart_applied_cart_rule_ids);
        $cart_applied_cart_rule_ids = array_unique($cart_applied_cart_rule_ids);
        $this->cart->update(['applied_cart_rule_ids' => implode(',', $cart_applied_cart_rule_ids)]);
        return $this;
    }
    /**
     * Cart free shipping discount calculation process
     *
     * @return void
     */
    public function process_free_shipping_discount()
    {
        if (!$selected_shipping = $this->cart->selected_shipping_rate) {
            return;
        }
        $selected_shipping->discount_amount = 0;
        $selected_shipping->base_discount_amount = 0;
        $applied_rule_ids = [];
        foreach ($this->cart->items->all() as $item) {
            foreach ($this->get_cart_rules() as $rule) {
                if (!$this->can_process_rule($rule)) {
                    continue;
                }
                /* given CartItem instance to the validator */
                if (!$this->validator->validate($rule, $item)) {
                    continue;
                }
                if (!$rule || !$rule->free_shipping) {
                    continue;
                }
                $selected_shipping->price = 0;
                $selected_shipping->price_incl_tax = 0;
                $selected_shipping->base_price = 0;
                $selected_shipping->base_price_incl_tax = 0;
                $selected_shipping->save();
                $applied_rule_ids[$rule->id] = $rule->id;
                if ($rule->end_other_rules) {
                    break;
                }
            }
        }
        $cart_applied_cart_rule_ids = array_merge(explode(',', $this->cart->applied_cart_rule_ids), $applied_rule_ids);
        $cart_applied_cart_rule_ids = array_filter($cart_applied_cart_rule_ids);
        $cart_applied_cart_rule_ids = array_unique($cart_applied_cart_rule_ids);
        $this->cart->update(['applied_cart_rule_ids' => implode(',', $cart_applied_cart_rule_ids)]);
    }
    /**
     * Calculate cart item totals for each rule
     *
     * @return array|void
     */
    public function calculate_cart_item_totals()
    {
        foreach ($this->get_cart_rules() as $rule) {
            if ($rule->action_type != 'cart_fixed') {
                continue;
            }
            $total_price = $total_base_price = $valid_count = 0;
            foreach ($this->cart->items as $item) {
                if (!$this->can_process_rule($rule)) {
                    continue;
                }
                if (!$this->validator->validate($rule, $item)) {
                    continue;
                }
                $quantity = $rule->discount_quantity ? min($item->quantity, $rule->discount_quantity) : $item->quantity;
                $total_base_price += $item->base_price * $quantity;
                $valid_count++;
            }
            $this->item_totals[$rule->id] = ['base_total_price' => $total_base_price, 'total_items' => $valid_count];
        }
    }
    /**
     * Check if coupon code is applied or not
     */
    public function check_coupon_code(): bool
    {
        if (!$this->cart->coupon_code) {
            return true;
        }
        $coupons = $this->cart_rule_coupon_repository->where(['code' => $this->cart->coupon_code])->get();
        foreach ($coupons as $coupon) {
            if (in_array($coupon->cart_rule_id, explode(',', $this->cart->applied_cart_rule_ids))) {
                return true;
            }
        }
        return false;
    }
    /**
     * Divide discount amount to children
     *
     * @param  \Webkul\Checkout\Contracts\CartItem  $item
     * @return void
     */
    protected function divide_discount($item)
    {
        foreach ($item->children as $child) {
            $ratio = $item->base_total != 0 ? $child->base_total / $item->base_total : 0;
            foreach (['discount_amount', 'base_discount_amount'] as $column) {
                if (!$item->{$column}) {
                    continue;
                }
                $child->{$column} = round($item->{$column} * $ratio, 4);
                $child->save();
            }
        }
    }
    /**
     * @return \Builder
     */
    public function get_cart_rule_query()
    {
        $customer_group = $this->customer_repository->get_current_group();
        return $this->cart_rule_repository->left_join('cart_rule_customer_groups', 'cart_rules.id', '=', 'cart_rule_customer_groups.cart_rule_id')->left_join('cart_rule_channels', 'cart_rules.id', '=', 'cart_rule_channels.cart_rule_id')->where('cart_rule_customer_groups.customer_group_id', $customer_group->id)->where('cart_rule_channels.channel_id', core()->get_current_channel()->id)->where(function ($query) {
            /** @var Builder $query1 */
            $query->where('cart_rules.starts_from', '<=', Carbon::now()->format('Y-m-d H:m:s'))->or_where_null('cart_rules.starts_from');
        })->where(function ($query) {
            /** @var Builder $query2 */
            $query->where('cart_rules.ends_till', '>=', Carbon::now()->format('Y-m-d H:m:s'))->or_where_null('cart_rules.ends_till');
        })->where('status', 1)->order_by('sort_order', 'asc');
    }
    /**
     * Check if cart rules are available or not for current customer group and channel
     */
    public function have_cart_rules(): bool
    {
        return (bool) $this->get_cart_rule_query()->count();
    }
}