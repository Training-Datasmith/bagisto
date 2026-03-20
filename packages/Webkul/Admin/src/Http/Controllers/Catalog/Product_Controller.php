<?php

declare (strict_types=1);
namespace Webkul\Admin\Http\Controllers\Catalog;

use Illuminate\Http\Json_Response;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Storage;
use Webkul\Admin\Data_Grids\Catalog\Product_Data_Grid;
use Webkul\Admin\Http\Controllers\Controller;
use Webkul\Admin\Http\Requests\Inventory_Request;
use Webkul\Admin\Http\Requests\Mass_Destroy_Request;
use Webkul\Admin\Http\Requests\Mass_Update_Request;
use Webkul\Admin\Http\Requests\Product_Form;
use Webkul\Admin\Http\Resources\Attribute_Resource;
use Webkul\Admin\Http\Resources\Product_Resource;
use Webkul\Attribute\Repositories\Attribute_Family_Repository;
use Webkul\Core\Rules\Slug;
use Webkul\Customer\Repositories\Customer_Repository;
use Webkul\Product\Helpers\Product;
use Webkul\Product\Helpers\Product_Type;
use Webkul\Product\Repositories\Product_Attribute_Value_Repository;
use Webkul\Product\Repositories\Product_Downloadable_Link_Repository;
use Webkul\Product\Repositories\Product_Downloadable_Sample_Repository;
use Webkul\Product\Repositories\Product_Inventory_Repository;
use Webkul\Product\Repositories\Product_Repository;
class Product_Controller extends Controller
{
    /**
     * Using const variable for status.
     */
    public const ACTIVE_STATUS = 1;
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct(protected Attribute_Family_Repository $attribute_family_repository, protected Product_Attribute_Value_Repository $product_attribute_value_repository, protected Product_Downloadable_Link_Repository $product_downloadable_link_repository, protected Product_Downloadable_Sample_Repository $product_downloadable_sample_repository, protected Product_Inventory_Repository $product_inventory_repository, protected Product_Repository $product_repository, protected Customer_Repository $customer_repository)
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
            return datagrid(Product_Data_Grid::class)->process();
        }
        $families = $this->attribute_family_repository->all();
        return view('admin::catalog.products.index', compact('families'));
    }
    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\View\View
     */
    public function create()
    {
        $families = $this->attribute_family_repository->all();
        $configurable_family = null;
        if ($family_id = request()->get('family')) {
            $configurable_family = $this->attribute_family_repository->find($family_id);
        }
        return view('admin::catalog.products.create', compact('families', 'configurableFamily'));
    }
    /**
     * Store a newly created resource in storage.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function store()
    {
        $this->validate(request(), ['type' => 'required', 'attribute_family_id' => 'required', 'sku' => ['required', 'unique:products,sku', new Slug()], 'super_attributes' => 'array|min:1', 'super_attributes.*' => 'array|min:1']);
        if (Product_Type::has_variants(request()->input('type')) && !request()->has('super_attributes')) {
            $configurable_family = $this->attribute_family_repository->find(request()->input('attribute_family_id'));
            return new Json_Response(['data' => ['attributes' => Attribute_Resource::collection($configurable_family->configurable_attributes)]]);
        }
        Event::dispatch('catalog.product.create.before');
        $product = $this->product_repository->create(request()->only(['type', 'attribute_family_id', 'sku', 'super_attributes', 'family']));
        Event::dispatch('catalog.product.create.after', $product);
        session()->flash('success', trans('admin::app.catalog.products.create-success'));
        return new Json_Response(['data' => ['redirect_url' => route('admin.catalog.products.edit', $product->id)]]);
    }
    /**
     * Show the form for editing the specified resource.
     *
     * @return \Illuminate\View\View
     */
    public function edit(int $id)
    {
        $product = $this->product_repository->find_or_fail($id);
        return view('admin::catalog.products.edit', compact('product'));
    }
    /**
     * Update the specified resource in storage.
     *
     * @return \Illuminate\Http\Response
     */
    public function update(Product_Form $request, int $id)
    {
        Event::dispatch('catalog.product.update.before', $id);
        $product = $this->product_repository->update($request->all(), $id);
        Event::dispatch('catalog.product.update.after', $product);
        session()->flash('success', trans('admin::app.catalog.products.update-success'));
        return redirect()->route('admin.catalog.products.index');
    }
    /**
     * Update inventories.
     *
     * @return \Illuminate\Http\Response
     */
    public function update_inventories(Inventory_Request $inventory_request, int $id)
    {
        $product = $this->product_repository->find_or_fail($id);
        Event::dispatch('catalog.product.update.before', $id);
        $this->product_inventory_repository->save_inventories(request()->all(), $product);
        Event::dispatch('catalog.product.update.after', $product);
        return response()->json(['message' => trans('admin::app.catalog.products.saved-inventory-message'), 'updatedTotal' => $this->product_inventory_repository->where('product_id', $product->id)->sum('qty')]);
    }
    /**
     * Uploads downloadable file.
     *
     * @return \Illuminate\Http\Response
     */
    public function upload_link(int $id)
    {
        return response()->json($this->product_downloadable_link_repository->upload(request()->all(), $id));
    }
    /**
     * Copy a given Product.
     *
     * @return \Illuminate\Http\Response
     */
    public function copy(int $id)
    {
        try {
            Event::dispatch('catalog.product.create.before');
            $product = $this->product_repository->copy($id);
            Event::dispatch('catalog.product.create.after', $product);
        } catch (\Exception $e) {
            session()->flash('error', $e->get_message());
            return redirect()->to(route('admin.catalog.products.index'));
        }
        return response()->json(['message' => trans('admin::app.catalog.products.product-copied')]);
    }
    /**
     * Uploads downloadable sample file.
     *
     * @return \Illuminate\Http\Response
     */
    public function upload_sample(int $id)
    {
        return response()->json($this->product_downloadable_sample_repository->upload(request()->all(), $id));
    }
    /**
     * Remove the specified resource from storage.
     */
    public function destroy(int $id): Json_Response
    {
        try {
            Event::dispatch('catalog.product.delete.before', $id);
            $this->product_repository->delete($id);
            Event::dispatch('catalog.product.delete.after', $id);
            return new Json_Response(['message' => trans('admin::app.catalog.products.delete-success')]);
        } catch (\Exception $e) {
            report($e);
        }
        return new Json_Response(['message' => trans('admin::app.catalog.products.delete-failed')], 500);
    }
    /**
     * Mass delete the products.
     */
    public function mass_destroy(Mass_Destroy_Request $mass_destroy_request): Json_Response
    {
        $product_ids = $mass_destroy_request->input('indices');
        try {
            foreach ($product_ids as $product_id) {
                $product = $this->product_repository->find($product_id);
                if (isset($product)) {
                    Event::dispatch('catalog.product.delete.before', $product_id);
                    $this->product_repository->delete($product_id);
                    Event::dispatch('catalog.product.delete.after', $product_id);
                }
            }
            return new Json_Response(['message' => trans('admin::app.catalog.products.index.datagrid.mass-delete-success')]);
        } catch (\Exception $e) {
            return new Json_Response(['message' => $e->get_message()], 500);
        }
    }
    /**
     * Mass update the products.
     */
    public function mass_update(Mass_Update_Request $mass_update_request): Json_Response
    {
        $product_ids = $mass_update_request->input('indices');
        foreach ($product_ids as $product_id) {
            Event::dispatch('catalog.product.update.before', $product_id);
            $product = $this->product_repository->update(['status' => $mass_update_request->input('value')], $product_id, ['status']);
            Event::dispatch('catalog.product.update.after', $product);
        }
        return new Json_Response(['message' => trans('admin::app.catalog.products.index.datagrid.mass-update-success')], 200);
    }
    /**
     * To be manually invoked when data is seeded into products.
     *
     * @return \Illuminate\Http\Response
     */
    public function sync()
    {
        Event::dispatch('products.datagrid.sync', true);
        return redirect()->route('admin.catalog.products.index');
    }
    /**
     * Result of search product.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function search()
    {
        $query = trim(request('query'));
        if (empty($query)) {
            return response()->json(['data' => []]);
        }
        $search_engine = 'database';
        if (core()->get_config_data('catalog.products.search.engine') == 'elastic' && core()->get_config_data('catalog.products.search.admin_mode') == 'elastic') {
            $search_engine = 'elastic';
            $index_names = core()->get_all_channels()->map(function ($channel) {
                return Product::format_elastic_search_index_name($channel->code, app()->get_locale());
            })->to_array();
        }
        $channel_id = $this->customer_repository->find(request('customer_id'))->channel_id ?? null;
        $params = ['index' => $index_names ?? null, 'name' => request('query'), 'sort' => 'created_at', 'order' => 'desc', 'channel_id' => $channel_id];
        if (request()->has('type')) {
            $params['type'] = request('type');
        }
        if (request()->has('exclude_customizable_products')) {
            $params['exclude_customizable_products'] = request('exclude_customizable_products');
        }
        $products = $this->product_repository->set_search_engine($search_engine)->get_all($params);
        return Product_Resource::collection($products);
    }
    /**
     * Download image or file.
     *
     * @param  int  $productId
     * @param  int  $attributeId
     * @return \Illuminate\Http\Response
     */
    public function download($product_id, $attribute_id)
    {
        $product_attribute = $this->product_attribute_value_repository->find_one_where(['product_id' => $product_id, 'attribute_id' => $attribute_id]);
        return Storage::download($product_attribute['text_value']);
    }
}