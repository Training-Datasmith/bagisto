<?php

declare (strict_types=1);
namespace Webkul\Admin\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Event;
use Webkul\Catalog_Rule\Models\Catalog_Rule;
class Catalog_Rule_Factory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Catalog_Rule::class;
    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        $starts_from = $this->faker->date_time_between('now', '+30 days');
        $ends_till = $this->faker->date_time_between($starts_from, $starts_from->format('Y-m-d') . ' +30 days');
        return ['starts_from' => $this->faker->date_time_this_month, 'ends_till' => $this->faker->date_time_between($starts_from, $ends_till), 'status' => $this->faker->boolean(), 'name' => preg_replace('/[^a-zA-Z ]/', '', $this->faker->name()), 'description' => substr($this->faker->paragraph, 0, 50), 'action_type' => 'by_percent', 'discount_amount' => rand(1, 50)];
    }
    /**
     * Configure the model factory.
     */
    public function configure(): static
    {
        return $this->after_creating(function (Catalog_Rule $catalog_rule) {
            Event::dispatch('promotions.catalog_rule.update.after', $catalog_rule);
        });
    }
}