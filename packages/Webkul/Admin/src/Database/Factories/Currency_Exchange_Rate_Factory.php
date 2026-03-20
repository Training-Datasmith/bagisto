<?php

declare (strict_types=1);
namespace Webkul\Admin\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Webkul\Core\Models\Currency_Exchange_Rate;
class Currency_Exchange_Rate_Factory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Currency_Exchange_Rate::class;
    /**
     * Define the model's default state.
     */
    public function definition()
    {
        return ['rate' => rand(1, 100)];
    }
}