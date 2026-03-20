<?php

declare (strict_types=1);
namespace Webkul\Admin\Data_Grids\Marketing\Promotions;

use Illuminate\Support\Facades\DB;
use Webkul\Data_Grid\Data_Grid;
class Catalog_Rule_Data_Grid extends Data_Grid
{
    /**
     * Prepare query builder.
     *
     * @return \Illuminate\Database\Query\Builder
     */
    public function prepare_query_builder()
    {
        $query_builder = DB::table('catalog_rules')->select('catalog_rules.id', 'name', 'status', 'starts_from', 'ends_till', 'sort_order');
        $this->add_filter('status', 'status');
        return $query_builder;
    }
    /**
     * Add columns.
     *
     * @return void
     */
    public function prepare_columns()
    {
        $this->add_column(['index' => 'id', 'label' => trans('admin::app.marketing.promotions.catalog-rules.index.datagrid.id'), 'type' => 'integer', 'filterable' => true, 'sortable' => true]);
        $this->add_column(['index' => 'name', 'label' => trans('admin::app.marketing.promotions.catalog-rules.index.datagrid.name'), 'type' => 'string', 'searchable' => true, 'filterable' => true, 'sortable' => true]);
        $this->add_column(['index' => 'starts_from', 'label' => trans('admin::app.marketing.promotions.catalog-rules.index.datagrid.start'), 'type' => 'datetime', 'filterable' => true, 'filterable_type' => 'datetime_range', 'sortable' => true, 'closure' => function ($value) {
            return $value->starts_from ?? '-';
        }]);
        $this->add_column(['index' => 'ends_till', 'label' => trans('admin::app.marketing.promotions.catalog-rules.index.datagrid.end'), 'type' => 'datetime', 'filterable' => true, 'filterable_type' => 'datetime_range', 'sortable' => true, 'closure' => function ($value) {
            return $value->ends_till ?? '-';
        }]);
        $this->add_column(['index' => 'status', 'label' => trans('admin::app.marketing.promotions.catalog-rules.index.datagrid.status'), 'type' => 'boolean', 'searchable' => true, 'filterable' => true, 'filterable_options' => [['label' => trans('admin::app.marketing.promotions.catalog-rules.index.datagrid.active'), 'value' => 1], ['label' => trans('admin::app.marketing.promotions.catalog-rules.index.datagrid.inactive'), 'value' => 0]], 'sortable' => true, 'closure' => function ($value) {
            if ($value->status) {
                return trans('admin::app.marketing.promotions.catalog-rules.index.datagrid.active');
            }
            return trans('admin::app.marketing.promotions.catalog-rules.index.datagrid.inactive');
        }]);
        $this->add_column(['index' => 'sort_order', 'label' => trans('admin::app.marketing.promotions.catalog-rules.index.datagrid.priority'), 'type' => 'integer', 'searchable' => true, 'filterable' => true, 'sortable' => true]);
    }
    /**
     * Prepare actions.
     *
     * @return void
     */
    public function prepare_actions()
    {
        if (bouncer()->has_permission('marketing.promotions.catalog_rules.edit')) {
            $this->add_action(['icon' => 'icon-edit', 'title' => trans('admin::app.marketing.promotions.catalog-rules.index.datagrid.edit'), 'method' => 'GET', 'url' => function ($row) {
                return route('admin.marketing.promotions.catalog_rules.edit', $row->id);
            }]);
        }
        if (bouncer()->has_permission('marketing.promotions.catalog_rules.delete')) {
            $this->add_action(['icon' => 'icon-delete', 'title' => trans('admin::app.marketing.promotions.catalog-rules.index.datagrid.delete'), 'method' => 'DELETE', 'url' => function ($row) {
                return route('admin.marketing.promotions.catalog_rules.delete', $row->id);
            }]);
        }
    }
}