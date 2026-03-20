<?php

declare (strict_types=1);
namespace Webkul\Admin\Http\Controllers\Marketing\Search_Seo;

use Illuminate\Http\Json_Response;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Storage;
use Webkul\Admin\Data_Grids\Marketing\Search_Seo\Search_Synonym_Data_Grid;
use Webkul\Admin\Http\Controllers\Controller;
use Webkul\Admin\Http\Requests\Mass_Destroy_Request;
use Webkul\Marketing\Repositories\Search_Synonym_Repository;
class Search_Synonym_Controller extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct(public Search_Synonym_Repository $search_synonym_repository)
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
            return datagrid(Search_Synonym_Data_Grid::class)->process();
        }
        return view('admin::marketing.search-seo.search-synonyms.index');
    }
    /**
     * Store a newly created resource in storage.
     */
    public function store(): Json_Response
    {
        $this->validate(request(), ['name' => 'required', 'terms' => 'required']);
        Event::dispatch('marketing.search_seo.search_synonyms.create.before');
        $search_synonym = $this->search_synonym_repository->create(request()->only(['name', 'terms']));
        Event::dispatch('marketing.search_seo.search_synonyms.create.after', $search_synonym);
        return new Json_Response(['message' => trans('admin::app.marketing.search-seo.search-synonyms.index.create.success')]);
    }
    /**
     * Update the specified resource in storage.
     *
     * @param  int  $id
     */
    public function update(): Json_Response
    {
        $id = request()->id;
        $this->validate(request(), ['name' => 'required', 'terms' => 'required']);
        Event::dispatch('marketing.search_seo.search_synonyms.update.before', $id);
        $search_synonym = $this->search_synonym_repository->update(request()->only(['name', 'terms']), $id);
        Event::dispatch('marketing.search_seo.search_synonyms.update.after', $search_synonym);
        return new Json_Response(['message' => trans('admin::app.marketing.search-seo.search-synonyms.index.edit.success')]);
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
            Event::dispatch('marketing.search_seo.search_synonyms.delete.before', $id);
            $this->search_synonym_repository->delete($id);
            Event::dispatch('marketing.search_seo.search_synonyms.delete.after', $id);
            return response()->json(['message' => trans('admin::app.marketing.search-seo.search-synonyms.index.edit.delete-success')], 200);
        } catch (\Exception $e) {
        }
        return response()->json(['message' => trans('admin::app.marketing.search-seo.search-synonyms.delete-failed')], 500);
    }
    /**
     * Mass delete the search terms.
     */
    public function mass_destroy(Mass_Destroy_Request $mass_destroy_request): Json_Response
    {
        $search_synonym_ids = $mass_destroy_request->input('indices');
        try {
            foreach ($search_synonym_ids as $search_synonym_id) {
                $search_synonym = $this->search_synonym_repository->find($search_synonym_id);
                if (isset($search_synonym)) {
                    Event::dispatch('marketing.search_seo.search_synonyms.delete.before', $search_synonym_id);
                    $this->search_synonym_repository->delete($search_synonym_id);
                    Event::dispatch('marketing.search_seo.search_synonyms.delete.after', $search_synonym_id);
                }
            }
            return new Json_Response(['message' => trans('admin::app.marketing.search-seo.search-synonyms.index.datagrid.mass-delete-success')]);
        } catch (\Exception $e) {
            return new Json_Response(['message' => $e->get_message()], 500);
        }
    }
}