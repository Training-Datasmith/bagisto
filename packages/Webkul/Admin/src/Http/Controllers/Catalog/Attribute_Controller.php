<?php

declare (strict_types=1);
namespace Webkul\Admin\Http\Controllers\Catalog;

use Illuminate\Http\Json_Response;
use Illuminate\Support\Facades\Event;
use Webkul\Admin\Data_Grids\Catalog\Attribute_Data_Grid;
use Webkul\Admin\Http\Controllers\Controller;
use Webkul\Admin\Http\Requests\Mass_Destroy_Request;
use Webkul\Attribute\Enums\Attribute_Type_Enum;
use Webkul\Attribute\Enums\Swatch_Type_Enum;
use Webkul\Attribute\Enums\Validation_Enum;
use Webkul\Attribute\Repositories\Attribute_Repository;
use Webkul\Core\Rules\Code;
use Webkul\Product\Repositories\Product_Repository;
class Attribute_Controller extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct(protected Attribute_Repository $attribute_repository, protected Product_Repository $product_repository)
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
            return datagrid(Attribute_Data_Grid::class)->process();
        }
        return view('admin::catalog.attributes.index');
    }
    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\View\View
     */
    public function create()
    {
        $locales = core()->get_all_locales();
        $attribute_types = Attribute_Type_Enum::get_values();
        $swatch_types = Swatch_Type_Enum::get_values();
        $validations = Validation_Enum::get_values();
        return view('admin::catalog.attributes.create', compact('locales', 'attributeTypes', 'swatchTypes', 'validations'));
    }
    /**
     * Store a newly created resource in storage.
     *
     * @return \Illuminate\Http\Response
     */
    public function store()
    {
        $rules = ['code' => ['required', 'not_in:type,attribute_family_id', 'unique:attributes,code', new Code()], 'admin_name' => 'required', 'type' => 'required'];
        if (request('type') === 'boolean') {
            $rules['default_value'] = 'in:0,1';
        }
        $this->validate(request(), $rules);
        $request_data = request()->all();
        $request_data['default_value'] ??= null;
        Event::dispatch('catalog.attribute.create.before');
        $attribute = $this->attribute_repository->create($request_data);
        Event::dispatch('catalog.attribute.create.after', $attribute);
        session()->flash('success', trans('admin::app.catalog.attributes.create-success'));
        return redirect()->route('admin.catalog.attributes.index');
    }
    /**
     * Show the form for editing the specified resource.
     *
     * @return \Illuminate\View\View
     */
    public function edit(int $id)
    {
        $attribute = $this->attribute_repository->find_or_fail($id);
        $locales = core()->get_all_locales();
        $attribute_types = Attribute_Type_Enum::get_values();
        $swatch_types = Swatch_Type_Enum::get_values();
        $validations = Validation_Enum::get_values();
        return view('admin::catalog.attributes.edit', compact('attribute', 'locales', 'attributeTypes', 'swatchTypes', 'validations'));
    }
    /**
     * Get attribute options associated with attribute.
     *
     * @return \Illuminate\View\View
     */
    public function get_attribute_options(int $id)
    {
        $attribute = $this->attribute_repository->find_or_fail($id);
        return $attribute->options()->order_by('sort_order')->get();
    }
    /**
     * Update the specified resource in storage.
     *
     * @return \Illuminate\Http\Response
     */
    public function update(int $id)
    {
        $rules = ['code' => ['required', 'unique:attributes,code,' . $id, new Code()], 'admin_name' => 'required', 'type' => 'required'];
        if (request('type') === 'boolean') {
            $rules['default_value'] = 'in:0,1';
        }
        $this->validate(request(), $rules);
        $request_data = request()->all();
        $request_data['default_value'] ??= null;
        Event::dispatch('catalog.attribute.update.before', $id);
        $attribute = $this->attribute_repository->update($request_data, $id);
        Event::dispatch('catalog.attribute.update.after', $attribute);
        session()->flash('success', trans('admin::app.catalog.attributes.update-success'));
        return redirect()->route('admin.catalog.attributes.index');
    }
    /**
     * Remove the specified resource from storage.
     */
    public function destroy(int $id): Json_Response
    {
        $attribute = $this->attribute_repository->find_or_fail($id);
        if (!$attribute->is_user_defined) {
            return response()->json(['message' => trans('admin::app.catalog.attributes.user-define-error')], 400);
        }
        try {
            Event::dispatch('catalog.attribute.delete.before', $id);
            $this->attribute_repository->delete($id);
            Event::dispatch('catalog.attribute.delete.after', $id);
            return new Json_Response(['message' => trans('admin::app.catalog.attributes.delete-success')]);
        } catch (\Exception $e) {
        }
        return new Json_Response(['message' => trans('admin::app.catalog.attributes.delete-failed')], 500);
    }
    /**
     * Remove the specified resources from database.
     */
    public function mass_destroy(Mass_Destroy_Request $mass_destroy_request): Json_Response
    {
        $indices = $mass_destroy_request->input('indices');
        foreach ($indices as $index) {
            $attribute = $this->attribute_repository->find($index);
            if (!$attribute->is_user_defined) {
                return response()->json(['message' => trans('admin::app.catalog.attributes.delete-failed')], 422);
            }
        }
        try {
            foreach ($indices as $index) {
                Event::dispatch('catalog.attribute.delete.before', $index);
                $this->attribute_repository->delete($index);
                Event::dispatch('catalog.attribute.delete.after', $index);
            }
            return new Json_Response(['message' => trans('admin::app.catalog.attributes.index.datagrid.mass-delete-success')]);
        } catch (\Exception $exception) {
            return new Json_Response(['message' => trans('admin::app.catalog.attributes.delete-failed')], 500);
        }
    }
    /**
     * Get super attributes of product.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function product_super_attributes(int $id)
    {
        $product = $this->product_repository->find_or_fail($id);
        $super_attributes = $this->product_repository->get_super_attributes($product);
        return response()->json(['data' => $super_attributes]);
    }
}