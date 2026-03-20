<?php

declare (strict_types=1);
namespace Webkul\Admin\Data_Grids\Marketing\Promotions;

use Illuminate\Support\Facades\DB;
use Webkul\Data_Grid\Data_Grid;
class Cart_Rule_Data_Grid extends Data_Grid
{
    /**
     * Prepare query builder.
     *
     * @return \Illuminate\Database\Query\Builder
     */
    public function prepare_query_builder()
    {
        $query_builder = DB::table('cart_rules')->distinct()->left_join('cart_rule_coupons', function ($left_join) {
            $left_join->on('cart_rule_coupons.cart_rule_id', '=', 'cart_rules.id')->where('cart_rule_coupons.is_primary', 1);
        })->select('cart_rules.id', 'name', 'cart_rule_coupons.code as coupon_code', 'status', 'starts_from', 'ends_till', 'sort_order');
        $this->add_filter('id', 'cart_rules.id');
        $this->add_filter('coupon_code', 'cart_rule_coupons.code');
        return $query_builder;
    }
    /**
     * Add columns.
     *
     * @return void
     */
    public function prepare_columns()
    {
        $this->add_column(['index' => 'id', 'label' => trans('admin::app.marketing.promotions.cart-rules.index.datagrid.id'), 'type' => 'integer', 'filterable' => true, 'sortable' => true]);
        $this->add_column(['index' => 'name', 'label' => trans('admin::app.marketing.promotions.cart-rules.index.datagrid.name'), 'type' => 'string', 'searchable' => true, 'filterable' => true, 'sortable' => true]);
        $this->add_column(['index' => 'coupon_code', 'label' => trans('admin::app.marketing.promotions.cart-rules.index.datagrid.coupon-code'), 'type' => 'string', 'searchable' => true, 'filterable' => true, 'sortable' => true, 'closure' => function ($value) {
            return $value->coupon_code ?? '-';
        }]);
        $this->add_column(['index' => 'starts_from', 'label' => trans('admin::app.marketing.promotions.cart-rules.index.datagrid.start'), 'type' => 'datetime', 'filterable' => true, 'filterable_type' => 'datetime_range', 'sortable' => true, 'closure' => function ($value) {
            return $value->starts_from ?? '-';
        }]);
        $this->add_column(['index' => 'ends_till', 'label' => trans('admin::app.marketing.promotions.cart-rules.index.datagrid.end'), 'type' => 'datetime', 'filterable' => true, 'filterable_type' => 'datetime_range', 'sortable' => true, 'closure' => function ($value) {
            return $value->ends_till ?? '-';
        }]);
        $this->add_column(['index' => 'status', 'label' => trans('admin::app.marketing.promotions.cart-rules.index.datagrid.status'), 'type' => 'boolean', 'searchable' => true, 'filterable' => true, 'filterable_options' => [['label' => trans('admin::app.marketing.promotions.cart-rules.index.datagrid.active'), 'value' => 1], ['label' => trans('admin::app.marketing.promotions.cart-rules.index.datagrid.inactive'), 'value' => 0]], 'sortable' => true, 'closure' => function ($value) {
            if ($value->status == 1) {
                return trans('admin::app.marketing.promotions.cart-rules.index.datagrid.active');
            }
            return trans('admin::app.marketing.promotions.cart-rules.index.datagrid.inactive');
        }]);
        $this->add_column(['index' => 'sort_order', 'label' => trans('admin::app.marketing.promotions.cart-rules.index.datagrid.priority'), 'type' => 'integer', 'searchable' => true, 'filterable' => true, 'sortable' => true]);
    }
    /**
     * Prepare actions.
     *
     * @return void
     */
    public function prepare_actions()
    {
        if (bouncer()->has_permission('marketing.promotions.cart_rules.edit')) {
            $this->add_action(['icon' => 'icon-edit', 'title' => trans('admin::app.marketing.promotions.cart-rules.index.datagrid.edit'), 'method' => 'GET', 'url' => function ($row) {
                return route('admin.marketing.promotions.cart_rules.edit', $row->id);
            }]);
        }
        if (bouncer()->has_permission('marketing.promotions.cart_rules.copy')) {
            $this->add_action(['icon' => 'icon-copy', 'title' => trans('admin::app.marketing.promotions.cart-rules.index.datagrid.copy'), 'method' => 'GET', 'url' => function ($row) {
                return route('admin.marketing.promotions.cart_rules.copy', $row->id);
            }]);
        }
        if (bouncer()->has_permission('marketing.promotions.cart_rules.delete')) {
            $this->add_action(['icon' => 'icon-delete', 'title' => trans('admin::app.marketing.promotions.cart-rules.index.datagrid.delete'), 'method' => 'DELETE', 'url' => function ($row) {
                return route('admin.marketing.promotions.cart_rules.delete', $row->id);
            }]);
        }
    }
}