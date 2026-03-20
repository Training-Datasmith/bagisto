<?php

declare (strict_types=1);
namespace Webkul\Admin\Data_Grids\Marketing\Communications;

use Illuminate\Support\Facades\DB;
use Webkul\Data_Grid\Data_Grid;
class News_Letter_Data_Grid extends Data_Grid
{
    /**
     * Prepare query builder.
     *
     * @return \Illuminate\Database\Query\Builder
     */
    public function prepare_query_builder()
    {
        $query_builder = DB::table('subscribers_list')->select('subscribers_list.id', 'subscribers_list.is_subscribed as status', 'subscribers_list.email');
        $this->add_filter('status', 'subscribers_list.is_subscribed');
        return $query_builder;
    }
    /**
     * Add columns.
     *
     * @return void
     */
    public function prepare_columns()
    {
        $this->add_column(['index' => 'id', 'label' => trans('admin::app.marketing.communications.subscribers.index.datagrid.id'), 'type' => 'integer', 'filterable' => true, 'sortable' => true]);
        $this->add_column(['index' => 'status', 'label' => trans('admin::app.marketing.communications.subscribers.index.datagrid.subscribed'), 'type' => 'boolean', 'searchable' => true, 'filterable' => true, 'sortable' => true, 'closure' => function ($value) {
            if ($value->status) {
                return trans('admin::app.marketing.communications.subscribers.index.datagrid.true');
            }
            return trans('admin::app.marketing.communications.subscribers.index.datagrid.false');
        }]);
        $this->add_column(['index' => 'email', 'label' => trans('admin::app.marketing.communications.subscribers.index.datagrid.email'), 'type' => 'string', 'searchable' => true, 'filterable' => true, 'sortable' => true]);
    }
    /**
     * Prepare actions.
     *
     * @return void
     */
    public function prepare_actions()
    {
        if (bouncer()->has_permission('marketing.communications.subscribers.edit')) {
            $this->add_action(['index' => 'edit', 'icon' => 'icon-edit', 'title' => trans('admin::app.marketing.communications.subscribers.index.datagrid.edit'), 'method' => 'GET', 'url' => function ($row) {
                return route('admin.marketing.communications.subscribers.edit', $row->id);
            }]);
        }
        if (bouncer()->has_permission('marketing.communications.subscribers.delete')) {
            $this->add_action(['index' => 'delete', 'icon' => 'icon-delete', 'title' => trans('admin::app.marketing.communications.subscribers.index.datagrid.delete'), 'method' => 'DELETE', 'url' => function ($row) {
                return route('admin.marketing.communications.subscribers.delete', $row->id);
            }]);
        }
    }
}