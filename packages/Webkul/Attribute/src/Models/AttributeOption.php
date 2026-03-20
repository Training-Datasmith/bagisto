<?php

declare (strict_types=1);
namespace Webkul\Attribute\Models;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\Has_Factory;
use Illuminate\Database\Eloquent\Relations\Belongs_To;
use Webkul\Attribute\Contracts\Attribute_Option as AttributeOptionContract;
use Webkul\Attribute\Database\Factories\Attribute_Option_Factory;
use Webkul\Core\Eloquent\Translatable_Model;
class Attribute_Option extends Translatable_Model implements Attribute_Option_Contract
{
    use Has_Factory;
    public $timestamps = false;
    public $translated_attributes = ['label'];
    protected $fillable = ['admin_name', 'swatch_value', 'sort_order', 'attribute_id'];
    /**
     * Append to the model attributes
     *
     * @var array
     */
    protected $appends = ['swatch_value_url'];
    /**
     * Get the attribute that owns the attribute option.
     */
    public function attribute(): Belongs_To
    {
        return $this->belongs_to(Attribute_Proxy::model_class());
    }
    /**
     * Get image url for the swatch value url.
     */
    public function swatch_value_url()
    {
        if ($this->swatch_value && $this->attribute->swatch_type == 'image') {
            return url('cache/small/' . $this->swatch_value);
        }
        return null;
    }
    /**
     * Get image url for the product image.
     */
    public function get_swatch_value_url_attribute()
    {
        return $this->swatch_value_url();
    }
    /**
     * Create a new factory instance for the model
     */
    protected static function new_factory(): Factory
    {
        return Attribute_Option_Factory::new();
    }
}