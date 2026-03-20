<?php

declare (strict_types=1);
namespace Webkul\Admin\Http\Controllers\Marketing\Promotions;

use Illuminate\Http\Json_Response;
use Illuminate\Support\Facades\Event;
use Webkul\Admin\Data_Grids\Marketing\Promotions\Catalog_Rule_Data_Grid;
use Webkul\Admin\Http\Controllers\Controller;
use Webkul\Admin\Http\Requests\Catalog_Rule_Request;
use Webkul\Catalog_Rule\Repositories\Catalog_Rule_Repository;
class Catalog_Rule_Controller extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct(protected Catalog_Rule_Repository $catalog_rule_repository)
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
            return datagrid(Catalog_Rule_Data_Grid::class)->process();
        }
        return view('admin::marketing.promotions.catalog-rules.index');
    }
    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\View\View
     */
    public function create()
    {
        return view('admin::marketing.promotions.catalog-rules.create');
    }
    /**
     * Store a newly created resource in storage.
     *
     * @return \Illuminate\Http\Response
     */
    public function store(Catalog_Rule_Request $catalog_rule_request)
    {
        Event::dispatch('promotions.catalog_rule.create.before');
        $catalog_rule = $this->catalog_rule_repository->create($catalog_rule_request->all());
        Event::dispatch('promotions.catalog_rule.create.after', $catalog_rule);
        session()->flash('success', trans('admin::app.marketing.promotions.catalog-rules.create-success'));
        return redirect()->route('admin.marketing.promotions.catalog_rules.index');
    }
    /**
     * Show the form for editing the specified resource.
     *
     * @return \Illuminate\View\View
     */
    public function edit(int $id)
    {
        $catalog_rule = $this->catalog_rule_repository->find_or_fail($id);
        return view('admin::marketing.promotions.catalog-rules.edit', compact('catalogRule'));
    }
    /**
     * Update the specified resource in storage.
     *
     * @return \Illuminate\Http\Response
     */
    public function update(Catalog_Rule_Request $catalog_rule_request, int $id)
    {
        $this->catalog_rule_repository->find_or_fail($id);
        Event::dispatch('promotions.catalog_rule.update.before', $id);
        $catalog_rule = $this->catalog_rule_repository->update($catalog_rule_request->all(), $id);
        Event::dispatch('promotions.catalog_rule.update.after', $catalog_rule);
        session()->flash('success', trans('admin::app.marketing.promotions.catalog-rules.update-success'));
        return redirect()->route('admin.marketing.promotions.catalog_rules.index');
    }
    /**
     * Remove the specified resource from storage.
     */
    public function destroy(int $id): Json_Response
    {
        $this->catalog_rule_repository->find_or_fail($id);
        try {
            Event::dispatch('promotions.catalog_rule.delete.before', $id);
            $this->catalog_rule_repository->delete($id);
            Event::dispatch('promotions.catalog_rule.delete.after', $id);
            return new Json_Response(['message' => trans('admin::app.marketing.promotions.catalog-rules.delete-success')]);
        } catch (\Exception $e) {
            return new Json_Response(['message' => trans('admin::app.marketing.promotions.catalog-rules.delete-failed')], 400);
        }
    }
}