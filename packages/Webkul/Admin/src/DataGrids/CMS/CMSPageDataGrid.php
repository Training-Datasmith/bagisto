<?php

declare (strict_types=1);
namespace Webkul\Admin\Data_Grids\CMS;

use Illuminate\Support\Facades\DB;
use Webkul\Data_Grid\Data_Grid;
class Cms_Page_Data_Grid extends Data_Grid
{
    /**
     * Prepare query builder.
     *
     * @return \Illuminate\Database\Query\Builder
     */
    public function prepare_query_builder()
    {
        $current_locale = app()->get_locale();
        $query_builder = DB::table('cms_pages')->select('cms_pages.id', 'cms_page_translations.page_title', 'cms_page_translations.url_key', 'cms_page_translations.locale')->add_select(DB::raw('GROUP_CONCAT(DISTINCT code) as channel'))->join('cms_page_translations', function ($join) use ($current_locale) {
            $join->on('cms_pages.id', '=', 'cms_page_translations.cms_page_id')->where('cms_page_translations.locale', '=', $current_locale);
        })->left_join('cms_page_channels', 'cms_pages.id', '=', 'cms_page_channels.cms_page_id')->left_join('channels', 'cms_page_channels.channel_id', '=', 'channels.id')->group_by('cms_pages.id', 'cms_page_translations.locale');
        $this->add_filter('id', 'cms_pages.id');
        $this->add_filter('channel', 'cms_page_channels.channel_id');
        $this->add_filter('locale', 'cms_page_translations.locale');
        return $query_builder;
    }
    /**
     * Add columns.
     *
     * @return void
     */
    public function prepare_columns()
    {
        $this->add_column(['index' => 'id', 'label' => trans('admin::app.cms.index.datagrid.id'), 'type' => 'integer', 'filterable' => true, 'sortable' => true]);
        $this->add_column(['index' => 'channel', 'label' => trans('admin::app.cms.index.datagrid.channel'), 'type' => 'string', 'filterable' => true, 'filterable_type' => 'dropdown', 'filterable_options' => collect(core()->get_all_channels())->map(fn($channel) => ['label' => $channel->name, 'value' => $channel->id])->values()->to_array(), 'sortable' => true, 'visibility' => false]);
        $this->add_column(['index' => 'page_title', 'label' => trans('admin::app.cms.index.datagrid.page-title'), 'type' => 'string', 'searchable' => true, 'filterable' => true, 'sortable' => true]);
        $this->add_column(['index' => 'url_key', 'label' => trans('admin::app.cms.index.datagrid.url-key'), 'type' => 'string', 'searchable' => true, 'filterable' => true, 'sortable' => true]);
    }
    /**
     * Prepare actions.
     *
     * @return void
     */
    public function prepare_actions()
    {
        $this->add_action(['icon' => 'icon-view', 'title' => trans('admin::app.cms.index.datagrid.view'), 'method' => 'GET', 'index' => 'url_key', 'target' => '_blank', 'url' => function ($row) {
            return route('shop.cms.page', $row->url_key);
        }]);
        if (bouncer()->has_permission('cms.edit')) {
            $this->add_action(['icon' => 'icon-edit', 'title' => trans('admin::app.cms.index.datagrid.edit'), 'method' => 'GET', 'url' => function ($row) {
                return route('admin.cms.edit', $row->id);
            }]);
        }
        if (bouncer()->has_permission('cms.delete')) {
            $this->add_action(['icon' => 'icon-delete', 'title' => trans('admin::app.cms.index.datagrid.delete'), 'method' => 'DELETE', 'url' => function ($row) {
                return route('admin.cms.delete', $row->id);
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
        if (bouncer()->has_permission('cms.delete')) {
            $this->add_mass_action(['title' => trans('admin::app.cms.index.datagrid.delete'), 'method' => 'POST', 'url' => route('admin.cms.mass_delete')]);
        }
    }
}