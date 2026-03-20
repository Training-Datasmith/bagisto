<?php

declare (strict_types=1);
namespace Webkul\Checkout\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Webkul\Checkout\Models\Cart;
class Cart_Factory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Cart::class;
    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        return ['channel_id' => core()->get_current_channel()->id, 'global_currency_code' => $base_currency_code = core()->get_base_currency_code(), 'base_currency_code' => $base_currency_code, 'channel_currency_code' => core()->get_channel_base_currency_code(), 'cart_currency_code' => core()->get_current_currency_code(), 'items_count' => 1, 'is_guest' => 1, 'customer_email' => $this->faker->safe_email(), 'customer_first_name' => preg_replace('/[^a-zA-Z ]/', '', $this->faker->first_name()), 'customer_last_name' => preg_replace('/[^a-zA-Z ]/', '', $this->faker->last_name())];
    }
}