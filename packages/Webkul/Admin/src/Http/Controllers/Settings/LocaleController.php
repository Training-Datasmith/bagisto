<?php

declare (strict_types=1);
namespace Webkul\Admin\Http\Controllers\Settings;

use Illuminate\Http\Json_Response;
use Webkul\Admin\Data_Grids\Settings\Locales_Data_Grid;
use Webkul\Admin\Http\Controllers\Controller;
use Webkul\Core\Repositories\Locale_Repository;
class Locale_Controller extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct(protected Locale_Repository $locale_repository)
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
            return datagrid(Locales_Data_Grid::class)->process();
        }
        return view('admin::settings.locales.index');
    }
    /**
     * Store a newly created resource in storage.
     */
    public function store(): Json_Response
    {
        $this->validate(request(), ['code' => ['required', 'unique:locales,code', new \Webkul\Core\Rules\Code()], 'name' => 'required', 'direction' => 'required|in:ltr,rtl', 'logo_path' => 'array', 'logo_path.*' => 'image|extensions:jpeg,jpg,png,svg,webp']);
        $this->locale_repository->create(request()->only(['code', 'name', 'direction', 'logo_path']));
        return new Json_Response(['message' => trans('admin::app.settings.locales.index.create-success')]);
    }
    /**
     * Show the form for editing the specified resource.
     */
    public function edit(int $id): Json_Response
    {
        $locale = $this->locale_repository->find_or_fail($id);
        return new Json_Response(['data' => $locale]);
    }
    /**
     * Update the specified resource in storage.
     */
    public function update(): Json_Response
    {
        $this->validate(request(), ['name' => 'required', 'direction' => 'required|in:ltr,rtl', 'logo_path' => 'array', 'logo_path.*' => 'image|extensions:jpeg,jpg,png,svg,webp']);
        $this->locale_repository->update(request()->only(['name', 'direction', 'logo_path']), request()->id);
        return new Json_Response(['message' => trans('admin::app.settings.locales.index.update-success')]);
    }
    /**
     * Remove the specified resource from storage.
     */
    public function destroy(int $id): Json_Response
    {
        $locale = $this->locale_repository->find_or_fail($id);
        if ($locale->count() == 1) {
            return response()->json(['message' => trans('admin::app.settings.locales.index.last-delete-error')], 400);
        }
        try {
            $locale->delete($id);
            return new Json_Response(['message' => trans('admin::app.settings.locales.index.delete-success')]);
        } catch (\Exception $e) {
            return response()->json(['message' => trans('admin::app.settings.locales.index.delete-failed')], 500);
        }
    }
}