<?php

declare (strict_types=1);
namespace Webkul\Admin\Data_Grids\Sales;

use Illuminate\Support\Facades\DB;
use Webkul\Data_Grid\Data_Grid;
use Webkul\Sales\Models\Order_Address;
class Order_Shipment_Data_Grid extends Data_Grid
{
    /**
     * Shipment Id.
     *
     * @var string
     */
    protected $primary_column = 'shipment_id';
    /**
     * Prepare query builder.
     *
     * @return \Illuminate\Database\Query\Builder
     */
    public function prepare_query_builder()
    {
        $query_builder = DB::table('shipments')->left_join('addresses as order_address_shipping', function ($left_join) {
            $left_join->on('order_address_shipping.order_id', '=', 'shipments.order_id')->where('order_address_shipping.address_type', Order_Address::ADDRESS_TYPE_SHIPPING);
        })->left_join('orders', 'shipments.order_id', '=', 'orders.id')->left_join('inventory_sources', 'shipments.inventory_source_id', '=', 'inventory_sources.id')->select('shipments.id as shipment_id', 'orders.increment_id as shipment_order_id', 'shipments.total_qty as shipment_total_qty', 'orders.created_at as order_date', 'shipments.created_at as shipment_created_at')->add_select(DB::raw('CONCAT(' . DB::get_table_prefix() . 'order_address_shipping.first_name, " ", ' . DB::get_table_prefix() . 'order_address_shipping.last_name) as shipped_to'))->select_raw('IF(' . DB::get_table_prefix() . 'shipments.inventory_source_id IS NOT NULL,' . DB::get_table_prefix() . 'inventory_sources.name, ' . DB::get_table_prefix() . 'shipments.inventory_source_name) as inventory_source_name');
        $this->add_filter('shipment_id', 'shipments.id');
        $this->add_filter('shipment_order_id', 'orders.increment_id');
        $this->add_filter('shipment_total_qty', 'shipments.total_qty');
        $this->add_filter('inventory_source_name', DB::raw('IF(' . DB::get_table_prefix() . 'shipments.inventory_source_id IS NOT NULL,' . DB::get_table_prefix() . 'inventory_sources.name, ' . DB::get_table_prefix() . 'shipments.inventory_source_name)'));
        $this->add_filter('order_date', 'orders.created_at');
        $this->add_filter('shipment_created_at', 'shipments.created_at');
        $this->add_filter('shipped_to', DB::raw('CONCAT(' . DB::get_table_prefix() . 'order_address_shipping.first_name, " ", ' . DB::get_table_prefix() . 'order_address_shipping.last_name)'));
        return $query_builder;
    }
    /**
     * Add Columns.
     *
     * @return void
     */
    public function prepare_columns()
    {
        $this->add_column(['index' => 'shipment_id', 'label' => trans('admin::app.sales.shipments.index.datagrid.id'), 'type' => 'integer', 'filterable' => true, 'sortable' => true]);
        $this->add_column(['index' => 'shipment_order_id', 'label' => trans('admin::app.sales.shipments.index.datagrid.order-id'), 'type' => 'string', 'searchable' => true, 'filterable' => true, 'sortable' => true]);
        $this->add_column(['index' => 'shipment_total_qty', 'label' => trans('admin::app.sales.shipments.index.datagrid.total-qty'), 'type' => 'integer', 'filterable' => true, 'sortable' => true]);
        $this->add_column(['index' => 'inventory_source_name', 'label' => trans('admin::app.sales.shipments.index.datagrid.inventory-source'), 'type' => 'string', 'searchable' => true, 'filterable' => true, 'sortable' => true]);
        $this->add_column(['index' => 'shipped_to', 'label' => trans('admin::app.sales.shipments.index.datagrid.shipment-to'), 'type' => 'string', 'searchable' => true, 'filterable' => true, 'sortable' => true]);
        $this->add_column(['index' => 'order_date', 'label' => trans('admin::app.sales.shipments.index.datagrid.order-date'), 'type' => 'date', 'filterable' => true, 'filterable_type' => 'date_range', 'sortable' => true]);
        $this->add_column(['index' => 'shipment_created_at', 'label' => trans('admin::app.sales.shipments.index.datagrid.shipment-date'), 'type' => 'date', 'filterable' => true, 'filterable_type' => 'date_range', 'sortable' => true]);
    }
    /**
     * Prepare actions.
     *
     * @return void
     */
    public function prepare_actions()
    {
        if (bouncer()->has_permission('sales.shipments.view')) {
            $this->add_action(['icon' => 'icon-view', 'title' => trans('admin::app.sales.shipments.index.datagrid.view'), 'method' => 'GET', 'url' => function ($row) {
                return route('admin.sales.shipments.view', $row->shipment_id);
            }]);
        }
    }
}