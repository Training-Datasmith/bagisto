<?php

declare (strict_types=1);
namespace Webkul\Checkout\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Webkul\Checkout\Models\Cart_Payment;
class Cart_Payment_Factory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Cart_Payment::class;
    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        return ['created_at' => now(), 'updated_at' => now()];
    }
}