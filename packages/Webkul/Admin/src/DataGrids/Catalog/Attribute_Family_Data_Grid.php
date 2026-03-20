<?php

declare (strict_types=1);
namespace Webkul\Admin\Data_Grids\Catalog;

use Illuminate\Support\Facades\DB;
use Webkul\Data_Grid\Data_Grid;
class Attribute_Family_Data_Grid extends Data_Grid
{
    /**
     * Prepare query builder.
     *
     * @return \Illuminate\Database\Query\Builder
     */
    public function prepare_query_builder()
    {
        return DB::table('attribute_families')->select('id', 'code', 'name');
    }
    /**
     * Add columns.
     *
     * @return void
     */
    public function prepare_columns()
    {
        $this->add_column(['index' => 'id', 'label' => trans('admin::app.catalog.families.index.datagrid.id'), 'type' => 'integer', 'filterable' => true, 'sortable' => true]);
        $this->add_column(['index' => 'code', 'label' => trans('admin::app.catalog.families.index.datagrid.code'), 'type' => 'string', 'searchable' => true, 'filterable' => true, 'sortable' => true]);
        $this->add_column(['index' => 'name', 'label' => trans('admin::app.catalog.families.index.datagrid.name'), 'type' => 'string', 'searchable' => true, 'filterable' => true, 'sortable' => true]);
    }
    /**
     * Prepare actions.
     *
     * @return void
     */
    public function prepare_actions()
    {
        if (bouncer()->has_permission('catalog.families.edit')) {
            $this->add_action(['icon' => 'icon-edit', 'title' => trans('admin::app.catalog.families.index.datagrid.catalog.families.index.datagrid.edit'), 'method' => 'GET', 'url' => function ($row) {
                return route('admin.catalog.families.edit', $row->id);
            }]);
        }
        if (bouncer()->has_permission('catalog.families.delete')) {
            $this->add_action(['icon' => 'icon-delete', 'title' => trans('admin::app.catalog.families.index.datagrid.catalog.families.index.datagrid.delete'), 'method' => 'DELETE', 'url' => function ($row) {
                return route('admin.catalog.families.delete', $row->id);
            }]);
        }
    }
}