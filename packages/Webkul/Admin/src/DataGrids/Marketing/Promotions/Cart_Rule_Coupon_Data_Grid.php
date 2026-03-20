<?php

declare (strict_types=1);
namespace Webkul\Admin\Data_Grids\Marketing\Promotions;

use Illuminate\Support\Facades\DB;
use Webkul\Data_Grid\Data_Grid;
class Cart_Rule_Coupon_Data_Grid extends Data_Grid
{
    /**
     * Prepare query builder.
     *
     * @return \Illuminate\Database\Query\Builder
     */
    public function prepare_query_builder()
    {
        $query_builder = DB::table('cart_rule_coupons')->select('id', 'code', 'created_at', 'expired_at', 'times_used')->where('cart_rule_coupons.cart_rule_id', request('id'));
        return $query_builder;
    }
    /**
     * Add columns.
     *
     * @return void
     */
    public function prepare_columns()
    {
        $this->add_column(['index' => 'id', 'label' => trans('admin::app.marketing.promotions.cart-rules-coupons.datagrid.id'), 'type' => 'integer', 'filterable' => true, 'sortable' => true]);
        $this->add_column(['index' => 'code', 'label' => trans('admin::app.marketing.promotions.cart-rules-coupons.datagrid.coupon-code'), 'type' => 'string', 'searchable' => true, 'filterable' => true, 'sortable' => true]);
        $this->add_column(['index' => 'created_at', 'label' => trans('admin::app.marketing.promotions.cart-rules-coupons.datagrid.created-date'), 'type' => 'datetime', 'filterable' => true, 'filterable_type' => 'datetime_range', 'sortable' => true]);
        $this->add_column(['index' => 'expired_at', 'label' => trans('admin::app.marketing.promotions.cart-rules-coupons.datagrid.expiration-date'), 'type' => 'datetime', 'filterable' => true, 'filterable_type' => 'datetime_range', 'sortable' => true]);
        $this->add_column(['index' => 'times_used', 'label' => trans('admin::app.marketing.promotions.cart-rules-coupons.datagrid.times-used'), 'type' => 'integer', 'searchable' => true, 'filterable' => true, 'sortable' => true]);
    }
    /**
     * Prepare actions.
     *
     * @return void
     */
    public function prepare_actions()
    {
        $this->add_action(['icon' => 'icon-delete', 'title' => trans('admin::app.marketing.promotions.catalog-rules.index.datagrid.delete'), 'method' => 'DELETE', 'url' => function ($row) {
            return route('admin.marketing.promotions.cart_rules.coupons.delete', $row->id);
        }]);
    }
    /**
     * Prepare mass actions.
     *
     * @return void
     */
    public function prepare_mass_actions()
    {
        $this->add_mass_action(['title' => trans('admin::app.marketing.promotions.cart-rules-coupons.datagrid.delete'), 'method' => 'POST', 'url' => route('admin.marketing.promotions.cart_rules.coupons.mass_delete')]);
    }
}