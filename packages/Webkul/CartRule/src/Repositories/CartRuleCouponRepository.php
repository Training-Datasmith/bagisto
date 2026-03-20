<?php

declare (strict_types=1);
namespace Webkul\Cart_Rule\Repositories;

use Webkul\Core\Eloquent\Repository;
class Cart_Rule_Coupon_Repository extends Repository
{
    /**
     * @var array
     */
    protected $charset = ['alphanumeric' => 'ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789', 'alphabetical' => 'ABCDEFGHIJKLMNOPQRSTUVWXYZ', 'numeric' => '0123456789'];
    /**
     * Specify Model class name
     */
    public function model(): string
    {
        return 'Webkul\CartRule\Contracts\CartRuleCoupon';
    }
    /**
     * Creates coupons for cart rule
     */
    public function generate_coupons(array $data, int $cart_rule_id): void
    {
        $cart_rule = app('Webkul\CartRule\Repositories\CartRuleRepository')->find_or_fail($cart_rule_id);
        for ($i = 0; $i < $data['coupon_qty']; $i++) {
            parent::create(['cart_rule_id' => $cart_rule_id, 'code' => $data['code_prefix'] . $this->get_random_string($data['code_format'], $data['code_length']) . $data['code_suffix'], 'usage_limit' => $cart_rule->uses_per_coupon ?? 0, 'usage_per_customer' => $cart_rule->usage_per_customer ?? 0, 'is_primary' => 0, 'expired_at' => $cart_rule->ends_till ?: null]);
        }
    }
    /**
     * Creates coupons for cart rule
     */
    public function get_random_string(string $format, int $length): string
    {
        $coupon_code = '';
        for ($i = 0; $i < $length; $i++) {
            $coupon_code .= $this->charset[$format][random_int(0, strlen($this->charset[$format]) - 1)];
        }
        return $coupon_code;
    }
}