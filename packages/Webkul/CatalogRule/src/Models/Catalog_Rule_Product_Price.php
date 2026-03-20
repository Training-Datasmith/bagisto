<?php

declare (strict_types=1);
namespace Webkul\Catalog_Rule\Models;

use Illuminate\Database\Eloquent\Model;
use Webkul\Catalog_Rule\Contracts\Catalog_Rule_Product_Price as CatalogRuleProductPriceContract;
class Catalog_Rule_Product_Price extends Model implements Catalog_Rule_Product_Price_Contract
{
    public $timestamps = false;
    protected $fillable = ['price', 'rule_date', 'starts_from', 'ends_till', 'catalog_rule_id', 'channel_id', 'customer_group_id'];
}