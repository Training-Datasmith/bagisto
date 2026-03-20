<?php

declare (strict_types=1);
namespace Webkul\Admin\Data_Grids\Marketing\Communications;

use Illuminate\Support\Facades\DB;
use Webkul\Data_Grid\Data_Grid;
class Email_Template_Data_Grid extends Data_Grid
{
    /**
     * Prepare query builder.
     *
     * @return \Illuminate\Database\Query\Builder
     */
    public function prepare_query_builder()
    {
        $query_builder = DB::table('marketing_templates')->select('id', 'name', 'status');
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
        $this->add_column(['index' => 'id', 'label' => trans('admin::app.marketing.communications.templates.index.datagrid.id'), 'type' => 'integer', 'filterable' => true, 'sortable' => true]);
        $this->add_column(['index' => 'name', 'label' => trans('admin::app.marketing.communications.templates.index.datagrid.name'), 'type' => 'string', 'searchable' => true, 'filterable' => true, 'sortable' => true]);
        $this->add_column(['index' => 'status', 'label' => trans('admin::app.marketing.communications.templates.index.datagrid.status'), 'type' => 'string', 'searchable' => true, 'filterable' => true, 'filterable_type' => 'dropdown', 'filterable_options' => [['label' => trans('admin::app.marketing.communications.templates.index.datagrid.active'), 'value' => 'active'], ['label' => trans('admin::app.marketing.communications.templates.index.datagrid.inactive'), 'value' => 'inactive'], ['label' => trans('admin::app.marketing.communications.templates.index.datagrid.draft'), 'value' => 'draft']], 'sortable' => true, 'closure' => function ($value) {
            if ($value->status == 'active') {
                return trans('admin::app.marketing.communications.templates.index.datagrid.active');
            } elseif ($value->status == 'inactive') {
                return trans('admin::app.marketing.communications.templates.index.datagrid.inactive');
            } elseif ($value->status == 'draft') {
                return trans('admin::app.marketing.communications.templates.index.datagrid.draft');
            }
        }]);
    }
    /**
     * Prepare actions.
     *
     * @return void
     */
    public function prepare_actions()
    {
        if (bouncer()->has_permission('marketing.communications.email_templates.edit')) {
            $this->add_action(['icon' => 'icon-edit', 'title' => trans('admin::app.marketing.communications.templates.index.datagrid.edit'), 'method' => 'GET', 'url' => function ($row) {
                return route('admin.marketing.communications.email_templates.edit', $row->id);
            }]);
        }
        if (bouncer()->has_permission('marketing.communications.email_templates.delete')) {
            $this->add_action(['icon' => 'icon-delete', 'title' => trans('admin::app.marketing.communications.templates.index.datagrid.delete'), 'method' => 'DELETE', 'url' => function ($row) {
                return route('admin.marketing.communications.email_templates.delete', $row->id);
            }]);
        }
    }
}