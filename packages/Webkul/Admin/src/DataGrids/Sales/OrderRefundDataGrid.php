<?php

declare (strict_types=1);
namespace Webkul\Admin\Data_Grids\Sales;

use Illuminate\Support\Facades\DB;
use Webkul\Data_Grid\Data_Grid;
use Webkul\Sales\Models\Order_Address;
class Order_Refund_Data_Grid extends Data_Grid
{
    /**
     * Prepare query builder.
     *
     * @return \Illuminate\Database\Query\Builder
     */
    public function prepare_query_builder()
    {
        $query_builder = DB::table('refunds')->left_join('orders', 'refunds.order_id', '=', 'orders.id')->left_join('addresses as order_address_billing', function ($left_join) {
            $left_join->on('order_address_billing.order_id', '=', 'orders.id')->where('order_address_billing.address_type', Order_Address::ADDRESS_TYPE_BILLING);
        })->select('refunds.id', 'orders.increment_id', 'refunds.state', 'refunds.base_grand_total', 'refunds.created_at')->add_select(DB::raw('CONCAT(' . DB::get_table_prefix() . 'order_address_billing.first_name, " ", ' . DB::get_table_prefix() . 'order_address_billing.last_name) as billed_to'));
        $this->add_filter('billed_to', DB::raw('CONCAT(' . DB::get_table_prefix() . 'order_address_billing.first_name, " ", ' . DB::get_table_prefix() . 'order_address_billing.last_name)'));
        $this->add_filter('id', 'refunds.id');
        $this->add_filter('increment_id', 'orders.increment_id');
        $this->add_filter('state', 'refunds.state');
        $this->add_filter('base_grand_total', 'refunds.base_grand_total');
        $this->add_filter('created_at', 'refunds.created_at');
        return $query_builder;
    }
    /**
     * Add Columns.
     *
     * @return void
     */
    public function prepare_columns()
    {
        $this->add_column(['index' => 'id', 'label' => trans('admin::app.sales.refunds.index.datagrid.id'), 'type' => 'integer', 'filterable' => true, 'sortable' => true]);
        $this->add_column(['index' => 'increment_id', 'label' => trans('admin::app.sales.refunds.index.datagrid.order-id'), 'type' => 'string', 'searchable' => true, 'filterable' => true, 'sortable' => true]);
        $this->add_column(['index' => 'base_grand_total', 'label' => trans('admin::app.sales.refunds.index.datagrid.refunded-amount'), 'type' => 'string', 'searchable' => true, 'filterable' => true, 'sortable' => true, 'closure' => function ($row) {
            return core()->format_base_price($row->base_grand_total);
        }]);
        $this->add_column(['index' => 'billed_to', 'label' => trans('admin::app.sales.refunds.index.datagrid.billed-to'), 'type' => 'string', 'searchable' => true, 'filterable' => true, 'sortable' => true]);
        $this->add_column(['index' => 'created_at', 'label' => trans('admin::app.sales.refunds.index.datagrid.refund-date'), 'type' => 'date', 'searchable' => true, 'filterable' => true, 'filterable_type' => 'date_range', 'sortable' => true]);
    }
    /**
     * Prepare actions.
     *
     * @return void
     */
    public function prepare_actions()
    {
        if (bouncer()->has_permission('sales.refunds.view')) {
            $this->add_action(['icon' => 'icon-view', 'title' => trans('admin::app.sales.refunds.index.datagrid.view'), 'method' => 'GET', 'url' => function ($row) {
                return route('admin.sales.refunds.view', $row->id);
            }]);
        }
    }
}