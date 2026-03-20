<?php

declare (strict_types=1);
namespace Webkul\Checkout\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Webkul\Checkout\Models\Cart_Shipping_Rate;
class Cart_Shipping_Rate_Factory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Cart_Shipping_Rate::class;
    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        return ['is_calculate_tax' => 1, 'discount_amount' => 0.0, 'base_discount_amount' => 0.0, 'created_at' => now(), 'updated_at' => now()];
    }
}