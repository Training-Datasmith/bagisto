<?php

declare (strict_types=1);
namespace Webkul\Customer\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Webkul\Customer\Models\Customer_Address;
class Customer_Address_Factory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Customer_Address::class;
    /**
     * Define the model's default state.
     *
     * @throws \Exception
     */
    public function definition(): array
    {
        $faker_it = \Faker\Factory::create('it_IT');
        return ['company_name' => $this->faker->company, 'vat_id' => $faker_it->vat_id(), 'email' => $this->faker->safe_email(), 'first_name' => preg_replace('/[^a-zA-Z ]/', '', $this->faker->first_name()), 'last_name' => preg_replace('/[^a-zA-Z ]/', '', $this->faker->last_name()), 'address' => $this->faker->street_address, 'country' => $this->faker->country_code, 'state' => $this->faker->state, 'city' => $this->faker->city, 'postcode' => rand(11111, 99999), 'phone' => $this->faker->e164phone_number, 'default_address' => $this->faker->boolean, 'address_type' => Customer_Address::ADDRESS_TYPE];
    }
}