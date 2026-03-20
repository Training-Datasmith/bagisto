<?php

declare (strict_types=1);
namespace Webkul\Admin\Http\Controllers\Marketing\Search_Seo;

use Illuminate\Http\Json_Response;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Storage;
use Webkul\Admin\Data_Grids\Marketing\Search_Seo\Url_Rewrite_Data_Grid;
use Webkul\Admin\Http\Controllers\Controller;
use Webkul\Admin\Http\Requests\Mass_Destroy_Request;
use Webkul\Marketing\Repositories\Url_Rewrite_Repository;
class Url_Rewrite_Controller extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct(public Url_Rewrite_Repository $url_rewrite_repository)
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
            return datagrid(Url_Rewrite_Data_Grid::class)->process();
        }
        return view('admin::marketing.search-seo.url-rewrites.index');
    }
    /**
     * Store a newly created resource in storage.
     */
    public function store(): Json_Response
    {
        $this->validate(request(), ['entity_type' => 'required|in:category,product,cms_page', 'request_path' => 'required', 'target_path' => 'required', 'redirect_type' => 'required|in:301,302', 'locale' => 'required|exists:locales,code']);
        Event::dispatch('marketing.search_seo.url_rewrites.create.before');
        $url_rewrite = $this->url_rewrite_repository->create(request()->only(['entity_type', 'request_path', 'target_path', 'redirect_type', 'locale']));
        Event::dispatch('marketing.search_seo.url_rewrites.create.after', $url_rewrite);
        return new Json_Response(['message' => trans('admin::app.marketing.search-seo.url-rewrites.index.create.success')]);
    }
    /**
     * Update the specified resource in storage.
     *
     * @param  int  $id
     */
    public function update(): Json_Response
    {
        $id = request()->id;
        $this->validate(request(), ['entity_type' => 'required|in:category,product,cms_page', 'request_path' => 'required', 'target_path' => 'required', 'redirect_type' => 'required|in:301,302', 'locale' => 'required|exists:locales,code']);
        Event::dispatch('marketing.search_seo.url_rewrites.update.before', $id);
        $url_rewrite = $this->url_rewrite_repository->update(request()->only(['entity_type', 'request_path', 'target_path', 'redirect_type', 'locale']), $id);
        Event::dispatch('marketing.search_seo.url_rewrites.update.after', $url_rewrite);
        return new Json_Response(['message' => trans('admin::app.marketing.search-seo.url-rewrites.index.edit.success')]);
    }
    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return void
     */
    public function destroy($id)
    {
        try {
            Event::dispatch('marketing.search_seo.url_rewrites.delete.before', $id);
            $this->url_rewrite_repository->delete($id);
            Event::dispatch('marketing.search_seo.url_rewrites.delete.after', $id);
            return response()->json(['message' => trans('admin::app.marketing.search-seo.url-rewrites.index.edit.delete-success')], 200);
        } catch (\Exception $e) {
        }
        return response()->json(['message' => trans('admin::app.marketing.search-seo.url-rewrites.delete-failed')], 500);
    }
    /**
     * Mass delete the search terms.
     */
    public function mass_destroy(Mass_Destroy_Request $mass_destroy_request): Json_Response
    {
        $url_rewrite_ids = $mass_destroy_request->input('indices');
        try {
            foreach ($url_rewrite_ids as $url_rewrite_id) {
                $url_rewrite = $this->url_rewrite_repository->find($url_rewrite_id);
                if (isset($url_rewrite)) {
                    Event::dispatch('marketing.search_seo.url_rewrites.delete.before', $url_rewrite_id);
                    $this->url_rewrite_repository->delete($url_rewrite_id);
                    Event::dispatch('marketing.search_seo.url_rewrites.delete.after', $url_rewrite_id);
                }
            }
            return new Json_Response(['message' => trans('admin::app.marketing.search-seo.url-rewrites.index.datagrid.mass-delete-success')]);
        } catch (\Exception $e) {
            return new Json_Response(['message' => $e->get_message()], 500);
        }
    }
}