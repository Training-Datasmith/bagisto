<?php

declare (strict_types=1);
namespace Webkul\Core\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Webkul\Core\Models\Channel_Translation;
class Channel_Translation_Factory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Channel_Translation::class;
    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        return ['locale' => 'en', 'name' => $this->faker->word, 'home_seo' => ['meta_title' => $this->faker->sentence(), 'meta_description' => $this->faker->paragraph(), 'meta_keywords' => $this->faker->words(5, true)]];
    }
}