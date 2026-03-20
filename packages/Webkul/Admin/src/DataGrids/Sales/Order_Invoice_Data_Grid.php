<?php

declare (strict_types=1);
namespace Webkul\Admin\Data_Grids\Sales;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Webkul\Data_Grid\Data_Grid;
use Webkul\Sales\Models\Invoice;
class Order_Invoice_Data_Grid extends Data_Grid
{
    /**
     * Prepare query builder.
     *
     * @return \Illuminate\Database\Query\Builder
     */
    public function prepare_query_builder()
    {
        $table_prefix = DB::get_table_prefix();
        $query_builder = DB::table('invoices')->left_join('orders', 'invoices.order_id', '=', 'orders.id')->select('invoices.id as id', 'orders.increment_id as order_id', 'invoices.state as state', 'invoices.base_grand_total as base_grand_total', 'invoices.created_at as created_at')->select_raw("CASE WHEN {$table_prefix}invoices.increment_id IS NOT NULL THEN {$table_prefix}invoices.increment_id ELSE {$table_prefix}invoices.id END AS increment_id");
        $this->add_filter('increment_id', 'invoices.increment_id');
        $this->add_filter('order_id', 'orders.increment_id');
        $this->add_filter('base_grand_total', 'invoices.base_grand_total');
        $this->add_filter('created_at', 'invoices.created_at');
        return $query_builder;
    }
    /**
     * Add columns.
     *
     * @return void
     */
    public function prepare_columns()
    {
        $this->add_column(['index' => 'increment_id', 'label' => trans('admin::app.sales.invoices.index.datagrid.id'), 'type' => 'string', 'filterable' => true, 'sortable' => true]);
        $this->add_column(['index' => 'order_id', 'label' => trans('admin::app.sales.invoices.index.datagrid.order-id'), 'type' => 'string', 'searchable' => true, 'filterable' => true, 'sortable' => true]);
        $this->add_column(['index' => 'base_grand_total', 'label' => trans('admin::app.sales.invoices.index.datagrid.grand-total'), 'type' => 'string', 'searchable' => true, 'filterable' => true, 'sortable' => true, 'closure' => function ($row) {
            return core()->format_base_price($row->base_grand_total);
        }]);
        $this->add_column(['index' => 'state', 'label' => trans('admin::app.sales.invoices.index.datagrid.status'), 'type' => 'string', 'searchable' => true, 'filterable' => true, 'sortable' => true, 'closure' => function ($row) {
            $due_duration = core()->get_config_data('sales.invoice_settings.payment_terms.due_duration');
            $today_date = Carbon::now();
            $due_date = Carbon::parse($row->created_at)->add_days($due_duration);
            if ($row->state == Invoice::STATUS_PAID) {
                return '<p class="label-active">' . trans('admin::app.sales.invoices.index.datagrid.paid') . '</p>';
            }
            if ($row->state == Invoice::STATUS_PENDING || $row->state == Invoice::STATUS_PENDING_PAYMENT) {
                $days_left = $today_date->diff_in_days($due_date, false);
                if ($days_left >= 0) {
                    $extra = trans('admin::app.sales.invoices.index.datagrid.days-left', ['count' => $days_left]);
                } else {
                    $extra = trans('admin::app.sales.invoices.index.datagrid.overdue-by', ['count' => abs($days_left)]);
                }
                return '<div class="flex flex-col gap-1"><p class="label-pending">' . trans('admin::app.sales.invoices.index.datagrid.pending') . '</p><p class="block text-xs italic leading-5 text-red-600 dark:text-gray-300">' . $extra . '</p></div>';
            }
            if ($row->state == Invoice::STATUS_OVERDUE) {
                $days_overdue = $due_date->diff_in_days($today_date, false);
                if ($days_overdue >= 0) {
                    $extra = trans('admin::app.sales.invoices.index.datagrid.days-overdue', ['count' => $days_overdue]);
                } else {
                    $extra = '';
                }
                return '<div class="flex flex-col gap-1"><p class="label-canceled">' . trans('admin::app.sales.invoices.index.datagrid.overdue') . '</p><p class="block text-xs italic leading-5 text-red-600 dark:text-gray-300">' . $extra . '</p></div>';
            }
            return $row->state;
        }]);
        $this->add_column(['index' => 'created_at', 'label' => trans('admin::app.sales.invoices.index.datagrid.invoice-date'), 'type' => 'date', 'searchable' => true, 'filterable' => true, 'filterable_type' => 'date_range', 'sortable' => true]);
    }
    /**
     * Prepare actions.
     *
     * @return void
     */
    public function prepare_actions()
    {
        if (bouncer()->has_permission('sales.invoices.view')) {
            $this->add_action(['icon' => 'icon-view', 'title' => trans('admin::app.sales.invoices.index.datagrid.view'), 'method' => 'GET', 'url' => function ($row) {
                return route('admin.sales.invoices.view', $row->id);
            }]);
        }
    }
    /**
     * Prepare mass actions.
     *
     * @return void
     */
    public function prepare_mass_actions()
    {
        $this->add_mass_action(['title' => trans('admin::app.sales.invoices.index.datagrid.update-status'), 'url' => route('admin.sales.invoices.mass_update.state'), 'method' => 'POST', 'options' => [['label' => trans('admin::app.sales.invoices.index.datagrid.pending'), 'value' => Invoice::STATUS_PENDING], ['label' => trans('admin::app.sales.invoices.index.datagrid.paid'), 'value' => Invoice::STATUS_PAID], ['label' => trans('admin::app.sales.invoices.index.datagrid.overdue'), 'value' => Invoice::STATUS_OVERDUE]]]);
    }
}