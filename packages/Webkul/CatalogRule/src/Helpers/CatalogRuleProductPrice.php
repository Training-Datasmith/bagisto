<?php

declare (strict_types=1);
namespace Webkul\Catalog_Rule\Helpers;

use Carbon\Carbon;
use Webkul\Catalog_Rule\Repositories\Catalog_Rule_Product_Price_Repository;
class Catalog_Rule_Product_Price
{
    /**
     * Create a new helper instance.
     *
     * @return void
     */
    public function __construct(protected Catalog_Rule_Product_Price_Repository $catalog_rule_product_price_repository, protected Catalog_Rule_Product $catalog_rule_product_helper)
    {
    }
    /**
     * Collect discount on cart
     *
     * @param  int  $batchCount
     * @param  \Webkul\Product\Contracts\Product  $product
     * @return void
     */
    public function index_rule_product_price($batch_count, $product = null)
    {
        $dates = ['current' => $current_date = Carbon::now(), 'previous' => (clone $current_date)->sub_days('1')->set_time(23, 59, 59), 'next' => (clone $current_date)->add_days('1')->set_time(0, 0, 0)];
        $prices = $end_rule_flags = [];
        $previous_key = null;
        $catalog_rule_products = $this->catalog_rule_product_helper->get_catalog_rule_products($product);
        foreach ($catalog_rule_products as $row) {
            $product_key = $row->product_id . '-' . $row->channel_id . '-' . $row->customer_group_id;
            if ($previous_key && $previous_key != $product_key) {
                $end_rule_flags = [];
                if (count($prices) > $batch_count) {
                    $this->catalog_rule_product_price_repository->insert($prices);
                    $prices = [];
                }
            }
            foreach ($dates as $key => $date) {
                if ((!$row->starts_from || $date >= $row->starts_from) && (!$row->ends_till || $date <= $row->ends_till)) {
                    $price_key = $date->get_timestamp() . '-' . $product_key;
                    if (isset($end_rule_flags[$price_key])) {
                        continue;
                    }
                    if (!isset($prices[$price_key])) {
                        $prices[$price_key] = ['rule_date' => $date, 'catalog_rule_id' => $row->catalog_rule_id, 'channel_id' => $row->channel_id, 'customer_group_id' => $row->customer_group_id, 'product_id' => $row->product_id, 'price' => $this->calculate($row), 'starts_from' => $row->starts_from, 'ends_till' => $row->ends_till];
                    } else {
                        $prices[$price_key]['price'] = $this->calculate($row, $prices[$price_key]);
                        $prices[$price_key]['starts_from'] = max($prices[$price_key]['starts_from'], $row->starts_from);
                        $prices[$price_key]['ends_till'] = min($prices[$price_key]['ends_till'], $row->ends_till);
                    }
                    if ($row->end_other_rules) {
                        $end_rule_flags[$price_key] = true;
                    }
                }
            }
            $previous_key = $product_key;
        }
        $this->catalog_rule_product_price_repository->insert($prices);
    }
    /**
     * Calculates product price based on rule
     *
     * @param  array  $rule
     * @param  \Webkul\Product\Contracts\Product|null  $productData
     * @return float
     */
    public function calculate($rule, $product_data = null)
    {
        $price = $product_data['price'] ?? $rule->price;
        switch ($rule->action_type) {
            case 'to_fixed':
                $price = min($rule->discount_amount, $price);
                break;
            case 'to_percent':
                $price = $price * $rule->discount_amount / 100;
                break;
            case 'by_fixed':
                $price = max(0, $price - $rule->discount_amount);
                break;
            case 'by_percent':
                $price = $price * (1 - $rule->discount_amount / 100);
                break;
        }
        return $price;
    }
    /**
     * Clean products price indices
     *
     * @param  array  $productIds
     * @return void
     */
    public function clean_product_price_indices($product_ids = [])
    {
        if (count($product_ids)) {
            $this->catalog_rule_product_price_repository->where_in('product_id', $product_ids)->delete();
        } else {
            $this->catalog_rule_product_price_repository->delete_where([['product_id', 'like', '%%']]);
        }
    }
}