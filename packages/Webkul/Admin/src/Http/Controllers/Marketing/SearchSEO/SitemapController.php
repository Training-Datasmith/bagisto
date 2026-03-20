<?php

declare (strict_types=1);
namespace Webkul\Admin\Http\Controllers\Marketing\Search_Seo;

use Illuminate\Http\Json_Response;
use Illuminate\Support\Facades\Event;
use Webkul\Admin\Data_Grids\Marketing\Search_Seo\Sitemap_Data_Grid;
use Webkul\Admin\Http\Controllers\Controller;
use Webkul\Sitemap\Jobs\Process_Sitemap;
use Webkul\Sitemap\Repositories\Sitemap_Repository;
class Sitemap_Controller extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct(public Sitemap_Repository $sitemap_repository)
    {
    }
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\View\View
     */
    public function index()
    {
        if (request()->ajax()) {
            return datagrid(Sitemap_Data_Grid::class)->process();
        }
        return view('admin::marketing.search-seo.sitemaps.index');
    }
    /**
     * Store a newly created resource in storage.
     */
    public function store(): Json_Response
    {
        $this->validate(request(), ['file_name' => 'required|regex:/^[\w\-\.]+$/|ends_with:.xml', 'path' => 'required|starts_with:/|regex:/^(?!.*\/\/)[\w\-\.\/]+$/|ends_with:/']);
        Event::dispatch('marketing.search_seo.sitemap.create.before');
        $sitemap = $this->sitemap_repository->create(request()->only(['file_name', 'path']));
        Process_Sitemap::dispatch($sitemap);
        Event::dispatch('marketing.search_seo.sitemap.create.after', $sitemap);
        return new Json_Response(['message' => trans('admin::app.marketing.search-seo.sitemaps.index.create.success')]);
    }
    /**
     * Update the specified resource in storage.
     *
     * @param  int  $id
     */
    public function update(): Json_Response
    {
        $id = request()->id;
        $this->validate(request(), ['file_name' => 'required|regex:/^[\w\-\.]+$/|ends_with:.xml', 'path' => 'required|starts_with:/|regex:/^(?!.*\/\/)[\w\-\.\/]+$/|ends_with:/']);
        Event::dispatch('marketing.search_seo.sitemap.update.before', $id);
        $sitemap = $this->sitemap_repository->update(request()->only(['file_name', 'path']), $id);
        Process_Sitemap::dispatch($sitemap);
        Event::dispatch('marketing.search_seo.sitemap.update.after', $sitemap);
        return new Json_Response(['message' => trans('admin::app.marketing.search-seo.sitemaps.index.edit.success')]);
    }
    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return void
     */
    public function destroy($id)
    {
        $sitemap = $this->sitemap_repository->find_or_fail($id);
        $sitemap->delete_from_storage();
        try {
            Event::dispatch('marketing.search_seo.sitemap.delete.before', $id);
            $this->sitemap_repository->delete($id);
            Event::dispatch('marketing.search_seo.sitemap.delete.after', $id);
            return response()->json(['message' => trans('admin::app.marketing.search-seo.sitemaps.index.edit.delete-success')]);
        } catch (\Exception $e) {
            return response()->json(['message' => trans('admin::app.marketing.search-seo.sitemaps.delete-failed', ['name' => 'admin::app.marketing.search-seo.sitemaps.index.sitemap'])], 500);
        }
    }
}