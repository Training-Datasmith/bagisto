<?php

declare (strict_types=1);
namespace Webkul\Catalog_Rule\Helpers;

use Carbon\Carbon;
use Webkul\Catalog_Rule\Repositories\Catalog_Rule_Repository;
class Catalog_Rule_Index
{
    /**
     * Create a new helper instance.
     *
     * @return void
     */
    public function __construct(protected Catalog_Rule_Repository $catalog_rule_repository, protected Catalog_Rule_Product $catalog_rule_product_helper, protected Catalog_Rule_Product_Price $catalog_rule_product_price_helper)
    {
    }
    /**
     * Full re-index
     *
     * @return void
     */
    public function re_index_complete()
    {
        try {
            $this->clean_product_indices();
            foreach ($this->get_catalog_rules() as $rule) {
                $this->catalog_rule_product_helper->insert_rule_product($rule);
            }
            $this->catalog_rule_product_price_helper->index_rule_product_price(1000);
        } catch (\Exception $e) {
            report($e);
        }
    }
    /**
     * Re-index rule indices
     *
     * @param  \Webkul\CatalogRule\Contracts\CatalogRule  $rule
     * @return void
     */
    public function re_index_rule($rule)
    {
        $this->clean_rule_indices($rule);
        $starts_from = $rule->starts_from ? Carbon::create_from_time_string($rule->starts_from . ' 00:00:01') : null;
        $ends_till = $rule->ends_till ? Carbon::create_from_time_string($rule->ends_till . ' 23:59:59') : null;
        if ((!$starts_from || $starts_from <= Carbon::now()) && (!$ends_till || $ends_till >= Carbon::now())) {
            $this->catalog_rule_product_helper->insert_rule_product($rule);
        }
        $this->catalog_rule_product_price_helper->index_rule_product_price(1000);
    }
    /**
     * Re-index single product
     *
     * @param  \Webkul\Product\Contracts\Product  $product
     * @return void
     */
    public function re_index_product($product)
    {
        try {
            if (!$product->get_type_instance()->price_rule_can_be_applied()) {
                return;
            }
            $product_ids = $product->get_type_instance()->is_composite() ? $product->get_type_instance()->get_children_ids() : [$product->id];
            $this->clean_product_indices($product_ids);
            foreach ($this->get_catalog_rules() as $rule) {
                $this->catalog_rule_product_helper->insert_rule_product($rule, 1000, $product);
            }
            $this->catalog_rule_product_price_helper->index_rule_product_price(1000, $product);
        } catch (\Exception $e) {
            report($e);
        }
    }
    /**
     * Clean rule indices
     *
     * @param  \Webkul\CatalogRule\Contracts\CatalogRule  $rule
     * @return void
     */
    public function clean_rule_indices($rule)
    {
        $this->catalog_rule_product_helper->clean_rule_indices($rule);
        $this->catalog_rule_product_price_helper->clean_product_price_indices();
    }
    /**
     * Clean products indices
     *
     * @param  array  $productIds
     * @return void
     */
    public function clean_product_indices($product_ids = [])
    {
        $this->catalog_rule_product_helper->clean_product_indices($product_ids);
        $this->catalog_rule_product_price_helper->clean_product_price_indices($product_ids);
    }
    /**
     * Returns catalog rules
     *
     * @return \Illuminate\Support\Collection
     */
    public function get_catalog_rules()
    {
        $catalog_rules = $this->catalog_rule_repository->scope_query(function ($query) {
            return $query->where(function ($query1) {
                $query1->where('catalog_rules.starts_from', '<=', Carbon::now()->format('Y-m-d'))->or_where_null('catalog_rules.starts_from');
            })->where(function ($query2) {
                $query2->where('catalog_rules.ends_till', '>=', Carbon::now()->format('Y-m-d'))->or_where_null('catalog_rules.ends_till');
            })->order_by('sort_order', 'asc');
        })->find_where(['status' => 1]);
        return $catalog_rules;
    }
}