<?php

declare (strict_types=1);
namespace Webkul\Admin\Http\Controllers\Settings;

use Illuminate\Http\Json_Response;
use Webkul\Admin\Data_Grids\Settings\Currency_Data_Grid;
use Webkul\Admin\Http\Controllers\Controller;
use Webkul\Core\Enums\Currency_Position_Enum;
use Webkul\Core\Repositories\Currency_Repository;
use Webkul\Core\Rules\Code;
class Currency_Controller extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct(protected Currency_Repository $currency_repository)
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
            return datagrid(Currency_Data_Grid::class)->process();
        }
        return view('admin::settings.currencies.index', ['currencyPositions' => Currency_Position_Enum::options()]);
    }
    /**
     * Store a newly created resource in storage.
     */
    public function store(): Json_Response
    {
        $this->validate(request(), ['code' => ['required', 'min:3', 'max:3', 'unique:currencies,code', new Code()], 'name' => 'required']);
        $this->currency_repository->create(request()->only(['code', 'name', 'symbol', 'decimal', 'group_separator', 'decimal_separator', 'currency_position']));
        return new Json_Response(['message' => trans('admin::app.settings.currencies.index.create-success')]);
    }
    /**
     * Currency details.
     */
    public function edit(int $id): Json_Response
    {
        $currency = $this->currency_repository->find_or_fail($id);
        return new Json_Response($currency);
    }
    /**
     * Update the specified resource in storage.
     */
    public function update(): Json_Response
    {
        $id = request('id');
        $this->validate(request(), ['name' => 'required']);
        $this->currency_repository->update(request()->only(['name', 'symbol', 'decimal', 'group_separator', 'decimal_separator', 'currency_position']), $id);
        return new Json_Response(['message' => trans('admin::app.settings.currencies.index.update-success')]);
    }
    /**
     * Remove the specified resource from storage.
     */
    public function destroy(int $id): Json_Response
    {
        $this->currency_repository->find_or_fail($id);
        if ($this->currency_repository->count() == 1) {
            return new Json_Response(['message' => trans('admin::app.settings.currencies.index.last-delete-error')], 400);
        }
        try {
            $this->currency_repository->delete($id);
            return new Json_Response(['message' => trans('admin::app.settings.currencies.index.delete-success')], 200);
        } catch (\Exception $e) {
            report($e);
            return new Json_Response(['message' => trans('admin::app.settings.currencies.index.delete-failed')], 500);
        }
    }
}