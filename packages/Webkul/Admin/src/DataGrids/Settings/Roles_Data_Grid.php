<?php

declare (strict_types=1);
namespace Webkul\Admin\Data_Grids\Settings;

use Illuminate\Support\Facades\DB;
use Webkul\Data_Grid\Data_Grid;
class Roles_Data_Grid extends Data_Grid
{
    /**
     * Prepare query builder.
     *
     * @return \Illuminate\Database\Query\Builder
     */
    public function prepare_query_builder()
    {
        return DB::table('roles')->select('id', 'name', 'permission_type');
    }
    /**
     * Add Columns.
     *
     * @return void
     */
    public function prepare_columns()
    {
        $this->add_column(['index' => 'id', 'label' => trans('admin::app.settings.roles.index.datagrid.id'), 'type' => 'integer', 'filterable' => true, 'sortable' => true]);
        $this->add_column(['index' => 'name', 'label' => trans('admin::app.settings.roles.index.datagrid.name'), 'type' => 'string', 'searchable' => true, 'filterable' => true, 'sortable' => true]);
        $this->add_column(['index' => 'permission_type', 'label' => trans('admin::app.settings.roles.index.datagrid.permission-type'), 'type' => 'string', 'searchable' => true, 'filterable' => true, 'filterable_type' => 'dropdown', 'filterable_options' => [['label' => trans('admin::app.settings.roles.index.datagrid.custom'), 'value' => 'custom'], ['label' => trans('admin::app.settings.roles.index.datagrid.all'), 'value' => 'all']], 'sortable' => true]);
    }
    /**
     * Prepare actions.
     *
     * @return void
     */
    public function prepare_actions()
    {
        if (bouncer()->has_permission('settings.roles.edit')) {
            $this->add_action(['icon' => 'icon-edit', 'title' => trans('admin::app.settings.roles.index.datagrid.edit'), 'method' => 'GET', 'url' => function ($row) {
                return route('admin.settings.roles.edit', $row->id);
            }]);
        }
        if (bouncer()->has_permission('settings.roles.delete')) {
            $this->add_action(['icon' => 'icon-delete', 'title' => trans('admin::app.settings.roles.index.datagrid.delete'), 'method' => 'DELETE', 'url' => function ($row) {
                return route('admin.settings.roles.delete', $row->id);
            }]);
        }
    }
}