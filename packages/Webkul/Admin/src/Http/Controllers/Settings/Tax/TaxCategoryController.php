<?php

declare (strict_types=1);
namespace Webkul\Admin\Http\Controllers\Settings\Tax;

use Illuminate\Http\Json_Response;
use Illuminate\Support\Facades\Event;
use Webkul\Admin\Data_Grids\Settings\Tax_Category_Data_Grid;
use Webkul\Admin\Http\Controllers\Controller;
use Webkul\Admin\Http\Resources\Tax_Category_Resource;
use Webkul\Tax\Repositories\Tax_Category_Repository;
use Webkul\Tax\Repositories\Tax_Rate_Repository;
class Tax_Category_Controller extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct(protected Tax_Category_Repository $tax_category_repository, protected Tax_Rate_Repository $tax_rate_repository)
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
            return datagrid(Tax_Category_Data_Grid::class)->process();
        }
        return view('admin::settings.taxes.categories.index')->with('taxRates', $this->tax_rate_repository->all());
    }
    /**
     * Function to create the tax category.
     *
     * @return \Illuminate\Http\Response
     */
    public function store(): Json_Response
    {
        $this->validate(request(), ['code' => 'required|string|unique:tax_categories,code', 'name' => 'required|string', 'description' => 'required|string', 'taxrates' => 'array|required']);
        Event::dispatch('tax.category.create.before');
        $data = request()->only(['code', 'name', 'description', 'taxrates']);
        $tax_category = $this->tax_category_repository->create($data);
        $tax_category->tax_rates()->sync($data['taxrates']);
        Event::dispatch('tax.category.create.after', $tax_category);
        return new Json_Response(['message' => trans('admin::app.settings.taxes.categories.index.create-success')]);
    }
    /**
     * Tax Category Details
     */
    public function edit(int $id): Tax_Category_Resource
    {
        $tax_category = $this->tax_category_repository->find_or_fail($id);
        return new Tax_Category_Resource($tax_category);
    }
    /**
     * To update the tax category.
     */
    public function update(): Json_Response
    {
        $id = request()->id;
        $this->validate(request(), ['code' => 'required|string|unique:tax_categories,code,' . $id, 'name' => 'required|string', 'description' => 'required|string', 'taxrates' => 'array|required']);
        Event::dispatch('tax.category.update.before', $id);
        $data = request()->only(['code', 'name', 'description', 'taxrates']);
        $tax_category = $this->tax_category_repository->update($data, $id);
        $tax_category->tax_rates()->sync($data['taxrates']);
        Event::dispatch('tax.category.update.after', $tax_category);
        return new Json_Response(['message' => trans('admin::app.settings.taxes.categories.index.update-success')]);
    }
    /**
     * Remove the specified resource from storage.
     */
    public function destroy(int $id): Json_Response
    {
        try {
            $tax_category = $this->tax_category_repository->find_or_fail($id);
            if (!$tax_category->tax_rates()->count()) {
                Event::dispatch('tax.category.delete.before', $id);
                $tax_category->delete();
                Event::dispatch('tax.category.delete.after', $id);
                return new Json_Response(['message' => trans('admin::app.settings.taxes.categories.index.delete-success')]);
            }
            return new Json_Response(['message' => trans('admin::app.settings.taxes.categories.index.can-not-delete')], 400);
        } catch (\Exception $e) {
            return new Json_Response(['message' => trans('admin::app.settings.taxes.categories.index.delete-failed')], 500);
        }
    }
}