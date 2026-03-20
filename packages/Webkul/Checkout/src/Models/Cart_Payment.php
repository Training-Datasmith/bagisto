<?php

declare (strict_types=1);
namespace Webkul\Checkout\Models;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\Has_Factory;
use Illuminate\Database\Eloquent\Model;
use Webkul\Checkout\Contracts\Cart_Payment as CartPaymentContract;
use Webkul\Checkout\Database\Factories\Cart_Payment_Factory;
class Cart_Payment extends Model implements Cart_Payment_Contract
{
    use Has_Factory;
    protected $table = 'cart_payment';
    /**
     * Create a new factory instance for the model
     */
    protected static function new_factory(): Factory
    {
        return Cart_Payment_Factory::new();
    }
}