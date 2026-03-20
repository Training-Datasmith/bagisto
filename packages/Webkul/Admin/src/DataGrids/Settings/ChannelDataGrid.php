<?php

declare (strict_types=1);
namespace Webkul\Admin\Data_Grids\Settings;

use Illuminate\Support\Facades\DB;
use Webkul\Data_Grid\Data_Grid;
class Channel_Data_Grid extends Data_Grid
{
    /**
     * Prepare query builder.
     *
     * @return \Illuminate\Database\Query\Builder
     */
    public function prepare_query_builder()
    {
        $query_builder = DB::table('channels')->left_join('channel_translations', function ($left_join) {
            $left_join->on('channel_translations.channel_id', '=', 'channels.id')->where('channel_translations.locale', core()->get_requested_locale_code());
        })->select('channels.id', 'channels.code', 'channel_translations.locale', 'channel_translations.name as translated_name', 'channels.hostname');
        $this->add_filter('id', 'channels.id');
        $this->add_filter('code', 'channels.code');
        $this->add_filter('hostname', 'channels.hostname');
        $this->add_filter('translated_name', 'channel_translations.name');
        return $query_builder;
    }
    public function prepare_columns()
    {
        $this->add_column(['index' => 'id', 'label' => trans('admin::app.settings.channels.index.datagrid.id'), 'type' => 'integer', 'filterable' => true, 'sortable' => true]);
        $this->add_column(['index' => 'code', 'label' => trans('admin::app.settings.channels.index.datagrid.code'), 'type' => 'string', 'searchable' => true, 'filterable' => true, 'sortable' => true]);
        $this->add_column(['index' => 'translated_name', 'label' => trans('admin::app.settings.channels.index.datagrid.name'), 'type' => 'string', 'searchable' => true, 'filterable' => true, 'sortable' => true]);
        $this->add_column(['index' => 'hostname', 'label' => trans('admin::app.settings.channels.index.datagrid.host-name'), 'type' => 'string', 'searchable' => true, 'filterable' => true, 'sortable' => true]);
    }
    public function prepare_actions()
    {
        if (bouncer()->has_permission('settings.channels.edit')) {
            $this->add_action(['icon' => 'icon-edit', 'title' => trans('admin::app.settings.channels.index.datagrid.edit'), 'method' => 'GET', 'url' => function ($row) {
                return route('admin.settings.channels.edit', $row->id);
            }]);
        }
        if (bouncer()->has_permission('settings.channels.delete')) {
            $this->add_action(['icon' => 'icon-delete', 'title' => trans('admin::app.settings.channels.index.datagrid.delete'), 'method' => 'DELETE', 'url' => function ($row) {
                return route('admin.settings.channels.delete', $row->id);
            }]);
        }
    }
}