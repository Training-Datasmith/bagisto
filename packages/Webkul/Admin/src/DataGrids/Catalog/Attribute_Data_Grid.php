<?php

declare (strict_types=1);
namespace Webkul\Admin\Data_Grids\Catalog;

use Illuminate\Support\Facades\DB;
use Webkul\Data_Grid\Data_Grid;
class Attribute_Data_Grid extends Data_Grid
{
    /**
     * Prepare query builder.
     *
     * @return \Illuminate\Database\Query\Builder
     */
    public function prepare_query_builder()
    {
        return DB::table('attributes')->select('id', 'code', 'admin_name', 'type', 'is_required', 'is_unique', 'value_per_locale', 'value_per_channel', 'created_at');
    }
    /**
     * Add columns.
     *
     * @return void
     */
    public function prepare_columns()
    {
        $this->add_column(['index' => 'id', 'label' => trans('admin::app.catalog.attributes.index.datagrid.id'), 'type' => 'integer', 'searchable' => true, 'filterable' => true, 'sortable' => true]);
        $this->add_column(['index' => 'code', 'label' => trans('admin::app.catalog.attributes.index.datagrid.code'), 'type' => 'string', 'searchable' => true, 'filterable' => true, 'sortable' => true]);
        $this->add_column(['index' => 'admin_name', 'label' => trans('admin::app.catalog.attributes.index.datagrid.name'), 'type' => 'string', 'searchable' => true, 'filterable' => true, 'sortable' => true]);
        $this->add_column(['index' => 'type', 'label' => trans('admin::app.catalog.attributes.index.datagrid.type'), 'type' => 'string', 'searchable' => true, 'filterable' => true, 'filterable_type' => 'dropdown', 'filterable_options' => [['label' => trans('admin::app.catalog.attributes.index.datagrid.text'), 'value' => 'text'], ['label' => trans('admin::app.catalog.attributes.index.datagrid.textarea'), 'value' => 'textarea'], ['label' => trans('admin::app.catalog.attributes.index.datagrid.price'), 'value' => 'price'], ['label' => trans('admin::app.catalog.attributes.index.datagrid.boolean'), 'value' => 'boolean'], ['label' => trans('admin::app.catalog.attributes.index.datagrid.select'), 'value' => 'select'], ['label' => trans('admin::app.catalog.attributes.index.datagrid.multiselect'), 'value' => 'multiselect'], ['label' => trans('admin::app.catalog.attributes.index.datagrid.date-time'), 'value' => 'datetime'], ['label' => trans('admin::app.catalog.attributes.index.datagrid.date'), 'value' => 'date'], ['label' => trans('admin::app.catalog.attributes.index.datagrid.image'), 'value' => 'image'], ['label' => trans('admin::app.catalog.attributes.index.datagrid.file'), 'value' => 'file'], ['label' => trans('admin::app.catalog.attributes.index.datagrid.checkbox'), 'value' => 'checkbox']], 'sortable' => true]);
        $this->add_column(['index' => 'is_required', 'label' => trans('admin::app.catalog.attributes.index.datagrid.required'), 'type' => 'boolean', 'searchable' => true, 'filterable' => true, 'sortable' => true, 'closure' => function ($row) {
            if ($row->is_required) {
                return trans('admin::app.catalog.attributes.index.datagrid.true');
            }
            return trans('admin::app.catalog.attributes.index.datagrid.false');
        }]);
        $this->add_column(['index' => 'is_unique', 'label' => trans('admin::app.catalog.attributes.index.datagrid.unique'), 'type' => 'boolean', 'searchable' => true, 'filterable' => true, 'sortable' => true, 'closure' => function ($row) {
            if ($row->is_unique) {
                return trans('admin::app.catalog.attributes.index.datagrid.true');
            }
            return trans('admin::app.catalog.attributes.index.datagrid.false');
        }]);
        $this->add_column(['index' => 'value_per_locale', 'label' => trans('admin::app.catalog.attributes.index.datagrid.locale-based'), 'type' => 'boolean', 'searchable' => true, 'filterable' => true, 'sortable' => true, 'closure' => function ($row) {
            if ($row->value_per_locale) {
                return trans('admin::app.catalog.attributes.index.datagrid.true');
            }
            return trans('admin::app.catalog.attributes.index.datagrid.false');
        }]);
        $this->add_column(['index' => 'value_per_channel', 'label' => trans('admin::app.catalog.attributes.index.datagrid.channel-based'), 'type' => 'boolean', 'searchable' => true, 'filterable' => true, 'sortable' => true, 'closure' => function ($row) {
            if ($row->value_per_channel) {
                return trans('admin::app.catalog.attributes.index.datagrid.true');
            }
            return trans('admin::app.catalog.attributes.index.datagrid.false');
        }]);
        $this->add_column(['index' => 'created_at', 'label' => trans('admin::app.catalog.attributes.index.datagrid.created-at'), 'type' => 'date', 'searchable' => true, 'filterable' => true, 'filterable_type' => 'date_range', 'sortable' => true]);
    }
    /**
     * Prepare actions.
     *
     * @return void
     */
    public function prepare_actions()
    {
        if (bouncer()->has_permission('catalog.attributes.edit')) {
            $this->add_action(['icon' => 'icon-edit', 'title' => trans('admin::app.catalog.attributes.index.datagrid.edit'), 'method' => 'GET', 'url' => function ($row) {
                return route('admin.catalog.attributes.edit', $row->id);
            }]);
        }
        if (bouncer()->has_permission('catalog.attributes.delete')) {
            $this->add_action(['icon' => 'icon-delete', 'title' => trans('admin::app.catalog.attributes.index.datagrid.delete'), 'method' => 'DELETE', 'url' => function ($row) {
                return route('admin.catalog.attributes.delete', $row->id);
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
        if (bouncer()->has_permission('catalog.attributes.delete')) {
            $this->add_mass_action(['icon' => 'icon-delete', 'title' => trans('admin::app.catalog.attributes.index.datagrid.delete'), 'method' => 'POST', 'url' => route('admin.catalog.attributes.mass_delete')]);
        }
    }
}