<?php

declare (strict_types=1);
namespace Webkul\Admin\Data_Grids\Sales;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Webkul\Data_Grid\Data_Grid;
class Booking_Data_Grid extends Data_Grid
{
    /**
     * Prepare query builder.
     *
     * @return \Illuminate\Database\Query\Builder
     */
    public function prepare_query_builder()
    {
        $query_builder = DB::table('bookings')->left_join('orders', 'bookings.order_id', '=', 'orders.id')->select('bookings.id as id', 'orders.increment_id as order_id', 'bookings.from as from', 'bookings.to as to', 'bookings.qty as qty', 'orders.created_at as created_at');
        $this->add_filter('id', 'bookings.id');
        $this->add_filter('order_id', 'orders.increment_id');
        $this->add_filter('qty', 'bookings.qty');
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
        $this->add_column(['index' => 'id', 'label' => trans('admin::app.sales.booking.index.datagrid.id'), 'type' => 'string', 'searchable' => false, 'sortable' => true, 'filterable' => true]);
        $this->add_column(['index' => 'order_id', 'label' => trans('admin::app.sales.booking.index.datagrid.order-id'), 'type' => 'string', 'searchable' => true, 'sortable' => true, 'filterable' => true]);
        $this->add_column(['index' => 'qty', 'label' => trans('admin::app.sales.booking.index.datagrid.qty'), 'type' => 'string', 'searchable' => true, 'sortable' => true, 'filterable' => true]);
        $this->add_column(['index' => 'from', 'label' => trans('admin::app.sales.booking.index.datagrid.from'), 'type' => 'datetime', 'searchable' => true, 'sortable' => true, 'filterable' => true, 'filterable_type' => 'datetime_range', 'closure' => function ($value) {
            return Carbon::create_from_timestamp($value->from)->format('d M, Y H:iA');
        }]);
        $this->add_column(['index' => 'to', 'label' => trans('admin::app.sales.booking.index.datagrid.to'), 'type' => 'datetime', 'searchable' => true, 'sortable' => true, 'filterable' => true, 'filterable_type' => 'datetime_range', 'closure' => function ($value) {
            return Carbon::create_from_timestamp($value->to)->format('d M, Y H:iA');
        }]);
        $this->add_column(['index' => 'created_at', 'label' => trans('admin::app.sales.booking.index.datagrid.created-date'), 'type' => 'datetime', 'searchable' => true, 'sortable' => true, 'filterable' => true, 'filterable_type' => 'datetime_range']);
    }
    /**
     * Prepare actions.
     *
     * @return void
     */
    public function prepare_actions()
    {
        $this->add_action(['icon' => 'icon-view', 'title' => trans('admin::app.sales.booking.index.datagrid.view'), 'method' => 'GET', 'url' => function ($row) {
            return route('admin.sales.orders.view', $row->order_id);
        }]);
    }
}