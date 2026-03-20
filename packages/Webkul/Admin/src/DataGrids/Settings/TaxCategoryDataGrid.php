<?php

declare (strict_types=1);
namespace Webkul\Admin\Data_Grids\Settings;

use Illuminate\Support\Facades\DB;
use Webkul\Data_Grid\Data_Grid;
class Tax_Category_Data_Grid extends Data_Grid
{
    /**
     * Prepare query builder.
     *
     * @return \Illuminate\Database\Query\Builder
     */
    public function prepare_query_builder()
    {
        return DB::table('tax_categories')->select('id', 'name', 'code');
    }
    /**
     * Add Columns.
     *
     * @return void
     */
    public function prepare_columns()
    {
        $this->add_column(['index' => 'id', 'label' => trans('admin::app.settings.taxes.categories.index.datagrid.id'), 'type' => 'integer', 'filterable' => true, 'sortable' => true]);
        $this->add_column(['index' => 'name', 'label' => trans('admin::app.settings.taxes.categories.index.datagrid.name'), 'type' => 'string', 'searchable' => true, 'filterable' => true, 'sortable' => true]);
        $this->add_column(['index' => 'code', 'label' => trans('admin::app.settings.taxes.categories.index.datagrid.code'), 'type' => 'string', 'searchable' => true, 'filterable' => true, 'sortable' => true]);
    }
    /**
     * Prepare actions.
     *
     * @return void
     */
    public function prepare_actions()
    {
        if (bouncer()->has_permission('settings.taxes.tax_categories.edit')) {
            $this->add_action(['index' => 'edit', 'icon' => 'icon-edit', 'title' => trans('admin::app.settings.taxes.categories.index.datagrid.edit'), 'method' => 'GET', 'url' => function ($row) {
                return route('admin.settings.taxes.categories.edit', $row->id);
            }]);
        }
        if (bouncer()->has_permission('settings.taxes.tax_categories.delete')) {
            $this->add_action(['index' => 'delete', 'icon' => 'icon-delete', 'title' => trans('admin::app.settings.taxes.categories.index.datagrid.delete'), 'method' => 'DELETE', 'url' => function ($row) {
                return route('admin.settings.taxes.categories.delete', $row->id);
            }]);
        }
    }
}