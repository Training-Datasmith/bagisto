<?php

declare (strict_types=1);
namespace Webkul\Admin\Data_Grids\Customers\View;

use Illuminate\Support\Facades\DB;
use Webkul\Data_Grid\Data_Grid;
class Invoice_Data_Grid extends Data_Grid
{
    /**
     * Prepare query builder.
     *
     * @return void
     */
    public function prepare_query_builder()
    {
        $db_prefix = DB::get_table_prefix();
        $query_builder = DB::table('invoices')->left_join('orders', 'invoices.order_id', '=', 'orders.id')->select('invoices.id as id', 'orders.increment_id as order_id', 'orders.customer_id as customer_id', 'invoices.state as state', 'invoices.base_grand_total as base_grand_total', 'invoices.created_at as created_at')->where('orders.customer_id', '=', request()->route('id'))->select_raw("CASE WHEN {$db_prefix}invoices.increment_id IS NOT NULL THEN {$db_prefix}invoices.increment_id ELSE {$db_prefix}invoices.id END AS increment_id");
        $this->add_filter('increment_id', 'invoices.increment_id');
        $this->add_filter('created_at', 'orders.created_at');
        $this->add_filter('base_grand_total', 'invoices.base_grand_total');
        return $query_builder;
    }
    /**
     * Add columns.
     *
     * @return void
     */
    public function prepare_columns()
    {
        $this->add_column(['index' => 'increment_id', 'label' => trans('admin::app.customers.customers.view.datagrid.invoices.increment-id'), 'type' => 'string', 'searchable' => true, 'filterable' => true, 'sortable' => true]);
        $this->add_column(['index' => 'created_at', 'label' => trans('admin::app.customers.customers.view.datagrid.invoices.invoice-date'), 'type' => 'date', 'searchable' => true, 'filterable' => true, 'filterable_type' => 'date_range', 'sortable' => true]);
        $this->add_column(['index' => 'base_grand_total', 'label' => trans('admin::app.customers.customers.view.datagrid.invoices.invoice-amount'), 'type' => 'string', 'searchable' => true, 'filterable' => true, 'sortable' => true]);
        $this->add_column(['index' => 'order_id', 'label' => trans('admin::app.customers.customers.view.datagrid.invoices.order-id'), 'type' => 'string', 'searchable' => true, 'filterable' => true, 'sortable' => true]);
    }
    /**
     * Prepare actions.
     *
     * @return void
     */
    public function prepare_actions()
    {
        $this->add_action(['icon' => 'icon-view', 'title' => trans('admin::app.customers.customers.view.datagrid.invoices.view'), 'method' => 'GET', 'url' => function ($row) {
            return route('admin.sales.orders.view', $row->id);
        }]);
    }
}