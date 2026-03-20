<?php

declare (strict_types=1);
namespace Webkul\Admin\Http\Controllers\Marketing\Search_Seo;

use Illuminate\Http\Json_Response;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Storage;
use Webkul\Admin\Data_Grids\Marketing\Search_Seo\Search_Term_Data_Grid;
use Webkul\Admin\Http\Controllers\Controller;
use Webkul\Admin\Http\Requests\Mass_Destroy_Request;
use Webkul\Marketing\Repositories\Search_Term_Repository;
class Search_Term_Controller extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct(public Search_Term_Repository $search_term_repository)
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
            return datagrid(Search_Term_Data_Grid::class)->process();
        }
        return view('admin::marketing.search-seo.search-terms.index');
    }
    /**
     * Store a newly created resource in storage.
     */
    public function store(): Json_Response
    {
        $this->validate(request(), ['term' => 'required', 'redirect_url' => 'url:http,https', 'channel_id' => 'required|exists:channels,id', 'locale' => 'required|exists:locales,code']);
        Event::dispatch('marketing.search_seo.search_terms.create.before');
        $search_term = $this->search_term_repository->create(request()->only(['term', 'redirect_url', 'channel_id', 'locale']));
        Event::dispatch('marketing.search_seo.search_terms.create.after', $search_term);
        return new Json_Response(['message' => trans('admin::app.marketing.search-seo.search-terms.index.create.success')]);
    }
    /**
     * Update the specified resource in storage.
     *
     * @param  int  $id
     */
    public function update(): Json_Response
    {
        $id = request()->id;
        $this->validate(request(), ['term' => 'required', 'redirect_url' => 'url:http,https', 'channel_id' => 'required|exists:channels,id', 'locale' => 'required|exists:locales,code']);
        Event::dispatch('marketing.search_seo.search_terms.update.before', $id);
        $search_term = $this->search_term_repository->update(request()->only(['term', 'results', 'uses', 'redirect_url', 'channel_id', 'locale']), $id);
        Event::dispatch('marketing.search_seo.search_terms.update.after', $search_term);
        return new Json_Response(['message' => trans('admin::app.marketing.search-seo.search-terms.index.edit.success')]);
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
            Event::dispatch('marketing.search_seo.search_terms.delete.before', $id);
            $this->search_term_repository->delete($id);
            Event::dispatch('marketing.search_seo.search_terms.delete.after', $id);
            return response()->json(['message' => trans('admin::app.marketing.search-seo.search-terms.index.edit.delete-success')], 200);
        } catch (\Exception $e) {
        }
        return response()->json(['message' => trans('admin::app.marketing.search-seo.search-terms.delete-failed')], 500);
    }
    /**
     * Mass delete the search terms.
     */
    public function mass_destroy(Mass_Destroy_Request $mass_destroy_request): Json_Response
    {
        $search_term_ids = $mass_destroy_request->input('indices');
        try {
            foreach ($search_term_ids as $search_term_id) {
                $search_term = $this->search_term_repository->find($search_term_id);
                if (isset($search_term)) {
                    Event::dispatch('marketing.search_seo.search_terms.delete.before', $search_term_id);
                    $this->search_term_repository->delete($search_term_id);
                    Event::dispatch('marketing.search_seo.search_terms.delete.after', $search_term_id);
                }
            }
            return new Json_Response(['message' => trans('admin::app.marketing.search-seo.search-terms.index.datagrid.mass-delete-success')]);
        } catch (\Exception $e) {
            return new Json_Response(['message' => $e->get_message()], 500);
        }
    }
}