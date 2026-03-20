<?php

declare (strict_types=1);
namespace Webkul\Admin\Data_Grids\Marketing\Search_Seo;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Webkul\Data_Grid\Data_Grid;
class Sitemap_Data_Grid extends Data_Grid
{
    /**
     * Prepare query builder.
     *
     * @return \Illuminate\Database\Query\Builder
     */
    public function prepare_query_builder()
    {
        return DB::table('sitemaps')->add_select('id', 'file_name', 'path', 'path as url');
    }
    /**
     * Add Columns.
     *
     * @return void
     */
    public function prepare_columns()
    {
        $this->add_column(['index' => 'id', 'label' => trans('admin::app.marketing.search-seo.sitemaps.index.datagrid.id'), 'type' => 'integer', 'filterable' => true, 'sortable' => true]);
        $this->add_column(['index' => 'file_name', 'label' => trans('admin::app.marketing.search-seo.sitemaps.index.datagrid.file-name'), 'type' => 'string', 'searchable' => true, 'filterable' => true, 'sortable' => true]);
        $this->add_column(['index' => 'path', 'label' => trans('admin::app.marketing.search-seo.sitemaps.index.datagrid.path'), 'type' => 'string', 'filterable' => true, 'sortable' => true]);
        $this->add_column(['index' => 'url', 'label' => trans('admin::app.marketing.search-seo.sitemaps.index.datagrid.link-for-google'), 'type' => 'string', 'closure' => function ($row) {
            return Storage::disk('public')->url(clean_path($row->path . '/' . $row->file_name));
        }]);
    }
    /**
     * Prepare actions.
     *
     * @return void
     */
    public function prepare_actions()
    {
        if (bouncer()->has_permission('marketing.search_seo.sitemaps.edit')) {
            $this->add_action(['index' => 'edit', 'icon' => 'icon-edit', 'title' => trans('admin::app.marketing.search-seo.sitemaps.index.datagrid.edit'), 'method' => 'GET', 'route' => 'admin.marketing.search_seo.sitemaps.update', 'url' => function ($row) {
                return route('admin.marketing.search_seo.sitemaps.update', $row->id);
            }]);
        }
        if (bouncer()->has_permission('marketing.search_seo.sitemaps.delete')) {
            $this->add_action(['index' => 'delete', 'icon' => 'icon-delete', 'title' => trans('admin::app.marketing.search-seo.sitemaps.index.datagrid.delete'), 'method' => 'DELETE', 'url' => function ($row) {
                return route('admin.marketing.search_seo.sitemaps.delete', $row->id);
            }]);
        }
    }
}