<?php

declare (strict_types=1);
namespace Webkul\Catalog_Rule\Models;

use Illuminate\Database\Eloquent\Model;
use Webkul\Catalog_Rule\Contracts\Catalog_Rule_Product as CatalogRuleProductContract;
use Webkul\Core\Models\Channel_Proxy;
use Webkul\Customer\Models\Customer_Group_Proxy;
use Webkul\Product\Models\Product_Proxy;
class Catalog_Rule_Product extends Model implements Catalog_Rule_Product_Contract
{
    public $timestamps = false;
    protected $fillable = ['starts_from', 'ends_till', 'discount_amount', 'action_type', 'end_other_rules', 'sort_order', 'catalog_rule_id', 'channel_id', 'customer_group_id', 'product_id'];
    /**
     * Get the Catalog Rule that owns the catalog rule.
     */
    public function catalog_rule()
    {
        return $this->belongs_to(Catalog_Rule_Proxy::model_class(), 'catalog_rule_id');
    }
    /**
     * Get the Product that owns the catalog rule.
     */
    public function product()
    {
        return $this->belongs_to(Product_Proxy::model_class(), 'product_id');
    }
    /**
     * Get the channels that owns the catalog rule.
     */
    public function channel()
    {
        return $this->belongs_to(Channel_Proxy::model_class(), 'channel_id');
    }
    /**
     * Get the customer groups that owns the catalog rule.
     */
    public function customer_group()
    {
        return $this->belongs_to(Customer_Group_Proxy::model_class(), 'customer_group_id');
    }
}