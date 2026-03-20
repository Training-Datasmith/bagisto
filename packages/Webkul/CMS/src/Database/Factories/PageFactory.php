<?php

declare (strict_types=1);
namespace Webkul\CMS\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Webkul\CMS\Models\Page;
class Page_Factory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Page::class;
    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        return ['layout' => null];
    }
}