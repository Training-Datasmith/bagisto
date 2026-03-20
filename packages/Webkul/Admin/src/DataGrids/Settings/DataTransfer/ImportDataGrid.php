<?php

declare (strict_types=1);
namespace Webkul\Admin\Data_Grids\Settings\Data_Transfer;

use Illuminate\Support\Facades\DB;
use Webkul\Data_Grid\Data_Grid;
class Import_Data_Grid extends Data_Grid
{
    /**
     * Prepare query builder.
     *
     * @return \Illuminate\Database\Query\Builder
     */
    public function prepare_query_builder()
    {
        return DB::table('imports')->select('id', 'state', 'file_path', 'error_file_path', 'started_at', 'completed_at', 'summary');
    }
    /**
     * Add columns.
     *
     * @return void
     */
    public function prepare_columns()
    {
        $this->add_column(['index' => 'id', 'label' => trans('admin::app.settings.data-transfer.imports.index.datagrid.id'), 'type' => 'integer', 'filterable' => true, 'sortable' => true]);
        $this->add_column(['index' => 'state', 'label' => trans('admin::app.settings.data-transfer.imports.index.datagrid.state'), 'type' => 'string', 'filterable' => true, 'sortable' => true]);
        $this->add_column(['index' => 'file_path', 'label' => trans('admin::app.settings.data-transfer.imports.index.datagrid.uploaded-file'), 'type' => 'string', 'closure' => function ($row) {
            return '<a href="' . route('admin.settings.data_transfer.imports.download', $row->id) . '" class="cursor-pointer text-blue-600 hover:underline">' . $row->file_path . '<a>';
        }]);
        $this->add_column(['index' => 'error_file_path', 'label' => trans('admin::app.settings.data-transfer.imports.index.datagrid.error-file'), 'type' => 'string', 'closure' => function ($row) {
            if (empty($row->error_file_path)) {
                return '';
            }
            return '<a href="' . route('admin.settings.data_transfer.imports.download_error_report', $row->id) . '" class="cursor-pointer text-blue-600 hover:underline">' . $row->error_file_path . '<a>';
        }]);
        $this->add_column(['index' => 'started_at', 'label' => trans('admin::app.settings.data-transfer.imports.index.datagrid.started-at'), 'type' => 'date', 'filterable' => true, 'filterable_type' => 'date_range', 'sortable' => true]);
        $this->add_column(['index' => 'completed_at', 'label' => trans('admin::app.settings.data-transfer.imports.index.datagrid.completed-at'), 'type' => 'date', 'filterable' => true, 'filterable_type' => 'date_range', 'sortable' => true]);
        $this->add_column(['index' => 'summary', 'label' => trans('admin::app.settings.data-transfer.imports.index.datagrid.summary'), 'type' => 'string', 'closure' => function ($row) {
            if (empty($row->summary)) {
                return '';
            }
            $summary = json_decode($row->summary, true);
            $stats = [];
            foreach ($summary as $type => $value) {
                $stats[] = trans('admin::app.settings.data-transfer.imports.index.datagrid.' . $type) . ': ' . $summary[$type];
            }
            return implode(', ', $stats);
        }]);
    }
    /**
     * Prepare actions.
     *
     * @return void
     */
    public function prepare_actions()
    {
        if (bouncer()->has_permission('settings.data_transfer.imports.import')) {
            $this->add_action(['index' => 'import', 'icon' => 'icon-import', 'title' => trans('admin::app.settings.data-transfer.imports.index.datagrid.import'), 'method' => 'GET', 'url' => function ($row) {
                return route('admin.settings.data_transfer.imports.import', $row->id);
            }]);
        }
        if (bouncer()->has_permission('settings.data_transfer.imports.edit')) {
            $this->add_action(['index' => 'edit', 'icon' => 'icon-edit', 'title' => trans('admin::app.settings.data-transfer.imports.index.datagrid.edit'), 'method' => 'GET', 'url' => function ($row) {
                return route('admin.settings.data_transfer.imports.edit', $row->id);
            }]);
        }
        if (bouncer()->has_permission('settings.data_transfer.imports.delete')) {
            $this->add_action(['index' => 'delete', 'icon' => 'icon-delete', 'title' => trans('admin::app.settings.data-transfer.imports.index.datagrid.delete'), 'method' => 'DELETE', 'url' => function ($row) {
                return route('admin.settings.data_transfer.imports.delete', $row->id);
            }]);
        }
    }
}