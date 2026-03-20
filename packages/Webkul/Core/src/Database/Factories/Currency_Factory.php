<?php

declare (strict_types=1);
namespace Webkul\Core\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Webkul\Core\Enums\Currency_Position_Enum;
use Webkul\Core\Models\Currency;
class Currency_Factory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Currency::class;
    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        return ['code' => $this->faker->unique()->currency_code, 'name' => $this->faker->word, 'decimal' => 2, 'group_separator' => ',', 'decimal_separator' => '.', 'currency_position' => Currency_Position_Enum::LEFT->value];
    }
}