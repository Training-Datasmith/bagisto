<?php

declare (strict_types=1);
namespace Webkul\Catalog_Rule\Models;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\Has_Factory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Belongs_To_Many;
use Illuminate\Database\Eloquent\Relations\Has_Many;
use Webkul\Admin\Database\Factories\Catalog_Rule_Factory;
use Webkul\Catalog_Rule\Contracts\Catalog_Rule as CatalogRuleContract;
use Webkul\Core\Models\Channel_Proxy;
use Webkul\Customer\Models\Customer_Group_Proxy;
class Catalog_Rule extends Model implements Catalog_Rule_Contract
{
    use Has_Factory;
    /**
     * Add fillable property to the model.
     *
     * @var array
     */
    protected $fillable = ['name', 'description', 'starts_from', 'ends_till', 'status', 'condition_type', 'conditions', 'end_other_rules', 'action_type', 'discount_amount', 'sort_order'];
    /**
     * Cast the conditions to the array.
     *
     * @var array
     */
    protected $casts = ['conditions' => 'array'];
    /**
     * Get the channels that owns the catalog rule.
     */
    public function channels(): Belongs_To_Many
    {
        return $this->belongs_to_many(Channel_Proxy::model_class(), 'catalog_rule_channels');
    }
    /**
     * Get the customer groups that owns the catalog rule.
     */
    public function customer_groups(): Belongs_To_Many
    {
        return $this->belongs_to_many(Customer_Group_Proxy::model_class(), 'catalog_rule_customer_groups');
    }
    /**
     * Get the Catalog rule Product that owns the catalog rule
     */
    public function catalog_rule_products(): Has_Many
    {
        return $this->has_many(Catalog_Rule_Product_Proxy::model_class());
    }
    /**
     * Get the Catalog rule Product that owns the catalog rule.
     */
    public function catalog_rule_product_prices(): Has_Many
    {
        return $this->has_many(Catalog_Rule_Product_Price_Proxy::model_class());
    }
    /**
     * Create a new factory instance for the model.
     */
    protected static function new_factory(): Factory
    {
        return Catalog_Rule_Factory::new();
    }
}