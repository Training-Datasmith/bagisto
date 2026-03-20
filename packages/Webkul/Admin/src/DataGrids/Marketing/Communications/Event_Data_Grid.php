<?php

declare (strict_types=1);
namespace Webkul\Admin\Data_Grids\Marketing\Communications;

use Illuminate\Support\Facades\DB;
use Webkul\Data_Grid\Data_Grid;
class Event_Data_Grid extends Data_Grid
{
    /**
     * Prepare query builder.
     *
     * @return \Illuminate\Database\Query\Builder
     */
    public function prepare_query_builder()
    {
        return DB::table('marketing_events')->select('id', 'name', 'date');
    }
    public function prepare_columns()
    {
        $this->add_column(['index' => 'id', 'label' => trans('admin::app.marketing.communications.events.index.datagrid.id'), 'type' => 'integer', 'filterable' => true, 'sortable' => true]);
        $this->add_column(['index' => 'name', 'label' => trans('admin::app.marketing.communications.events.index.datagrid.name'), 'type' => 'string', 'searchable' => true, 'filterable' => true, 'sortable' => true]);
        $this->add_column(['index' => 'date', 'label' => trans('admin::app.marketing.communications.events.index.datagrid.date'), 'type' => 'date', 'searchable' => true, 'filterable' => true, 'filterable_type' => 'date_range', 'sortable' => true]);
    }
    public function prepare_actions()
    {
        if (bouncer()->has_permission('marketing.communications.events.edit')) {
            $this->add_action(['index' => 'edit', 'icon' => 'icon-edit', 'title' => trans('admin::app.marketing.communications.events.index.datagrid.edit'), 'method' => 'PUT', 'url' => function ($row) {
                return route('admin.marketing.communications.events.edit', $row->id);
            }]);
        }
        if (bouncer()->has_permission('marketing.communications.events.delete')) {
            $this->add_action(['index' => 'delete', 'icon' => 'icon-delete', 'title' => trans('admin::app.marketing.communications.events.index.datagrid.delete'), 'method' => 'DELETE', 'url' => function ($row) {
                return route('admin.marketing.communications.events.delete', $row->id);
            }]);
        }
    }
}