<?php

declare (strict_types=1);
namespace Webkul\Admin\Data_Grids\Customers;

use Illuminate\Support\Facades\DB;
use Webkul\Data_Grid\Data_Grid;
class Group_Data_Grid extends Data_Grid
{
    /**
     * Prepare query builder.
     *
     * @return \Illuminate\Database\Query\Builder
     */
    public function prepare_query_builder()
    {
        return DB::table('customer_groups')->select('id', 'code', 'name');
    }
    /**
     * Prepare columns.
     *
     * @return void
     */
    public function prepare_columns()
    {
        $this->add_column(['index' => 'id', 'label' => trans('admin::app.customers.groups.index.datagrid.id'), 'type' => 'integer', 'filterable' => true, 'sortable' => true]);
        $this->add_column(['index' => 'code', 'label' => trans('admin::app.customers.groups.index.datagrid.code'), 'type' => 'string', 'filterable' => true, 'sortable' => true]);
        $this->add_column(['index' => 'name', 'label' => trans('admin::app.customers.groups.index.datagrid.name'), 'type' => 'string', 'searchable' => true, 'filterable' => true, 'sortable' => true]);
    }
    /**
     * Prepare mass actions.
     *
     * @return void
     */
    public function prepare_actions()
    {
        if (bouncer()->has_permission('customers.groups.edit')) {
            $this->add_action(['index' => 'edit', 'icon' => 'icon-edit', 'title' => trans('admin::app.customers.groups.index.datagrid.edit'), 'method' => 'PUT', 'url' => function ($row) {
                return '';
            }]);
        }
        if (bouncer()->has_permission('customers.groups.delete')) {
            $this->add_action(['index' => 'delete', 'icon' => 'icon-delete', 'title' => trans('admin::app.customers.groups.index.datagrid.delete'), 'method' => 'DELETE', 'url' => function ($row) {
                return route('admin.customers.groups.delete', $row->id);
            }]);
        }
    }
}