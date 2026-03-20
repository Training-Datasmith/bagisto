<?php

declare (strict_types=1);
namespace Webkul\Attribute\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\Has_Factory;
use Illuminate\Database\Eloquent\Relations\Has_Many;
use Webkul\Attribute\Contracts\Attribute as AttributeContract;
use Webkul\Attribute\Database\Factories\Attribute_Factory;
use Webkul\Core\Eloquent\Translatable_Model;
class Attribute extends Translatable_Model implements Attribute_Contract
{
    use Has_Factory;
    public $translated_attributes = ['name'];
    protected $fillable = ['code', 'admin_name', 'type', 'enable_wysiwyg', 'position', 'is_required', 'is_unique', 'validation', 'regex', 'value_per_locale', 'value_per_channel', 'default_value', 'is_filterable', 'is_configurable', 'is_visible_on_front', 'is_user_defined', 'swatch_type', 'is_comparable'];
    /**
     * Attribute type fields.
     *
     * @var array
     */
    public $attribute_type_fields = ['text' => 'text_value', 'textarea' => 'text_value', 'price' => 'float_value', 'boolean' => 'boolean_value', 'select' => 'integer_value', 'multiselect' => 'text_value', 'datetime' => 'datetime_value', 'date' => 'date_value', 'file' => 'text_value', 'image' => 'text_value', 'checkbox' => 'text_value'];
    /**
     * Get the options.
     */
    public function options(): Has_Many
    {
        return $this->has_many(Attribute_Option_Proxy::model_class());
    }
    /**
     * Scope a query to only include popular users.
     */
    public function scope_filterable_attributes(Builder $query): Builder
    {
        return $query->where('is_filterable', 1)->where('swatch_type', '<>', 'image')->order_by('position');
    }
    /**
     * Returns attribute value table column based attribute type
     *
     * @return string
     */
    protected function get_column_name_attribute()
    {
        return $this->attribute_type_fields[$this->type];
    }
    /**
     * Returns attribute validation rules
     *
     * @return string
     */
    protected function get_validations_attribute()
    {
        $validations = [];
        if ($this->is_required) {
            $validations[] = 'required: true';
        }
        if ($this->type == 'price') {
            $validations[] = 'decimal: true';
        }
        if ($this->type == 'file') {
            $ret_val = core()->get_config_data('catalog.products.attribute.file_attribute_upload_size') ?? '2048';
            if ($ret_val) {
                $validations[] = 'size:' . $ret_val;
            }
        }
        if ($this->type == 'image') {
            $ret_val = core()->get_config_data('catalog.products.attribute.image_attribute_upload_size') ?? '2048';
            if ($ret_val) {
                $validations[] = 'size:' . $ret_val . ', mimes: ["image/bmp", "image/jpeg", "image/jpg", "image/png", "image/webp"]';
            }
        }
        if ($this->validation == 'regex') {
            $validations[] = 'regex: ' . $this->regex;
        } elseif ($this->validation) {
            $validations[] = $this->validation . ': true';
        }
        $validations = '{ ' . implode(', ', array_filter($validations)) . ' }';
        return $validations;
    }
    /**
     * Create a new factory instance for the model
     */
    protected static function new_factory(): Factory
    {
        return Attribute_Factory::new();
    }
}