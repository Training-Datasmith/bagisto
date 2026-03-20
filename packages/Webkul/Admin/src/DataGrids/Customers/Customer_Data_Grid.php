<?php

declare (strict_types=1);
namespace Webkul\Admin\Data_Grids\Customers;

use Illuminate\Support\Facades\DB;
use Webkul\Customer\Repositories\Customer_Group_Repository;
use Webkul\Data_Grid\Data_Grid;
use Webkul\Sales\Models\Order;
use Webkul\Sales\Repositories\Order_Repository;
class Customer_Data_Grid extends Data_Grid
{
    /**
     * Index.
     *
     * @var string
     */
    protected $primary_column = 'customer_id';
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct(protected Customer_Group_Repository $customer_group_repository)
    {
    }
    /**
     * Prepare query builder.
     *
     * @return \Illuminate\Database\Query\Builder
     */
    public function prepare_query_builder()
    {
        $table_prefix = DB::get_table_prefix();
        $query_builder = DB::table('customers')->left_join('addresses', function ($join) {
            $join->on('customers.id', '=', 'addresses.customer_id')->where('addresses.address_type', '=', 'customer');
        })->left_join('orders', 'customers.id', '=', 'orders.customer_id')->left_join('customer_groups', 'customers.customer_group_id', '=', 'customer_groups.id')->add_select('customers.id as customer_id', 'customers.email', 'customers.phone', 'customers.gender', 'customers.status', 'customers.is_suspended', 'customer_groups.name as group', 'customers.channel_id')->add_select(DB::raw('COUNT(DISTINCT ' . $table_prefix . 'addresses.id) as address_count'))->add_select(DB::raw('COUNT(DISTINCT ' . $table_prefix . 'orders.id) as order_count'))->add_select(DB::raw('CONCAT(' . $table_prefix . 'customers.first_name, " ", ' . $table_prefix . 'customers.last_name) as full_name'))->group_by('customers.id');
        $this->add_filter('channel_id', 'customers.channel_id');
        $this->add_filter('customer_id', 'customers.id');
        $this->add_filter('email', 'customers.email');
        $this->add_filter('full_name', DB::raw('CONCAT(' . $table_prefix . 'customers.first_name, " ", ' . $table_prefix . 'customers.last_name)'));
        $this->add_filter('group', 'customer_groups.name');
        $this->add_filter('phone', 'customers.phone');
        $this->add_filter('status', 'customers.status');
        return $query_builder;
    }
    /**
     * Add columns.
     *
     * @return void
     */
    public function prepare_columns()
    {
        $all_channels = core()->get_all_channels();
        $this->add_column(['index' => 'channel_id', 'label' => trans('admin::app.customers.customers.index.datagrid.channel'), 'type' => 'string', 'filterable' => true, 'filterable_type' => 'dropdown', 'filterable_options' => collect($all_channels)->map(fn($channel) => ['label' => $channel->name, 'value' => $channel->id])->values()->to_array(), 'sortable' => true, 'closure' => function ($row) use ($all_channels) {
            $channel = $all_channels->first_where('id', $row->channel_id);
            return $channel ? $channel->name : '-';
        }]);
        $this->add_column(['index' => 'customer_id', 'label' => trans('admin::app.customers.customers.index.datagrid.id'), 'type' => 'integer', 'filterable' => true]);
        $this->add_column(['index' => 'full_name', 'label' => trans('admin::app.customers.customers.index.datagrid.name'), 'type' => 'string', 'searchable' => true, 'filterable' => true, 'sortable' => true]);
        $this->add_column(['index' => 'email', 'label' => trans('admin::app.customers.customers.index.datagrid.email'), 'type' => 'string', 'searchable' => true, 'filterable' => true, 'sortable' => true]);
        $this->add_column(['index' => 'phone', 'label' => trans('admin::app.customers.customers.index.datagrid.phone'), 'type' => 'integer', 'filterable' => true]);
        $this->add_column(['index' => 'status', 'label' => trans('admin::app.customers.customers.index.datagrid.status'), 'type' => 'boolean', 'filterable' => true, 'filterable_options' => [['label' => trans('admin::app.customers.customers.index.datagrid.active'), 'value' => 1], ['label' => trans('admin::app.customers.customers.index.datagrid.inactive'), 'value' => 0]], 'sortable' => true]);
        $this->add_column(['index' => 'gender', 'label' => trans('admin::app.customers.customers.index.datagrid.gender'), 'type' => 'string', 'sortable' => true]);
        $this->add_column(['index' => 'group', 'label' => trans('admin::app.customers.customers.index.datagrid.group'), 'type' => 'string', 'filterable' => true, 'filterable_type' => 'dropdown', 'filterable_options' => $this->customer_group_repository->all(['name as label', 'name as value'])->to_array()]);
        $this->add_column(['index' => 'is_suspended', 'label' => trans('admin::app.customers.customers.index.datagrid.suspended'), 'type' => 'boolean', 'sortable' => true]);
        $this->add_column(['index' => 'revenue', 'label' => trans('admin::app.customers.customers.index.datagrid.revenue'), 'type' => 'integer', 'closure' => function ($row) {
            return app(Order_Repository::class)->scope_query(function ($q) use ($row) {
                return $q->where_not_in('status', [Order::STATUS_CANCELED, Order::STATUS_CLOSED])->where('customer_id', $row->customer_id);
            })->sum('base_grand_total_invoiced');
        }]);
        $this->add_column(['index' => 'order_count', 'label' => trans('admin::app.customers.customers.index.datagrid.order-count'), 'type' => 'integer', 'sortable' => true]);
        $this->add_column(['index' => 'address_count', 'label' => trans('admin::app.customers.customers.index.datagrid.address-count'), 'type' => 'integer', 'sortable' => true]);
    }
    /**
     * Prepare actions.
     *
     * @return void
     */
    public function prepare_actions()
    {
        $this->add_action(['icon' => 'icon-view', 'title' => trans('admin::app.customers.customers.index.datagrid.view'), 'method' => 'GET', 'url' => function ($row) {
            return route('admin.customers.customers.view', $row->customer_id);
        }]);
        $this->add_action(['icon' => 'icon-exit', 'title' => trans('admin::app.customers.customers.index.datagrid.login-as-customer'), 'method' => 'GET', 'target' => 'blank', 'url' => function ($row) {
            return route('admin.customers.customers.login_as_customer', $row->customer_id);
        }]);
    }
    /**
     * Prepare mass actions.
     *
     * @return void
     */
    public function prepare_mass_actions()
    {
        if (bouncer()->has_permission('customers.customers.delete')) {
            $this->add_mass_action(['title' => trans('admin::app.customers.customers.index.datagrid.delete'), 'method' => 'POST', 'url' => route('admin.customers.customers.mass_delete')]);
        }
        if (bouncer()->has_permission('customers.customers.edit')) {
            $this->add_mass_action(['title' => trans('admin::app.customers.customers.index.datagrid.update-status'), 'method' => 'POST', 'url' => route('admin.customers.customers.mass_update'), 'options' => [['label' => trans('admin::app.customers.customers.index.datagrid.active'), 'value' => 1], ['label' => trans('admin::app.customers.customers.index.datagrid.inactive'), 'value' => 0]]]);
        }
    }
}