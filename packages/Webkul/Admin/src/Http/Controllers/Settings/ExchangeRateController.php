<?php

declare (strict_types=1);
namespace Webkul\Admin\Http\Controllers\Settings;

use Illuminate\Http\Json_Response;
use Illuminate\Support\Facades\Event;
use Webkul\Admin\Data_Grids\Settings\Exchange_Rates_Data_Grid;
use Webkul\Admin\Http\Controllers\Controller;
use Webkul\Core\Repositories\Currency_Repository;
use Webkul\Core\Repositories\Exchange_Rate_Repository;
class Exchange_Rate_Controller extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct(protected Exchange_Rate_Repository $exchange_rate_repository, protected Currency_Repository $currency_repository)
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
            return datagrid(Exchange_Rates_Data_Grid::class)->process();
        }
        $base_currency = core()->get_base_currency();
        $currencies = $this->currency_repository->with('exchange_rate')->where('id', '!=', $base_currency->id)->get();
        return view('admin::settings.exchange-rates.index', compact('currencies'));
    }
    /**
     * Store a newly created resource in storage.
     */
    public function store(): Json_Response
    {
        $base_currency = core()->get_base_currency();
        $this->validate(request(), ['target_currency' => ['required', 'unique:currency_exchange_rates,target_currency', 'not_in:' . $base_currency->id], 'rate' => 'required|numeric']);
        Event::dispatch('core.exchange_rate.create.before');
        $exchange_rate = $this->exchange_rate_repository->create(request()->only(['target_currency', 'rate']));
        Event::dispatch('core.exchange_rate.create.after', $exchange_rate);
        return new Json_Response(['message' => trans('admin::app.settings.exchange-rates.index.create-success')]);
    }
    /**
     * Show the form for editing the specified resource.
     */
    public function edit(int $id): Json_Response
    {
        $base_currency = core()->get_base_currency();
        $currencies = $this->currency_repository->with('exchange_rate')->where('id', '!=', $base_currency->id)->get();
        $exchange_rate = $this->exchange_rate_repository->find_or_fail($id);
        return new Json_Response(['data' => ['currencies' => $currencies, 'exchangeRate' => $exchange_rate]]);
    }
    /**
     * Update the specified resource in storage.
     */
    public function update(): Json_Response
    {
        $base_currency = core()->get_base_currency();
        $this->validate(request(), ['target_currency' => ['required', 'unique:currency_exchange_rates,target_currency,' . request()->id, 'not_in:' . $base_currency->id], 'rate' => 'required|numeric']);
        Event::dispatch('core.exchange_rate.update.before', request()->id);
        $exchange_rate = $this->exchange_rate_repository->update(request()->only(['target_currency', 'rate']), request()->id);
        Event::dispatch('core.exchange_rate.update.after', $exchange_rate);
        return new Json_Response(['message' => trans('admin::app.settings.exchange-rates.index.update-success')]);
    }
    /**
     * Update Rates Using Exchange Rates API
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function update_rates()
    {
        try {
            app(config('services.exchange_api.' . config('services.exchange_api.default') . '.class'))->update_rates();
            session()->flash('success', trans('admin::app.settings.exchange-rates.index.update-success'));
        } catch (\Exception $e) {
            session()->flash('error', $e->get_message());
        }
        return redirect()->route('admin.settings.exchange_rates.index');
    }
    /**
     * Remove the specified resource from storage.
     */
    public function destroy(int $id): Json_Response
    {
        try {
            $this->exchange_rate_repository->find_or_fail($id);
            Event::dispatch('core.exchange_rate.delete.before', $id);
            $this->exchange_rate_repository->delete($id);
            Event::dispatch('core.exchange_rate.delete.after', $id);
            return new Json_Response(['message' => trans('admin::app.settings.exchange-rates.index.delete-success')], 200);
        } catch (\Exception $e) {
            report($e);
        }
        return new Json_Response(['message' => trans('admin::app.settings.exchange-rates.index.delete-error')], 500);
    }
}