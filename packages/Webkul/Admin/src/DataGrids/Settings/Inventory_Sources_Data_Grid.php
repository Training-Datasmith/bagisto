<?php

declare (strict_types=1);
namespace Webkul\Admin\Data_Grids\Settings;

use Illuminate\Support\Facades\DB;
use Webkul\Data_Grid\Data_Grid;
class Inventory_Sources_Data_Grid extends Data_Grid
{
    /**
     * Prepare query builder.
     *
     * @return \Illuminate\Database\Query\Builder
     */
    public function prepare_query_builder()
    {
        return DB::table('inventory_sources')->select('id', 'code', 'name', 'priority', 'status');
    }
    /**
     * Add columns.
     *
     * @return void
     */
    public function prepare_columns()
    {
        $this->add_column(['index' => 'id', 'label' => trans('admin::app.settings.inventory-sources.index.datagrid.id'), 'type' => 'integer', 'filterable' => true, 'sortable' => true]);
        $this->add_column(['index' => 'code', 'label' => trans('admin::app.settings.inventory-sources.index.datagrid.code'), 'type' => 'string', 'searchable' => true, 'filterable' => true, 'sortable' => true]);
        $this->add_column(['index' => 'name', 'label' => trans('admin::app.settings.inventory-sources.index.datagrid.name'), 'type' => 'string', 'searchable' => true, 'filterable' => true, 'sortable' => true]);
        $this->add_column(['index' => 'priority', 'label' => trans('admin::app.settings.inventory-sources.index.datagrid.priority'), 'type' => 'integer', 'searchable' => true, 'filterable' => true, 'sortable' => true]);
        $this->add_column(['index' => 'status', 'label' => trans('admin::app.settings.inventory-sources.index.datagrid.status'), 'type' => 'boolean', 'searchable' => true, 'filterable' => true, 'filterable_options' => [['label' => trans('admin::app.settings.inventory-sources.index.datagrid.active'), 'value' => 1], ['label' => trans('admin::app.settings.inventory-sources.index.datagrid.inactive'), 'value' => 0]], 'sortable' => true, 'closure' => function ($value) {
            if ($value->status) {
                return trans('admin::app.settings.inventory-sources.index.datagrid.active');
            }
            return trans('admin::app.settings.inventory-sources.index.datagrid.inactive');
        }]);
    }
    /**
     * Prepare actions.
     *
     * @return void
     */
    public function prepare_actions()
    {
        if (bouncer()->has_permission('settings.inventory_sources.edit')) {
            $this->add_action(['icon' => 'icon-edit', 'title' => trans('admin::app.settings.inventory-sources.index.datagrid.edit'), 'method' => 'GET', 'url' => function ($row) {
                return route('admin.settings.inventory_sources.edit', $row->id);
            }]);
        }
        if (bouncer()->has_permission('settings.inventory_sources.delete')) {
            $this->add_action(['icon' => 'icon-delete', 'title' => trans('admin::app.settings.inventory-sources.index.datagrid.delete'), 'method' => 'DELETE', 'url' => function ($row) {
                return route('admin.settings.inventory_sources.delete', $row->id);
            }]);
        }
    }
}