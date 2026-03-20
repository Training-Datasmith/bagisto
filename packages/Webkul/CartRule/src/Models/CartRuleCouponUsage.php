<?php

declare (strict_types=1);
namespace Webkul\Cart_Rule\Models;

use Illuminate\Database\Eloquent\Model;
use Webkul\Cart_Rule\Contracts\Cart_Rule_Coupon_Usage as CartRuleCouponUsageContract;
class Cart_Rule_Coupon_Usage extends Model implements Cart_Rule_Coupon_Usage_Contract
{
    public $timestamps = false;
    protected $table = 'cart_rule_coupon_usage';
    protected $guarded = ['created_at', 'updated_at'];
}