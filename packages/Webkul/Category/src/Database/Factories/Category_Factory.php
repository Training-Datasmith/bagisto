<?php

declare (strict_types=1);
namespace Webkul\Category\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Webkul\Category\Models\Category;
class Category_Factory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Category::class;
    /**
     * @var string[]
     */
    protected $states = ['inactive', 'rtl'];
    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        return ['status' => 1, 'position' => $this->faker->random_digit(), 'parent_id' => 1];
    }
    public function inactive(): Category_Factory
    {
        return $this->state(function (array $attributes) {
            return ['status' => 0];
        });
    }
    /**
     * Handle rtl state
     */
    public function rtl(): Category_Factory
    {
        return $this->state(function (array $attributes) {
            return ['direction' => 'rtl'];
        });
    }
}