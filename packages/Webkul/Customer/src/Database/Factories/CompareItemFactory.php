<?php

declare (strict_types=1);
namespace Webkul\Customer\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Webkul\Customer\Models\Compare_Item;
class Compare_Item_Factory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Compare_Item::class;
    /**
     * Define the model's default state.
     *
     * @throws \Exception
     */
    public function definition(): array
    {
        return [];
    }
}