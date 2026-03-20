<?php

declare (strict_types=1);
namespace Webkul\Admin\Http\Controllers\Catalog;

use Illuminate\Http\Json_Response;
use Illuminate\Support\Facades\Event;
use Webkul\Admin\Data_Grids\Catalog\Attribute_Family_Data_Grid;
use Webkul\Admin\Http\Controllers\Controller;
use Webkul\Attribute\Repositories\Attribute_Family_Repository;
use Webkul\Attribute\Repositories\Attribute_Repository;
use Webkul\Core\Rules\Code;
class Attribute_Family_Controller extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct(protected Attribute_Family_Repository $attribute_family_repository, protected Attribute_Repository $attribute_repository)
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
            return datagrid(Attribute_Family_Data_Grid::class)->process();
        }
        return view('admin::catalog.families.index');
    }
    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\View\View
     */
    public function create()
    {
        $attribute_family = $this->attribute_family_repository->with(['attribute_groups.custom_attributes'])->find_one_by_field('code', 'default');
        $custom_attributes = $this->attribute_repository->all(['id', 'code', 'admin_name', 'type', 'is_user_defined']);
        return view('admin::catalog.families.create', compact('attributeFamily', 'customAttributes'));
    }
    /**
     * Store a newly created resource in storage.
     *
     * @return \Illuminate\Http\Response
     */
    public function store()
    {
        $this->validate(request(), ['code' => ['required', 'unique:attribute_families,code', new Code()], 'name' => 'required', 'attribute_groups.*.code' => 'required', 'attribute_groups.*.name' => 'required', 'attribute_groups.*.column' => 'required|in:1,2']);
        Event::dispatch('catalog.attribute_family.create.before');
        $attribute_family = $this->attribute_family_repository->create(['attribute_groups' => request('attribute_groups'), 'code' => request('code'), 'name' => request('name')]);
        Event::dispatch('catalog.attribute_family.create.after', $attribute_family);
        session()->flash('success', trans('admin::app.catalog.families.create-success'));
        return redirect()->route('admin.catalog.families.index');
    }
    /**
     * Show the form for editing the specified resource.
     *
     * @return \Illuminate\View\View
     */
    public function edit(int $id)
    {
        $attribute_family = $this->attribute_family_repository->with(['attribute_groups.custom_attributes'])->find_or_fail($id, ['*']);
        $custom_attributes = $this->attribute_repository->all(['id', 'code', 'admin_name', 'type', 'is_user_defined']);
        return view('admin::catalog.families.edit', compact('attributeFamily', 'customAttributes'));
    }
    /**
     * Update the specified resource in storage.
     *
     * @return \Illuminate\Http\Response
     */
    public function update(int $id)
    {
        $this->validate(request(), ['code' => ['required', 'unique:attribute_families,code,' . $id, new Code()], 'name' => 'required', 'attribute_groups.*.code' => 'required', 'attribute_groups.*.name' => 'required', 'attribute_groups.*.column' => 'required|in:1,2']);
        Event::dispatch('catalog.attribute_family.update.before', $id);
        $attribute_family = $this->attribute_family_repository->update(['attribute_groups' => request('attribute_groups'), 'code' => request('code'), 'name' => request('name')], $id);
        Event::dispatch('catalog.attribute_family.update.after', $attribute_family);
        session()->flash('success', trans('admin::app.catalog.families.update-success'));
        return redirect()->route('admin.catalog.families.index');
    }
    /**
     * Remove the specified resource from storage.
     */
    public function destroy(int $id): Json_Response
    {
        $attribute_family = $this->attribute_family_repository->find_or_fail($id);
        if ($this->attribute_family_repository->count() == 1) {
            return new Json_Response(['message' => trans('admin::app.catalog.families.last-delete-error')], 400);
        }
        if ($attribute_family->products()->count()) {
            return new Json_Response(['message' => trans('admin::app.catalog.families.attribute-product-error')], 400);
        }
        try {
            Event::dispatch('catalog.attribute_family.delete.before', $id);
            $this->attribute_family_repository->delete($id);
            Event::dispatch('catalog.attribute_family.delete.after', $id);
            return new Json_Response(['message' => trans('admin::app.catalog.families.delete-success')]);
        } catch (\Exception $e) {
            report($e);
        }
        return new Json_Response(['message' => trans('admin::app.catalog.families.delete-failed', ['name' => 'admin::app.catalog.families.family'])], 500);
    }
}