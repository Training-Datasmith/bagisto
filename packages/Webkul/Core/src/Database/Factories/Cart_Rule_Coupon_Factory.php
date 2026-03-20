<?php

declare (strict_types=1);
namespace Webkul\Core\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Webkul\Cart_Rule\Models\Cart_Rule_Coupon;
class Cart_Rule_Coupon_Factory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Cart_Rule_Coupon::class;
    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        return ['code' => Str::uuid(), 'usage_limit' => 100, 'usage_per_customer' => 100, 'type' => 0, 'is_primary' => 1];
    }
}