<?php

declare (strict_types=1);
namespace Webkul\Admin\Data_Grids\Settings;

use Illuminate\Support\Facades\DB;
use Webkul\Data_Grid\Data_Grid;
class Locales_Data_Grid extends Data_Grid
{
    /**
     * Prepare query builder.
     *
     * @return \Illuminate\Database\Query\Builder
     */
    public function prepare_query_builder()
    {
        return DB::table('locales')->select('id', 'code', 'name', 'direction');
    }
    /**
     * Add columns.
     *
     * @return void
     */
    public function prepare_columns()
    {
        $this->add_column(['index' => 'id', 'label' => trans('admin::app.settings.locales.index.datagrid.id'), 'type' => 'integer', 'filterable' => true, 'sortable' => true]);
        $this->add_column(['index' => 'code', 'label' => trans('admin::app.settings.locales.index.datagrid.code'), 'type' => 'string', 'searchable' => true, 'filterable' => true, 'sortable' => true]);
        $this->add_column(['index' => 'name', 'label' => trans('admin::app.settings.locales.index.datagrid.name'), 'type' => 'string', 'searchable' => true, 'filterable' => true, 'sortable' => true]);
        $this->add_column(['index' => 'direction', 'label' => trans('admin::app.settings.locales.index.datagrid.direction'), 'type' => 'string', 'searchable' => true, 'filterable' => true, 'filterable_type' => 'dropdown', 'filterable_options' => [['label' => trans('admin::app.settings.locales.index.datagrid.ltr'), 'value' => 'ltr'], ['label' => trans('admin::app.settings.locales.index.datagrid.rtl'), 'value' => 'rtl']], 'sortable' => true, 'closure' => function ($value) {
            if ($value->direction == 'ltr') {
                return trans('admin::app.settings.locales.index.datagrid.ltr');
            }
            return trans('admin::app.settings.locales.index.datagrid.rtl');
        }]);
    }
    /**
     * Prepare actions.
     *
     * @return void
     */
    public function prepare_actions()
    {
        if (bouncer()->has_permission('settings.locales.edit')) {
            $this->add_action(['index' => 'edit', 'icon' => 'icon-edit', 'title' => trans('admin::app.settings.locales.index.datagrid.edit'), 'method' => 'GET', 'url' => function ($row) {
                return route('admin.settings.locales.edit', $row->id);
            }]);
        }
        if (bouncer()->has_permission('settings.locales.delete')) {
            $this->add_action(['index' => 'delete', 'icon' => 'icon-delete', 'title' => trans('admin::app.settings.locales.index.datagrid.delete'), 'method' => 'DELETE', 'url' => function ($row) {
                return route('admin.settings.locales.delete', $row->id);
            }]);
        }
    }
}