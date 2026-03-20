<?php

declare (strict_types=1);
namespace Webkul\Admin\Data_Grids\Sales;

use Illuminate\Support\Facades\DB;
use Webkul\Data_Grid\Data_Grid;
use Webkul\Sales\Models\Order;
use Webkul\Sales\Models\Order_Address;
use Webkul\Sales\Repositories\Order_Repository;
class Order_Data_Grid extends Data_Grid
{
    /**
     * Prepare query builder.
     *
     * @return \Illuminate\Database\Query\Builder
     */
    public function prepare_query_builder()
    {
        $query_builder = DB::table('orders')->left_join('addresses as order_address_shipping', function ($left_join) {
            $left_join->on('order_address_shipping.order_id', '=', 'orders.id')->where('order_address_shipping.address_type', Order_Address::ADDRESS_TYPE_SHIPPING);
        })->left_join('addresses as order_address_billing', function ($left_join) {
            $left_join->on('order_address_billing.order_id', '=', 'orders.id')->where('order_address_billing.address_type', Order_Address::ADDRESS_TYPE_BILLING);
        })->left_join('order_payment', 'orders.id', '=', 'order_payment.order_id')->select('orders.id', DB::raw('GROUP_CONCAT(' . DB::get_table_prefix() . 'order_payment.method SEPARATOR "|") as method'), 'orders.increment_id', 'orders.base_grand_total', 'orders.created_at', 'channel_name', 'channel_id', 'status', 'customer_email', 'orders.cart_id as items', DB::raw('CONCAT(' . DB::get_table_prefix() . 'orders.customer_first_name, " ", ' . DB::get_table_prefix() . 'orders.customer_last_name) as full_name'), DB::raw('CONCAT(' . DB::get_table_prefix() . 'order_address_billing.city, ", ", ' . DB::get_table_prefix() . 'order_address_billing.state,", ", ' . DB::get_table_prefix() . 'order_address_billing.country) as location'))->group_by('orders.id');
        $this->add_filter('full_name', DB::raw('CONCAT(' . DB::get_table_prefix() . 'orders.customer_first_name, " ", ' . DB::get_table_prefix() . 'orders.customer_last_name)'));
        $this->add_filter('created_at', 'orders.created_at');
        return $query_builder;
    }
    /**
     * Add columns.
     *
     * @return void
     */
    public function prepare_columns()
    {
        $this->add_column(['index' => 'increment_id', 'label' => trans('admin::app.sales.orders.index.datagrid.order-id'), 'type' => 'string', 'searchable' => true, 'filterable' => true, 'sortable' => true]);
        $this->add_column(['index' => 'status', 'label' => trans('admin::app.sales.orders.index.datagrid.status'), 'type' => 'string', 'searchable' => true, 'filterable' => true, 'filterable_type' => 'dropdown', 'filterable_options' => [['label' => trans('admin::app.sales.orders.index.datagrid.processing'), 'value' => Order::STATUS_PROCESSING], ['label' => trans('admin::app.sales.orders.index.datagrid.completed'), 'value' => Order::STATUS_COMPLETED], ['label' => trans('admin::app.sales.orders.index.datagrid.canceled'), 'value' => Order::STATUS_CANCELED], ['label' => trans('admin::app.sales.orders.index.datagrid.closed'), 'value' => Order::STATUS_CLOSED], ['label' => trans('admin::app.sales.orders.index.datagrid.pending'), 'value' => Order::STATUS_PENDING], ['label' => trans('admin::app.sales.orders.index.datagrid.pending-payment'), 'value' => Order::STATUS_PENDING_PAYMENT], ['label' => trans('admin::app.sales.orders.index.datagrid.fraud'), 'value' => Order::STATUS_FRAUD]], 'sortable' => true, 'closure' => function ($row) {
            switch ($row->status) {
                case Order::STATUS_PROCESSING:
                    return '<p class="label-processing">' . trans('admin::app.sales.orders.index.datagrid.processing') . '</p>';
                case Order::STATUS_COMPLETED:
                    return '<p class="label-active">' . trans('admin::app.sales.orders.index.datagrid.completed') . '</p>';
                case Order::STATUS_CANCELED:
                    return '<p class="label-canceled">' . trans('admin::app.sales.orders.index.datagrid.canceled') . '</p>';
                case Order::STATUS_CLOSED:
                    return '<p class="label-closed">' . trans('admin::app.sales.orders.index.datagrid.closed') . '</p>';
                case Order::STATUS_PENDING:
                    return '<p class="label-pending">' . trans('admin::app.sales.orders.index.datagrid.pending') . '</p>';
                case Order::STATUS_PENDING_PAYMENT:
                    return '<p class="label-pending">' . trans('admin::app.sales.orders.index.datagrid.pending-payment') . '</p>';
                case Order::STATUS_FRAUD:
                    return '<p class="label-canceled">' . trans('admin::app.sales.orders.index.datagrid.fraud') . '</p>';
            }
        }]);
        $this->add_column(['index' => 'base_grand_total', 'label' => trans('admin::app.sales.orders.index.datagrid.grand-total'), 'type' => 'string', 'filterable' => true, 'sortable' => true]);
        $this->add_column(['index' => 'method', 'label' => trans('admin::app.sales.orders.index.datagrid.pay-via'), 'type' => 'string', 'closure' => function ($row) {
            return collect(explode('|', $row->method))->map(fn($method) => core()->get_config_data('sales.payment_methods.' . $method . '.title'))->filter()->unique()->join(', ');
        }]);
        $this->add_column(['index' => 'channel_id', 'label' => trans('admin::app.sales.orders.index.datagrid.channel-name'), 'type' => 'string', 'filterable' => true, 'filterable_type' => 'dropdown', 'filterable_options' => core()->get_all_channels()->map(fn($channel) => ['label' => $channel->name, 'value' => $channel->id])->values()->to_array()]);
        $this->add_column(['index' => 'full_name', 'label' => trans('admin::app.sales.orders.index.datagrid.customer'), 'type' => 'string', 'searchable' => true, 'filterable' => true, 'sortable' => true]);
        /**
         * Searchable dropdown sample. In testing phase.
         */
        $this->add_column(['index' => 'customer_email', 'label' => trans('admin::app.sales.orders.index.datagrid.email'), 'type' => 'string', 'searchable' => true, 'filterable' => true, 'sortable' => true]);
        $this->add_column(['index' => 'location', 'label' => trans('admin::app.sales.orders.index.datagrid.location'), 'type' => 'string']);
        $this->add_column(['index' => 'items', 'label' => trans('admin::app.sales.orders.index.datagrid.items'), 'type' => 'string', 'exportable' => false, 'closure' => function ($value) {
            $order = app(Order_Repository::class)->with('items')->find($value->id);
            return view('admin::sales.orders.items', compact('order'))->render();
        }]);
        $this->add_column(['index' => 'created_at', 'label' => trans('admin::app.sales.orders.index.datagrid.date'), 'type' => 'date', 'filterable' => true, 'filterable_type' => 'date_range', 'sortable' => true]);
    }
    /**
     * Prepare actions.
     *
     * @return void
     */
    public function prepare_actions()
    {
        if (bouncer()->has_permission('sales.orders.view')) {
            $this->add_action(['icon' => 'icon-view', 'title' => trans('admin::app.sales.orders.index.datagrid.view'), 'method' => 'GET', 'url' => function ($row) {
                return route('admin.sales.orders.view', $row->id);
            }]);
        }
    }
}