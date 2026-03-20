<?php

declare (strict_types=1);
namespace Webkul\Attribute\Models;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\Has_Factory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Has_Many;
use Webkul\Attribute\Contracts\Attribute_Family as AttributeFamilyContract;
use Webkul\Attribute\Database\Factories\Attribute_Family_Factory;
use Webkul\Product\Models\Product_Proxy;
class Attribute_Family extends Model implements Attribute_Family_Contract
{
    use Has_Factory;
    public $timestamps = false;
    protected $fillable = ['code', 'name'];
    /**
     * Get all the attributes for the attribute groups.
     */
    public function custom_attributes()
    {
        return Attribute_Proxy::model_class()::join('attribute_group_mappings', 'attributes.id', '=', 'attribute_group_mappings.attribute_id')->join('attribute_groups', 'attribute_group_mappings.attribute_group_id', '=', 'attribute_groups.id')->join('attribute_families', 'attribute_groups.attribute_family_id', '=', 'attribute_families.id')->where('attribute_families.id', $this->id)->select('attributes.*');
    }
    /**
     * Get all the comparable attributes which belongs to attribute family.
     */
    public function get_comparable_attributes_belongs_to_family()
    {
        return Attribute_Proxy::model_class()::join('attribute_group_mappings', 'attribute_group_mappings.attribute_id', '=', 'attributes.id')->select('attributes.*')->where('attributes.is_comparable', 1)->distinct()->get();
    }
    /**
     * Get all the attributes for the attribute groups.
     */
    public function get_custom_attributes_attribute()
    {
        return $this->custom_attributes()->get();
    }
    /**
     * Get all the attribute groups.
     */
    public function attribute_groups(): Has_Many
    {
        return $this->has_many(Attribute_Group_Proxy::model_class())->order_by('position');
    }
    /**
     * Get all the attributes for the attribute groups.
     */
    public function get_configurable_attributes_attribute()
    {
        return $this->custom_attributes()->where('attributes.is_configurable', 1)->where('attributes.type', 'select')->get();
    }
    /**
     * Get all the products.
     */
    public function products(): Has_Many
    {
        return $this->has_many(Product_Proxy::model_class());
    }
    /**
     * Create a new factory instance for the model
     */
    protected static function new_factory(): Factory
    {
        return Attribute_Family_Factory::new();
    }
}