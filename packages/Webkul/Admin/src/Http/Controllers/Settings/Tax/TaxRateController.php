<?php

declare (strict_types=1);
namespace Webkul\Admin\Http\Controllers\Settings\Tax;

use Illuminate\Http\Json_Response;
use Illuminate\Support\Facades\Event;
use Webkul\Admin\Data_Grids\Settings\Tax_Rate_Data_Grid;
use Webkul\Admin\Http\Controllers\Controller;
use Webkul\Admin\Http\Requests\Tax_Rate_Request;
use Webkul\Tax\Repositories\Tax_Rate_Repository;
class Tax_Rate_Controller extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct(protected Tax_Rate_Repository $tax_rate_repository)
    {
    }
    /**
     * Display a listing resource for the available tax rates.
     *
     * @return \Illuminate\View\View
     */
    public function index()
    {
        if (request()->ajax()) {
            return datagrid(Tax_Rate_Data_Grid::class)->process();
        }
        return view('admin::settings.taxes.rates.index');
    }
    /**
     * Display a create form for tax rate.
     *
     * @return \Illuminate\View\View
     */
    public function create()
    {
        if (request()->ajax()) {
            return datagrid(Tax_Rate_Data_Grid::class)->process();
        }
        return view('admin::settings.taxes.rates.create');
    }
    /**
     * Create the tax rate.
     *
     * @return \Illuminate\Http\Response
     */
    public function store(Tax_Rate_Request $request)
    {
        Event::dispatch('tax.rate.create.before');
        $tax_rate = $this->tax_rate_repository->create($request->only(['identifier', 'country', 'state', 'tax_rate', 'zip_code', 'is_zip', 'zip_from', 'zip_to']));
        Event::dispatch('tax.rate.create.after', $tax_rate);
        session()->flash('success', trans('admin::app.settings.taxes.rates.create-success'));
        return redirect()->route('admin.settings.taxes.rates.index');
    }
    /**
     * Show the edit form for the previously created tax rates.
     *
     * @return \Illuminate\View\View
     */
    public function edit(int $id)
    {
        $tax_rate = $this->tax_rate_repository->find_or_fail($id);
        return view('admin::settings.taxes.rates.edit')->with('taxRate', $tax_rate);
    }
    /**
     * Edit the previous tax rate.
     *
     * @return \Illuminate\Http\Response
     */
    public function update(Tax_Rate_Request $request, int $id)
    {
        Event::dispatch('tax.rate.update.before', $id);
        $tax_rate = $this->tax_rate_repository->update($request->only(['identifier', 'country', 'state', 'tax_rate', 'zip_code', 'is_zip', 'zip_from', 'zip_to']), $id);
        Event::dispatch('tax.rate.update.after', $tax_rate);
        session()->flash('success', trans('admin::app.settings.taxes.rates.update-success'));
        return redirect()->route('admin.settings.taxes.rates.index');
    }
    /**
     * Remove the specified resource from storage.
     */
    public function destroy(int $id): Json_Response
    {
        try {
            Event::dispatch('tax.rate.delete.before', $id);
            $this->tax_rate_repository->delete($id);
            Event::dispatch('tax.rate.delete.after', $id);
            return new Json_Response(['message' => trans('admin::app.settings.taxes.rates.delete-success')]);
        } catch (\Exception $e) {
        }
        return new Json_Response(['message' => trans('admin::app.settings.taxes.rates.delete-failed')], 500);
    }
}