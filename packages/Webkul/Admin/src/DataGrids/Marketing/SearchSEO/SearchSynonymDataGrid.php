<?php

declare (strict_types=1);
namespace Webkul\Admin\Data_Grids\Marketing\Search_Seo;

use Illuminate\Support\Facades\DB;
use Webkul\Data_Grid\Data_Grid;
class Search_Synonym_Data_Grid extends Data_Grid
{
    /**
     * Prepare query builder.
     *
     * @return \Illuminate\Database\Query\Builder
     */
    public function prepare_query_builder()
    {
        return DB::table('search_synonyms')->add_select('id', 'name', 'terms');
    }
    /**
     * Add Columns.
     *
     * @return void
     */
    public function prepare_columns()
    {
        $this->add_column(['index' => 'id', 'label' => trans('admin::app.marketing.search-seo.search-synonyms.index.datagrid.id'), 'type' => 'integer', 'filterable' => true, 'sortable' => true]);
        $this->add_column(['index' => 'name', 'label' => trans('admin::app.marketing.search-seo.search-synonyms.index.datagrid.name'), 'type' => 'string', 'searchable' => true, 'filterable' => true, 'sortable' => true]);
        $this->add_column(['index' => 'terms', 'label' => trans('admin::app.marketing.search-seo.search-synonyms.index.datagrid.terms'), 'type' => 'string', 'searchable' => true, 'filterable' => true, 'sortable' => true]);
    }
    /**
     * Prepare actions.
     *
     * @return void
     */
    public function prepare_actions()
    {
        if (bouncer()->has_permission('marketing.search_seo.search_synonyms.edit')) {
            $this->add_action(['index' => 'edit', 'icon' => 'icon-edit', 'title' => trans('admin::app.marketing.search-seo.search-synonyms.index.datagrid.edit'), 'method' => 'GET', 'route' => 'admin.marketing.search_seo.search_synonyms.update', 'url' => function ($row) {
                return route('admin.marketing.search_seo.search_synonyms.update', $row->id);
            }]);
        }
        if (bouncer()->has_permission('marketing.search_seo.search_synonyms.delete')) {
            $this->add_action(['index' => 'delete', 'icon' => 'icon-delete', 'title' => trans('admin::app.marketing.search-seo.search-synonyms.index.datagrid.delete'), 'method' => 'DELETE', 'url' => function ($row) {
                return route('admin.marketing.search_seo.search_synonyms.delete', $row->id);
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
        if (bouncer()->has_permission('marketing.search_synonyms.delete')) {
            $this->add_mass_action(['title' => trans('admin::app.marketing.search-seo.search-synonyms.index.datagrid.delete'), 'method' => 'POST', 'url' => route('admin.marketing.search_seo.search_synonyms.mass_delete')]);
        }
    }
}