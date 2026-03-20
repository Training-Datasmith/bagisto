<?php

declare (strict_types=1);
namespace Webkul\Cart_Rule\Models;

use Illuminate\Database\Eloquent\Model;
use Webkul\Cart_Rule\Contracts\Cart_Rule_Customer as CartRuleCustomerContract;
class Cart_Rule_Customer extends Model implements Cart_Rule_Customer_Contract
{
    public $timestamps = false;
    protected $fillable = ['times_used', 'cart_rule_id', 'customer_id'];
}