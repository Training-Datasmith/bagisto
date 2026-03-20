<?php

declare (strict_types=1);
namespace Webkul\Admin\Data_Grids\Theme;

use Illuminate\Support\Facades\DB;
use Webkul\Data_Grid\Data_Grid;
class Theme_Data_Grid extends Data_Grid
{
    /**
     * Prepare query builder.
     *
     * @return \Illuminate\Database\Query\Builder
     */
    public function prepare_query_builder()
    {
        $where_in_locales = core()->get_requested_locale_code() === 'all' ? core()->get_all_locales()->pluck('code')->to_array() : [core()->get_requested_locale_code()];
        $query_builder = DB::table('theme_customizations')->distinct()->left_join('theme_customization_translations', function ($join) use ($where_in_locales) {
            $join->on('theme_customizations.id', '=', 'theme_customization_translations.theme_customization_id')->where_in('theme_customization_translations.locale', $where_in_locales);
        })->left_join('channel_translations', function ($join) use ($where_in_locales) {
            $join->on('theme_customizations.channel_id', '=', 'channel_translations.channel_id')->where_in('channel_translations.locale', $where_in_locales);
        })->select('theme_customizations.id', 'theme_customizations.type', 'theme_customizations.sort_order', 'theme_customizations.status', 'theme_customizations.name as theme_customization_name', 'theme_customizations.theme_code', 'theme_customizations.channel_id', 'channel_translations.name as channel_name');
        $this->add_filter('id', 'theme_customizations.id');
        $this->add_filter('type', 'theme_customizations.type');
        $this->add_filter('theme_customization_name', 'theme_customizations.name');
        $this->add_filter('sort_order', 'theme_customizations.sort_order');
        $this->add_filter('status', 'theme_customizations.status');
        $this->add_filter('channel_name', 'channel_translations.name');
        $this->add_filter('theme_code', 'theme_customizations.theme_code');
        return $query_builder;
    }
    /**
     * Add columns.
     *
     * @return void
     */
    public function prepare_columns()
    {
        $themes = config('themes.shop');
        $this->add_column(['index' => 'channel_name', 'label' => trans('admin::app.settings.themes.index.datagrid.channel_name'), 'type' => 'string', 'filterable' => true, 'filterable_type' => 'dropdown', 'filterable_options' => core()->get_all_channels()->map(fn($channel) => ['label' => $channel->name, 'value' => $channel->id])->values()->to_array(), 'sortable' => true]);
        $this->add_column(['index' => 'theme_code', 'label' => trans('admin::app.settings.themes.index.datagrid.theme'), 'type' => 'string', 'filterable' => true, 'filterable_type' => 'dropdown', 'filterable_options' => collect($themes = config('themes.shop'))->map(fn($theme, $code) => ['label' => $theme['name'], 'value' => $code])->values()->to_array(), 'closure' => function ($row) use ($themes) {
            return collect($themes)->first(fn($theme, $code) => $code === $row->theme_code)['name'] ?? 'N/A';
        }, 'sortable' => true]);
        $this->add_column(['index' => 'type', 'label' => trans('admin::app.settings.themes.index.datagrid.type'), 'type' => 'string', 'searchable' => true, 'filterable' => true, 'sortable' => true]);
        $this->add_column(['index' => 'theme_customization_name', 'label' => trans('admin::app.settings.themes.index.datagrid.name'), 'type' => 'string', 'searchable' => true, 'filterable' => true, 'sortable' => true]);
        $this->add_column(['index' => 'sort_order', 'label' => trans('admin::app.settings.themes.index.datagrid.sort-order'), 'type' => 'string', 'searchable' => true, 'filterable' => true, 'sortable' => true]);
        $this->add_column(['index' => 'status', 'label' => trans('admin::app.settings.themes.index.datagrid.status'), 'type' => 'boolean', 'searchable' => true, 'filterable' => true, 'filterable_options' => [['label' => trans('admin::app.settings.themes.index.datagrid.active'), 'value' => 1], ['label' => trans('admin::app.settings.themes.index.datagrid.inactive'), 'value' => 0]], 'sortable' => true, 'closure' => function ($value) {
            if ($value->status) {
                return '<p class="label-active">' . trans('admin::app.settings.themes.index.datagrid.active') . '</p>';
            }
            return '<p class="label-pending">' . trans('admin::app.settings.themes.index.datagrid.inactive') . '</p>';
        }]);
    }
    public function prepare_actions()
    {
        if (bouncer()->has_permission('settings.themes.edit')) {
            $this->add_action(['icon' => 'icon-edit', 'title' => trans('admin::app.settings.themes.index.datagrid.view'), 'method' => 'GET', 'url' => function ($row) {
                return route('admin.settings.themes.edit', $row->id);
            }]);
        }
        if (bouncer()->has_permission('settings.themes.delete')) {
            $this->add_action(['icon' => 'icon-delete', 'title' => trans('admin::app.settings.themes.index.datagrid.delete'), 'method' => 'DELETE', 'url' => function ($row) {
                return route('admin.settings.themes.delete', $row->id);
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
        if (bouncer()->has_permission('settings.themes.edit')) {
            $this->add_mass_action(['title' => trans('admin::app.settings.themes.index.datagrid.change-status'), 'url' => route('admin.settings.themes.mass_update'), 'method' => 'POST', 'options' => [['label' => trans('admin::app.settings.themes.index.datagrid.active'), 'value' => 1], ['label' => trans('admin::app.settings.themes.index.datagrid.inactive'), 'value' => 0]]]);
        }
        if (bouncer()->has_permission('settings.themes.delete')) {
            $this->add_mass_action(['title' => trans('admin::app.settings.themes.index.datagrid.delete'), 'url' => route('admin.settings.themes.mass_delete'), 'method' => 'POST']);
        }
    }
}