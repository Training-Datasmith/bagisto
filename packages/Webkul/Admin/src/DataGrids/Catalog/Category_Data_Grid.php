<?php

declare (strict_types=1);
namespace Webkul\Admin\Data_Grids\Catalog;

use Illuminate\Support\Facades\DB;
use Webkul\Data_Grid\Data_Grid;
class Category_Data_Grid extends Data_Grid
{
    /**
     * Index.
     *
     * @var string
     */
    protected $primary_column = 'category_id';
    /**
     * Prepare query builder.
     *
     * @return \Illuminate\Database\Query\Builder
     */
    public function prepare_query_builder()
    {
        $query_builder = DB::table('categories')->select('categories.id as category_id', 'category_translations.name', 'categories.position', 'categories.status', 'category_translations.locale')->left_join('category_translations', function ($join) {
            $join->on('categories.id', '=', 'category_translations.category_id')->where('category_translations.locale', '=', app()->get_locale());
        })->where('category_translations.locale', app()->get_locale())->group_by('categories.id');
        $this->add_filter('category_id', 'categories.id');
        return $query_builder;
    }
    /**
     * Add columns.
     *
     * @return void
     */
    public function prepare_columns()
    {
        $this->add_column(['index' => 'category_id', 'label' => trans('admin::app.catalog.categories.index.datagrid.id'), 'type' => 'integer', 'filterable' => true, 'sortable' => true]);
        $this->add_column(['index' => 'name', 'label' => trans('admin::app.catalog.categories.index.datagrid.name'), 'type' => 'string', 'searchable' => true, 'filterable' => true, 'sortable' => true]);
        $this->add_column(['index' => 'position', 'label' => trans('admin::app.catalog.categories.index.datagrid.position'), 'type' => 'integer', 'filterable' => true, 'sortable' => true]);
        $this->add_column(['index' => 'status', 'label' => trans('admin::app.catalog.categories.index.datagrid.status'), 'type' => 'boolean', 'filterable' => true, 'filterable_options' => [['label' => trans('admin::app.catalog.categories.index.datagrid.active'), 'value' => 1], ['label' => trans('admin::app.catalog.categories.index.datagrid.inactive'), 'value' => 0]], 'sortable' => true, 'closure' => function ($value) {
            if ($value->status) {
                return '<span class="badge badge-md badge-success">' . trans('admin::app.catalog.categories.index.datagrid.active') . '</span>';
            }
            return '<span class="badge badge-md badge-danger">' . trans('admin::app.catalog.categories.index.datagrid.inactive') . '</span>';
        }]);
    }
    /**
     * Prepare actions.
     *
     * @return void
     */
    public function prepare_actions()
    {
        if (bouncer()->has_permission('catalog.categories.edit')) {
            $this->add_action(['icon' => 'icon-edit', 'title' => trans('admin::app.catalog.categories.index.datagrid.edit'), 'method' => 'GET', 'url' => function ($row) {
                return route('admin.catalog.categories.edit', $row->category_id);
            }]);
        }
        if (bouncer()->has_permission('catalog.categories.delete')) {
            $this->add_action(['icon' => 'icon-delete', 'title' => trans('admin::app.catalog.categories.index.datagrid.delete'), 'method' => 'DELETE', 'url' => function ($row) {
                return route('admin.catalog.categories.delete', $row->category_id);
            }]);
        }
        if (bouncer()->has_permission('catalog.categories.delete')) {
            $this->add_mass_action(['title' => trans('admin::app.catalog.categories.index.datagrid.delete'), 'method' => 'POST', 'url' => route('admin.catalog.categories.mass_delete')]);
        }
        if (bouncer()->has_permission('catalog.categories.edit')) {
            $this->add_mass_action(['title' => trans('admin::app.catalog.categories.index.datagrid.update-status'), 'method' => 'POST', 'url' => route('admin.catalog.categories.mass_update'), 'options' => [['label' => trans('admin::app.catalog.categories.index.datagrid.active'), 'value' => 1], ['label' => trans('admin::app.catalog.categories.index.datagrid.inactive'), 'value' => 0]]]);
        }
    }
}