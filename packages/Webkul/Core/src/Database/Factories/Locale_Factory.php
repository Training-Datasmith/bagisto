<?php

declare (strict_types=1);
namespace Webkul\Core\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Webkul\Core\Models\Locale;
class Locale_Factory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Locale::class;
    /**
     * @var array
     */
    protected $states = ['rtl'];
    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        do {
            $language_code = $this->faker->language_code;
        } while (Locale::query()->where('code', $language_code)->exists());
        return ['code' => $language_code, 'name' => $this->faker->country, 'direction' => 'ltr'];
    }
    public function rtl(): Locale_Factory
    {
        return $this->state(function (array $attributes) {
            return ['direction' => 'rtl'];
        });
    }
}