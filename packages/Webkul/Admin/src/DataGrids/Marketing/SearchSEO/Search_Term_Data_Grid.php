<?php

declare (strict_types=1);
namespace Webkul\Admin\Data_Grids\Marketing\Search_Seo;

use Illuminate\Support\Facades\DB;
use Webkul\Data_Grid\Data_Grid;
class Search_Term_Data_Grid extends Data_Grid
{
    /**
     * Prepare query builder.
     *
     * @return \Illuminate\Database\Query\Builder
     */
    public function prepare_query_builder()
    {
        $query_builder = DB::table('search_terms')->select('search_terms.id', 'search_terms.term', 'search_terms.results', 'search_terms.uses', 'search_terms.redirect_url', 'search_terms.channel_id', 'channel_translations.name as channel_name', 'search_terms.locale')->left_join('channel_translations', function ($left_join) {
            $left_join->on('search_terms.channel_id', '=', 'channel_translations.channel_id')->where('channel_translations.locale', app()->get_locale());
        });
        $this->add_filter('channel_id', 'search_terms.channel_id');
        $this->add_filter('locale', 'search_terms.locale');
        return $query_builder;
    }
    /**
     * Add Columns.
     *
     * @return void
     */
    public function prepare_columns()
    {
        $this->add_column(['index' => 'id', 'label' => trans('admin::app.marketing.search-seo.search-terms.index.datagrid.id'), 'type' => 'integer', 'filterable' => true, 'sortable' => true]);
        $this->add_column(['index' => 'term', 'label' => trans('admin::app.marketing.search-seo.search-terms.index.datagrid.search-query'), 'type' => 'string', 'searchable' => true, 'filterable' => true, 'sortable' => true]);
        $this->add_column(['index' => 'results', 'label' => trans('admin::app.marketing.search-seo.search-terms.index.datagrid.results'), 'type' => 'integer', 'filterable' => true, 'sortable' => true]);
        $this->add_column(['index' => 'uses', 'label' => trans('admin::app.marketing.search-seo.search-terms.index.datagrid.uses'), 'type' => 'integer', 'filterable' => true, 'sortable' => true]);
        $this->add_column(['index' => 'redirect_url', 'label' => trans('admin::app.marketing.search-seo.search-terms.index.datagrid.redirect-url'), 'type' => 'string', 'filterable' => true, 'sortable' => true]);
        $this->add_column(['index' => 'channel_id', 'label' => trans('admin::app.marketing.search-seo.search-terms.index.datagrid.channel'), 'type' => 'string', 'filterable' => true, 'filterable_type' => 'dropdown', 'filterable_options' => core()->get_all_channels()->map(fn($channel) => ['label' => $channel->name, 'value' => $channel->id])->values()->to_array(), 'sortable' => true]);
        $this->add_column(['index' => 'locale', 'label' => trans('admin::app.marketing.search-seo.search-terms.index.datagrid.locale'), 'type' => 'string', 'filterable' => true, 'filterable_type' => 'dropdown', 'filterable_options' => core()->get_all_locales()->map(fn($locale) => ['label' => $locale->name, 'value' => $locale->code])->values()->to_array(), 'sortable' => true]);
    }
    /**
     * Prepare actions.
     *
     * @return void
     */
    public function prepare_actions()
    {
        if (bouncer()->has_permission('marketing.search_seo.search_terms.edit')) {
            $this->add_action(['index' => 'edit', 'icon' => 'icon-edit', 'title' => trans('admin::app.marketing.search-seo.search-terms.index.datagrid.edit'), 'method' => 'GET', 'route' => 'admin.marketing.search_seo.search_terms.update', 'url' => function ($row) {
                return route('admin.marketing.search_seo.search_terms.update', $row->id);
            }]);
        }
        if (bouncer()->has_permission('marketing.search_seo.search_terms.delete')) {
            $this->add_action(['index' => 'delete', 'icon' => 'icon-delete', 'title' => trans('admin::app.marketing.search-seo.search-terms.index.datagrid.delete'), 'method' => 'DELETE', 'url' => function ($row) {
                return route('admin.marketing.search_seo.search_terms.delete', $row->id);
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
        if (bouncer()->has_permission('marketing.search_terms.delete')) {
            $this->add_mass_action(['title' => trans('admin::app.marketing.search-seo.search-terms.index.datagrid.delete'), 'method' => 'POST', 'url' => route('admin.marketing.search_seo.search_terms.mass_delete')]);
        }
    }
}