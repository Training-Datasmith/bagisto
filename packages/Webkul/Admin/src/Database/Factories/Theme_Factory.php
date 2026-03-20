<?php

declare (strict_types=1);
namespace Webkul\Admin\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Webkul\Theme\Models\Theme_Customization as ThemeCustomizationModel;
class Theme_Factory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Theme_Customization_Model::class;
    /**
     * Define the model's default state.
     */
    public function definition()
    {
        $last_theme = Theme_Customization_Model::query()->order_by('id', 'desc')->limit(1)->first();
        $types = ['product_carousel', 'category_carousel', 'image_carousel', 'footer_links', 'services_content'];
        return ['type' => $this->faker->random_element($types), 'name' => preg_replace('/[^a-zA-Z ]/', '', $this->faker->name()), 'sort_order' => ($last_theme ? $last_theme->id : 0) + 1, 'channel_id' => core()->get_current_channel()->id, 'theme_code' => core()->get_current_channel()->theme];
    }
}