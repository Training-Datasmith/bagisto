<?php

declare (strict_types=1);
namespace Webkul\Catalog_Rule\Helpers;

use Carbon\Carbon;
use Webkul\Attribute\Repositories\Attribute_Repository;
use Webkul\Catalog_Rule\Repositories\Catalog_Rule_Product_Repository;
use Webkul\Product\Repositories\Product_Repository;
use Webkul\Rule\Helpers\Validator;
class Catalog_Rule_Product
{
    /**
     * Create a new helper instance.
     *
     * @return void
     */
    public function __construct(protected Attribute_Repository $attribute_repository, protected Product_Repository $product_repository, protected Catalog_Rule_Product_Repository $catalog_rule_product_repository, protected Validator $validator)
    {
    }
    /**
     * Collect discount on cart
     *
     * @param  \Webkul\CatalogRule\Contracts\CatalogRule  $rule
     * @param  int  $batchCount
     * @return void
     */
    public function insert_rule_product($rule, $batch_count = 1000, $product = null)
    {
        if (!(float) $rule->discount_amount) {
            return;
        }
        $product_ids = $this->get_matching_product_ids($rule, $product);
        $rows = [];
        $starts_from = $rule->starts_from ? Carbon::create_from_time_string($rule->starts_from . ' 00:00:01') : null;
        $ends_till = $rule->ends_till ? Carbon::create_from_time_string($rule->ends_till . ' 23:59:59') : null;
        $channel_ids = $rule->channels->pluck('id');
        $customer_group_ids = $rule->customer_groups->pluck('id');
        foreach ($product_ids as $product_id) {
            foreach ($channel_ids as $channel_id) {
                foreach ($customer_group_ids as $customer_group_id) {
                    $rows[] = ['starts_from' => $starts_from, 'ends_till' => $ends_till, 'catalog_rule_id' => $rule->id, 'channel_id' => $channel_id, 'customer_group_id' => $customer_group_id, 'product_id' => $product_id, 'discount_amount' => $rule->discount_amount, 'action_type' => $rule->action_type, 'end_other_rules' => $rule->end_other_rules, 'sort_order' => $rule->sort_order];
                    if (count($rows) == $batch_count) {
                        $this->catalog_rule_product_repository->insert($rows);
                        $rows = [];
                    }
                }
            }
        }
        if (!empty($rows)) {
            $this->catalog_rule_product_repository->insert($rows);
        }
    }
    /**
     * Clean catalog rule product indices
     *
     * @param  \Webkul\CatalogRule\Contracts\CatalogRule  $rule
     * @return void
     */
    public function clean_rule_indices($rule)
    {
        $this->catalog_rule_product_repository->where('catalog_rule_id', $rule->id)->delete();
    }
    /**
     * Clean products indices
     *
     * @param  array  $productIds
     * @return void
     */
    public function clean_product_indices($product_ids = [])
    {
        if (count($product_ids)) {
            $this->catalog_rule_product_repository->where_in('product_id', $product_ids)->delete();
        } else {
            $this->catalog_rule_product_repository->delete_where([['product_id', 'like', '%%']]);
        }
    }
    /**
     * Get array of product ids which are matched by rule
     *
     * @param  \Webkul\CatalogRule\Contracts\CatalogRule  $rule
     * @param  \Webkul\Product\Contracts\Product  $product
     * @return array
     */
    public function get_matching_product_ids($rule, $product = null)
    {
        $products = $this->product_repository->scope_query(function ($query) use ($rule, $product) {
            $query = $query->add_select('products.*');
            if ($product) {
                $query->where('products.id', $product->id);
            }
            if (!$rule->conditions) {
                return $query;
            }
            $applied_attributes = [];
            foreach ($rule->conditions as $condition) {
                if (!$condition['attribute'] || !isset($condition['value']) || is_null($condition['value']) || in_array($condition['attribute'], $applied_attributes)) {
                    continue;
                }
                $applied_attributes[] = $condition['attribute'];
                $chunks = explode('|', $condition['attribute']);
                $query = $this->add_attribute_to_select(end($chunks), $query);
            }
            return $query;
        })->get();
        $validated_product_ids = [];
        foreach ($products as $product) {
            if (!$product->get_type_instance()->price_rule_can_be_applied()) {
                continue;
            }
            if ($this->validator->validate($rule, $product)) {
                if ($product->get_type_instance()->is_composite()) {
                    $validated_product_ids = array_merge($validated_product_ids, $product->get_type_instance()->get_children_ids());
                } else {
                    $validated_product_ids[] = $product->id;
                }
            }
        }
        return array_unique($validated_product_ids);
    }
    /**
     * Returns catalog rule products
     *
     * @param  \Webkul\Product\Contracts\Product  $product
     * @return \Illuminate\Support\Collection
     */
    public function get_catalog_rule_products($product = null)
    {
        $rule_products = $this->catalog_rule_product_repository->scope_query(function ($query) use ($product) {
            $query = $query->distinct()->select('catalog_rule_products.*')->left_join('products', 'catalog_rule_products.product_id', '=', 'products.id')->order_by('channel_id', 'asc')->order_by('customer_group_id', 'asc')->order_by('product_id', 'asc')->order_by('sort_order', 'asc')->order_by('catalog_rule_id', 'asc');
            $query = $this->add_attribute_to_select('price', $query);
            if (!$product) {
                return $query;
            }
            if (!$product->get_type_instance()->price_rule_can_be_applied()) {
                return $query;
            }
            if ($product->get_type_instance()->is_composite()) {
                $query->where_in('catalog_rule_products.product_id', $product->get_type_instance()->get_children_ids());
            } else {
                $query->where('catalog_rule_products.product_id', $product->id);
            }
            return $query;
        })->get();
        return $rule_products;
    }
    /**
     * Add product attribute condition to query
     *
     * @param  string  $attributeCode
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function add_attribute_to_select($attribute_code, $query)
    {
        $attribute = $this->attribute_repository->find_one_by_field('code', $attribute_code);
        if (!$attribute) {
            return $query;
        }
        $query->left_join('product_attribute_values as ' . 'pav_' . $attribute->code, function ($qb) use ($attribute) {
            $qb->where('pav_' . $attribute->code . '.channel', $attribute->value_per_channel ? core()->get_default_channel_code() : null)->where('pav_' . $attribute->code . '.locale', $attribute->value_per_locale ? app()->get_locale() : null);
            $qb->on('products.id', 'pav_' . $attribute->code . '.product_id')->where('pav_' . $attribute->code . '.attribute_id', $attribute->id);
        });
        $query->add_select('pav_' . $attribute->code . '.' . $attribute->column_name . ' as ' . $attribute->code);
        return $query;
    }
}