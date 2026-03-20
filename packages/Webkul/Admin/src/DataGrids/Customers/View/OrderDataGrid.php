<?php

declare (strict_types=1);
namespace Webkul\Admin\Data_Grids\Customers\View;

use Illuminate\Support\Facades\DB;
use Webkul\Data_Grid\Data_Grid;
use Webkul\Sales\Models\Order;
use Webkul\Sales\Models\Order_Address;
class Order_Data_Grid extends Data_Grid
{
    /**
     * Prepare query builder.
     *
     * @return void
     */
    public function prepare_query_builder()
    {
        $table_prefix = DB::get_table_prefix();
        $query_builder = DB::table('orders')->left_join('addresses as order_address_billing', function ($left_join) {
            $left_join->on('order_address_billing.order_id', '=', 'orders.id')->where('order_address_billing.address_type', Order_Address::ADDRESS_TYPE_BILLING);
        })->left_join('order_payment', 'orders.id', '=', 'order_payment.order_id')->select('orders.id', 'orders.increment_id', 'order_payment.method', 'orders.base_grand_total', 'orders.created_at', 'channel_name', 'status', 'order_address_billing.email as customer_email', 'orders.cart_id as image', DB::raw('CONCAT(' . $table_prefix . 'order_address_billing.first_name, " ", ' . $table_prefix . 'order_address_billing.last_name) as full_name'), DB::raw('CONCAT(' . $table_prefix . 'order_address_billing.address, ", ", ' . $table_prefix . 'order_address_billing.city,", ", ' . $table_prefix . 'order_address_billing.state, ", ", ' . $table_prefix . 'order_address_billing.country) as location'))->where('orders.customer_id', request()->route('id'));
        $this->add_filter('full_name', DB::raw('CONCAT(' . $table_prefix . 'orders.customer_first_name, " ", ' . $table_prefix . 'orders.customer_last_name)'));
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
        $this->add_column(['index' => 'increment_id', 'label' => trans('admin::app.customers.customers.view.datagrid.orders.order-id'), 'type' => 'string', 'searchable' => true, 'filterable' => true, 'sortable' => true]);
        $this->add_column(['index' => 'status', 'label' => trans('admin::app.customers.customers.view.datagrid.orders.status'), 'type' => 'string', 'searchable' => true, 'filterable' => true, 'filterable_type' => 'dropdown', 'filterable_options' => [['label' => trans('admin::app.customers.customers.view.datagrid.orders.processing'), 'value' => Order::STATUS_PROCESSING], ['label' => trans('admin::app.customers.customers.view.datagrid.orders.completed'), 'value' => Order::STATUS_COMPLETED], ['label' => trans('admin::app.customers.customers.view.datagrid.orders.canceled'), 'value' => Order::STATUS_CANCELED], ['label' => trans('admin::app.customers.customers.view.datagrid.orders.closed'), 'value' => Order::STATUS_CLOSED], ['label' => trans('admin::app.customers.customers.view.datagrid.orders.pending'), 'value' => Order::STATUS_PENDING], ['label' => trans('admin::app.customers.customers.view.datagrid.orders.pending-payment'), 'value' => Order::STATUS_PENDING_PAYMENT], ['label' => trans('admin::app.customers.customers.view.datagrid.orders.fraud'), 'value' => Order::STATUS_FRAUD]], 'sortable' => true, 'closure' => function ($row) {
            switch ($row->status) {
                case Order::STATUS_PROCESSING:
                    return '<p class="label-processing">' . trans('admin::app.customers.customers.view.datagrid.orders.processing') . '</p>';
                case Order::STATUS_COMPLETED:
                    return '<p class="label-active">' . trans('admin::app.customers.customers.view.datagrid.orders.completed') . '</p>';
                case Order::STATUS_CANCELED:
                    return '<p class="label-canceled">' . trans('admin::app.customers.customers.view.datagrid.orders.canceled') . '</p>';
                case Order::STATUS_CLOSED:
                    return '<p class="label-closed">' . trans('admin::app.customers.customers.view.datagrid.orders.closed') . '</p>';
                case Order::STATUS_PENDING:
                    return '<p class="label-pending">' . trans('admin::app.customers.customers.view.datagrid.orders.pending') . '</p>';
                case Order::STATUS_PENDING_PAYMENT:
                    return '<p class="label-pending">' . trans('admin::app.customers.customers.view.datagrid.orders.pending-payment') . '</p>';
                case Order::STATUS_FRAUD:
                    return '<p class="label-canceled">' . trans('admin::app.customers.customers.view.datagrid.orders.fraud') . '</p>';
            }
        }]);
        $this->add_column(['index' => 'base_grand_total', 'label' => trans('admin::app.customers.customers.view.datagrid.orders.grand-total'), 'type' => 'string', 'filterable' => true, 'sortable' => true]);
        $this->add_column(['index' => 'method', 'label' => trans('admin::app.customers.customers.view.datagrid.orders.pay-via'), 'type' => 'string', 'closure' => function ($row) {
            return core()->get_config_data('sales.payment_methods.' . $row->method . '.title');
        }]);
        $this->add_column(['index' => 'channel_name', 'label' => trans('admin::app.customers.customers.view.datagrid.orders.channel-name'), 'type' => 'string', 'searchable' => false, 'filterable' => true, 'filterable_type' => 'dropdown', 'filterable_options' => core()->get_all_channels()->map(fn($channel) => ['label' => $channel->name, 'value' => $channel->id])->values()->to_array(), 'sortable' => true]);
        $this->add_column(['index' => 'full_name', 'label' => trans('admin::app.customers.customers.view.datagrid.orders.customer-name'), 'type' => 'string', 'searchable' => true, 'sortable' => true]);
        /**
         * Searchable dropdown sample. In testing phase.
         */
        $this->add_column(['index' => 'customer_email', 'label' => trans('admin::app.customers.customers.view.datagrid.orders.email'), 'type' => 'string', 'filterable' => true, 'sortable' => true]);
        $this->add_column(['index' => 'location', 'label' => trans('admin::app.customers.customers.view.datagrid.orders.location'), 'type' => 'string']);
        $this->add_column(['index' => 'created_at', 'label' => trans('admin::app.customers.customers.view.datagrid.orders.date'), 'type' => 'date', 'filterable' => true, 'filterable_type' => 'date_range', 'sortable' => true]);
    }
    /**
     * Prepare actions.
     *
     * @return void
     */
    public function prepare_actions()
    {
        if (bouncer()->has_permission('sales.orders.view')) {
            $this->add_action(['icon' => 'icon-view', 'title' => trans('admin::app.customers.customers.view.datagrid.orders.view'), 'method' => 'GET', 'url' => function ($row) {
                return route('admin.sales.orders.view', $row->id);
            }]);
        }
    }
}