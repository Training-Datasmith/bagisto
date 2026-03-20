<?php

declare (strict_types=1);
namespace Webkul\Core\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Webkul\Core\Models\Channel;
use Webkul\Core\Models\Currency;
use Webkul\Core\Models\Locale;
use Webkul\Inventory\Models\Inventory_Source;
class Channel_Factory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Channel::class;
    /**
     * Configure the model factory.
     */
    public function configure(): static
    {
        return $this->has_attached(Currency::in_random_order()->limit(1)->get())->has_attached(Locale::in_random_order()->limit(1)->get())->has_attached(Inventory_Source::in_random_order()->limit(1)->get(), [], 'inventory_sources')->has_translations();
    }
    /**
     * Define the model's default state.
     *
     * @throws \JsonException
     */
    public function definition(): array
    {
        return ['code' => $code = $this->faker->unique()->word(), 'theme' => $code, 'hostname' => 'http://' . $this->faker->ipv4(), 'root_category_id' => 1, 'default_locale_id' => 1, 'base_currency_id' => 1];
    }
}