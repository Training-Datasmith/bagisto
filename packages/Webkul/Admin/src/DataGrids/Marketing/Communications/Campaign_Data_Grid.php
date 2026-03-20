<?php

declare (strict_types=1);
namespace Webkul\Admin\Data_Grids\Marketing\Communications;

use Illuminate\Support\Facades\DB;
use Webkul\Data_Grid\Data_Grid;
class Campaign_Data_Grid extends Data_Grid
{
    /**
     * Prepare query builder.
     *
     * @return \Illuminate\Database\Query\Builder
     */
    public function prepare_query_builder()
    {
        $query_builder = DB::table('marketing_campaigns')->select('id', 'name', 'subject', 'status');
        $this->add_filter('status', 'marketing_campaigns.status');
        return $query_builder;
    }
    /**
     * Add columns.
     *
     * @return void
     */
    public function prepare_columns()
    {
        $this->add_column(['index' => 'id', 'label' => trans('admin::app.marketing.communications.campaigns.index.datagrid.id'), 'type' => 'integer', 'sortable' => true, 'filterable' => true]);
        $this->add_column(['index' => 'name', 'label' => trans('admin::app.marketing.communications.campaigns.index.datagrid.name'), 'type' => 'string', 'searchable' => true, 'sortable' => true, 'filterable' => true]);
        $this->add_column(['index' => 'subject', 'label' => trans('admin::app.marketing.communications.campaigns.index.datagrid.subject'), 'type' => 'string', 'searchable' => true, 'sortable' => true, 'filterable' => true]);
        $this->add_column(['index' => 'status', 'label' => trans('admin::app.marketing.communications.campaigns.index.datagrid.status'), 'type' => 'boolean', 'searchable' => true, 'sortable' => true, 'filterable' => true, 'filterable_options' => [['label' => trans('admin::app.marketing.communications.campaigns.index.datagrid.active'), 'value' => 1], ['label' => trans('admin::app.marketing.communications.campaigns.index.datagrid.inactive'), 'value' => 0]], 'closure' => function ($value) {
            if ($value->status) {
                return trans('admin::app.marketing.communications.campaigns.index.datagrid.active');
            }
            return trans('admin::app.marketing.communications.campaigns.index.datagrid.inactive');
        }]);
    }
    /**
     * Prepare actions.
     *
     * @return void
     */
    public function prepare_actions()
    {
        if (bouncer()->has_permission('marketing.communications.campaigns.edit')) {
            $this->add_action(['icon' => 'icon-edit', 'title' => trans('admin::app.marketing.communications.campaigns.index.datagrid.edit'), 'method' => 'GET', 'url' => function ($row) {
                return route('admin.marketing.communications.campaigns.edit', $row->id);
            }]);
        }
        if (bouncer()->has_permission('marketing.communications.campaigns.delete')) {
            $this->add_action(['icon' => 'icon-delete', 'title' => trans('admin::app.marketing.communications.campaigns.index.datagrid.delete'), 'method' => 'DELETE', 'url' => function ($row) {
                return route('admin.marketing.communications.campaigns.delete', $row->id);
            }]);
        }
    }
}