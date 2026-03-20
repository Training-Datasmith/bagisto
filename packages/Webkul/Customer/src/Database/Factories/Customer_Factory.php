<?php

declare (strict_types=1);
namespace Webkul\Customer\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Hash;
use Webkul\Customer\Models\Customer;
class Customer_Factory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Customer::class;
    /**
     * States.
     *
     * @var array
     */
    protected $states = ['male', 'female'];
    /**
     * Define the model's default state.
     *
     * @throws \Exception
     */
    public function definition(): array
    {
        return ['first_name' => preg_replace('/[^a-zA-Z ]/', '', $this->faker->first_name()), 'last_name' => preg_replace('/[^a-zA-Z ]/', '', $this->faker->last_name()), 'gender' => Arr::random(['male', 'female', 'other']), 'email' => $this->faker->safe_email(), 'status' => 1, 'password' => Hash::make($this->faker->password), 'customer_group_id' => 2, 'channel_id' => 1, 'is_verified' => 1, 'created_at' => now(), 'updated_at' => now()];
    }
    /**
     * Male.
     */
    public function male(): Customer_Factory
    {
        return $this->state(function (array $attributes) {
            return ['gender' => 'Male'];
        });
    }
    /**
     * Female.
     */
    public function female(): Customer_Factory
    {
        return $this->state(function (array $attributes) {
            return ['gender' => 'Female'];
        });
    }
}