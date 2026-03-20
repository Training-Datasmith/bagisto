<?php

declare (strict_types=1);
namespace Webkul\Checkout\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Webkul\Checkout\Models\Cart_Address;
class Cart_Address_Factory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var \Webkul\Checkout\Models\CartAddress
     */
    protected $model = Cart_Address::class;
    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        return ['address' => implode(PHP_EOL, [$this->faker->address()]), 'company_name' => $this->faker->company(), 'first_name' => preg_replace('/[^a-zA-Z ]/', '', $this->faker->first_name()), 'last_name' => preg_replace('/[^a-zA-Z ]/', '', $this->faker->last_name()), 'email' => $this->faker->safe_email(), 'country' => $this->faker->country_code(), 'state' => $this->faker->random_element(['Delhi', 'Mumbai', 'Kolkata', 'Rajasthan']), 'city' => $this->faker->city(), 'postcode' => $this->faker->numerify('######'), 'phone' => $this->faker->e164phone_number(), 'address_type' => Cart_Address::ADDRESS_TYPE_BILLING];
    }
}