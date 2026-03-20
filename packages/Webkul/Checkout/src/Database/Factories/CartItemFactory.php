<?php

declare (strict_types=1);
namespace Webkul\Checkout\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Webkul\Checkout\Models\Cart_Item;
class Cart_Item_Factory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Cart_Item::class;
    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        return ['quantity' => 1, 'created_at' => now(), 'updated_at' => now()];
    }
    /**
     * Adjust product.
     */
    public function adjust_product(): Cart_Item_Factory
    {
        return $this->state(function () {
            $fallback_price = $this->faker->random_float(4, 0, 1000);
            return ['price' => $fallback_price, 'price_incl_tax' => $fallback_price, 'base_price' => $fallback_price, 'base_price_incl_tax' => $fallback_price, 'total' => $fallback_price, 'total_incl_tax' => $fallback_price, 'base_total' => $fallback_price, 'base_total_incl_tax' => $fallback_price];
        });
    }
}