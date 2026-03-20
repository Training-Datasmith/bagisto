<?php

declare (strict_types=1);
namespace Webkul\Attribute\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Webkul\Attribute\Models\Attribute;
class Attribute_Factory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Attribute::class;
    /**
     * @var array
     */
    protected $states = ['validation_numeric', 'validation_email', 'validation_decimal', 'validation_url', 'required', 'unique', 'filterable', 'configurable'];
    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        $types = ['text', 'textarea', 'price', 'boolean', 'select', 'multiselect', 'datetime', 'date', 'image', 'file', 'checkbox'];
        return ['admin_name' => $this->faker->word, 'code' => $this->faker->regexify('/^[a-zA-Z]+[a-zA-Z0-9_]+$/'), 'type' => array_rand($types), 'validation' => '', 'position' => $this->faker->random_digit, 'is_required' => false, 'is_unique' => false, 'value_per_locale' => false, 'value_per_channel' => false, 'is_filterable' => false, 'is_configurable' => false, 'is_user_defined' => true, 'is_visible_on_front' => true, 'swatch_type' => null];
    }
    public function validation_numeric(): Attribute_Factory
    {
        return $this->state(function (array $attributes) {
            return ['validation' => 'numeric'];
        });
    }
    public function validation_email(): Attribute_Factory
    {
        return $this->state(function (array $attributes) {
            return ['validation' => 'email'];
        });
    }
    public function validation_decimal(): Attribute_Factory
    {
        return $this->state(function (array $attributes) {
            return ['validation' => 'decimal'];
        });
    }
    public function validation_url(): Attribute_Factory
    {
        return $this->state(function (array $attributes) {
            return ['validation' => 'url'];
        });
    }
    public function required(): Attribute_Factory
    {
        return $this->state(function (array $attributes) {
            return ['is_required' => true];
        });
    }
    public function unique(): Attribute_Factory
    {
        return $this->state(function (array $attributes) {
            return ['is_unique' => true];
        });
    }
    public function filterable(): Attribute_Factory
    {
        return $this->state(function (array $attributes) {
            return ['is_filterable' => true];
        });
    }
    public function configurable(): Attribute_Factory
    {
        return $this->state(function (array $attributes) {
            return ['is_configurable' => true];
        });
    }
}