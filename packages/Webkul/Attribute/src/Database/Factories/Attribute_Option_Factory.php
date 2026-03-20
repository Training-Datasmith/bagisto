<?php

declare (strict_types=1);
namespace Webkul\Attribute\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Webkul\Attribute\Models\Attribute_Option;
class Attribute_Option_Factory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Attribute_Option::class;
    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        return ['admin_name' => $this->faker->word, 'sort_order' => $this->faker->random_digit(), 'swatch_value' => null];
    }
}