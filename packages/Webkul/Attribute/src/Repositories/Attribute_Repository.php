<?php

declare (strict_types=1);
namespace Webkul\Attribute\Repositories;

use Illuminate\Container\Container;
use Webkul\Attribute\Contracts\Attribute;
use Webkul\Attribute\Enums\Attribute_Type_Enum;
use Webkul\Core\Eloquent\Repository;
class Attribute_Repository extends Repository
{
    /**
     * Attributes.
     *
     * @var array
     */
    protected $attributes = [];
    /**
     * Create a new repository instance.
     *
     * @return void
     */
    public function __construct(protected Attribute_Option_Repository $attribute_option_repository, Container $container)
    {
        parent::__construct($container);
    }
    /**
     * Specify model class name.
     */
    public function model(): string
    {
        return Attribute::class;
    }
    /**
     * Create attribute.
     *
     * @return \Webkul\Attribute\Contracts\Attribute
     */
    public function create(array $data)
    {
        $data = $this->validate_user_input($data);
        $options = $data['options'] ?? [];
        unset($data['options']);
        $attribute = $this->model->create($data);
        if (in_array($attribute->type, [Attribute_Type_Enum::CHECKBOX->value, Attribute_Type_Enum::SELECT->value, Attribute_Type_Enum::MULTISELECT->value])) {
            foreach ($options as $option_inputs) {
                $this->attribute_option_repository->create(array_merge(['attribute_id' => $attribute->id], $option_inputs));
            }
        }
        return $attribute;
    }
    /**
     * Update attribute.
     *
     * @param  int  $id
     * @param  string  $attribute
     * @return \Webkul\Attribute\Contracts\Attribute
     */
    public function update(array $data, $id)
    {
        $data = $this->validate_user_input($data);
        $attribute = $this->find($id);
        $attribute->update($data);
        if (!in_array($attribute->type, [Attribute_Type_Enum::CHECKBOX->value, Attribute_Type_Enum::SELECT->value, Attribute_Type_Enum::MULTISELECT->value])) {
            return $attribute;
        }
        if (!isset($data['options'])) {
            return $attribute;
        }
        foreach ($data['options'] as $option_id => $option_inputs) {
            $is_new = $option_inputs['isNew'] == 'true';
            if ($is_new) {
                $this->attribute_option_repository->create(array_merge(['attribute_id' => $attribute->id], $option_inputs));
            } else {
                $is_delete = $option_inputs['isDelete'] == 'true';
                if ($is_delete) {
                    $this->attribute_option_repository->delete($option_id);
                } else {
                    $this->attribute_option_repository->update($option_inputs, $option_id);
                }
            }
        }
        return $attribute;
    }
    /**
     * Validate user input.
     *
     * @param  array  $data
     * @return array
     */
    public function validate_user_input($data)
    {
        if (isset($data['is_configurable'])) {
            $data['value_per_channel'] = $data['value_per_locale'] = 0;
        }
        if (!in_array($data['type'], [Attribute_Type_Enum::CHECKBOX->value, Attribute_Type_Enum::SELECT->value, Attribute_Type_Enum::MULTISELECT->value, Attribute_Type_Enum::BOOLEAN->value])) {
            $data['is_filterable'] = 0;
        }
        if (in_array($data['type'], [Attribute_Type_Enum::SELECT->value, Attribute_Type_Enum::MULTISELECT->value, Attribute_Type_Enum::BOOLEAN->value])) {
            unset($data['value_per_locale']);
        }
        return $data;
    }
    /**
     * Get filter attributes.
     *
     * @return array
     */
    public function get_filterable_attributes()
    {
        return $this->model->where('is_filterable', 1)->get();
    }
    /**
     * Get product default attributes.
     *
     * @param  array  $codes
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function get_product_default_attributes($codes = null)
    {
        $attribute_columns = ['id', 'code', 'value_per_channel', 'value_per_locale', 'type', 'is_filterable', 'is_configurable'];
        if (!is_array($codes) && !$codes) {
            return $this->find_where_in('code', ['name', 'description', 'short_description', 'url_key', 'price', 'special_price', 'special_price_from', 'special_price_to', 'status'], $attribute_columns);
        }
        if (in_array('*', $codes)) {
            return $this->all($attribute_columns);
        }
        return $this->find_where_in('code', $codes, $attribute_columns);
    }
    /**
     * Get family attributes.
     *
     * @param  \Webkul\Attribute\Contracts\AttributeFamily  $attributeFamily
     * @return \Webkul\Attribute\Contracts\Attribute
     */
    public function get_family_attributes($attribute_family)
    {
        if (array_key_exists($attribute_family->id, $this->attributes)) {
            return $this->attributes[$attribute_family->id];
        }
        return $this->attributes[$attribute_family->id] = $attribute_family->custom_attributes;
    }
    /**
     * Get partials.
     *
     * @return array
     */
    public function get_partial()
    {
        $attributes = $this->model->all();
        $trimmed = [];
        foreach ($attributes as $attribute) {
            if ($attribute->code != 'tax_category_id' && (in_array($attribute->type, [Attribute_Type_Enum::SELECT->value, Attribute_Type_Enum::MULTISELECT->value]) || $attribute->code == 'sku')) {
                array_push($trimmed, ['id' => $attribute->id, 'name' => $attribute->admin_name, 'type' => $attribute->type, 'code' => $attribute->code, 'options' => $attribute->options]);
            }
        }
        return $trimmed;
    }
}