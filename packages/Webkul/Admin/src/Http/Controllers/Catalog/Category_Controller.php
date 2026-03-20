<?php

declare (strict_types=1);
namespace Webkul\Admin\Http\Controllers\Catalog;

use Illuminate\Http\Json_Response;
use Illuminate\Http\Resources\Json\Json_Resource;
use Illuminate\Support\Facades\Event;
use Webkul\Admin\Data_Grids\Catalog\Category_Data_Grid;
use Webkul\Admin\Http\Controllers\Controller;
use Webkul\Admin\Http\Requests\Category_Request;
use Webkul\Admin\Http\Requests\Mass_Destroy_Request;
use Webkul\Admin\Http\Requests\Mass_Update_Request;
use Webkul\Admin\Http\Resources\Category_Tree_Resource;
use Webkul\Attribute\Repositories\Attribute_Repository;
use Webkul\Category\Repositories\Category_Repository;
use Webkul\Core\Repositories\Channel_Repository;
class Category_Controller extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct(protected Channel_Repository $channel_repository, protected Category_Repository $category_repository, protected Attribute_Repository $attribute_repository)
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
            return datagrid(Category_Data_Grid::class)->process();
        }
        return view('admin::catalog.categories.index');
    }
    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\View\View
     */
    public function create()
    {
        $categories = $this->category_repository->get_category_tree();
        $attributes = $this->attribute_repository->find_where(['is_filterable' => 1]);
        return view('admin::catalog.categories.create', compact('categories', 'attributes'));
    }
    /**
     * Store a newly created resource in storage.
     *
     * @return \Illuminate\Http\Response
     */
    public function store(Category_Request $category_request)
    {
        Event::dispatch('catalog.category.create.before');
        $data = $category_request->only(['locale', 'name', 'parent_id', 'description', 'slug', 'meta_title', 'meta_keywords', 'meta_description', 'status', 'position', 'display_mode', 'attributes', 'logo_path', 'banner_path']);
        if (!empty($data['description'])) {
            $data['description'] = clean_content($data['description']);
        }
        $category = $this->category_repository->create($data);
        Event::dispatch('catalog.category.create.after', $category);
        session()->flash('success', trans('admin::app.catalog.categories.create-success'));
        return redirect()->route('admin.catalog.categories.index');
    }
    /**
     * Show the form for editing the specified resource.
     *
     * @return \Illuminate\View\View
     */
    public function edit(int $id)
    {
        $category = $this->category_repository->find_or_fail($id);
        $categories = $this->category_repository->get_category_tree_without_descendant($id);
        $attributes = $this->attribute_repository->find_where(['is_filterable' => 1]);
        return view('admin::catalog.categories.edit', compact('category', 'categories', 'attributes'));
    }
    /**
     * Update the specified resource in storage.
     *
     * @return \Illuminate\Http\Response
     */
    public function update(Category_Request $category_request, int $id)
    {
        Event::dispatch('catalog.category.update.before', $id);
        $locale = $category_request->input('locale');
        $locale_data = $category_request->input($locale);
        if (!empty($locale_data['description'])) {
            $locale_data['description'] = clean_content($locale_data['description']);
        }
        $data = $category_request->only('locale', 'parent_id', 'logo_path', 'banner_path', 'position', 'display_mode', 'status', 'attributes');
        $data[$locale] = $locale_data;
        $category = $this->category_repository->update($data, $id);
        Event::dispatch('catalog.category.update.after', $category);
        session()->flash('success', trans('admin::app.catalog.categories.update-success'));
        return redirect()->route('admin.catalog.categories.index');
    }
    /**
     * Remove the specified resource from storage.
     */
    public function destroy(int $id): Json_Response
    {
        $category = $this->category_repository->find_or_fail($id);
        if (!$this->is_category_deletable($category)) {
            return new Json_Response(['message' => trans('admin::app.catalog.categories.delete-category-root')], 400);
        }
        try {
            Event::dispatch('catalog.category.delete.before', $id);
            $category->delete($id);
            Event::dispatch('catalog.category.delete.after', $id);
            return new Json_Response(['message' => trans('admin::app.catalog.categories.delete-success')]);
        } catch (\Exception $e) {
            return new Json_Response(['message' => trans('admin::app.catalog.categories.delete-failed')], 500);
        }
    }
    /**
     * Remove the specified resources from database.
     */
    public function mass_destroy(Mass_Destroy_Request $mass_destroy_request): Json_Response
    {
        $suppress_flash = true;
        $category_ids = $mass_destroy_request->input('indices');
        foreach ($category_ids as $category_id) {
            $category = $this->category_repository->find($category_id);
            if (isset($category)) {
                if (!$this->is_category_deletable($category)) {
                    $suppress_flash = false;
                    return new Json_Response(['message' => trans('admin::app.catalog.categories.delete-category-root')], 400);
                } else {
                    try {
                        $suppress_flash = true;
                        Event::dispatch('catalog.category.delete.before', $category_id);
                        $this->category_repository->delete($category_id);
                        Event::dispatch('catalog.category.delete.after', $category_id);
                    } catch (\Exception $e) {
                        return new Json_Response(['message' => trans('admin::app.catalog.categories.delete-failed')], 500);
                    }
                }
            }
        }
        if (count($category_ids) != 1 || $suppress_flash == true) {
            return new Json_Response(['message' => trans('admin::app.catalog.categories.delete-success')]);
        }
        return redirect()->route('admin.catalog.categories.index');
    }
    /**
     * Mass update Category.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function mass_update(Mass_Update_Request $mass_update_request)
    {
        try {
            $category_ids = $mass_update_request->input('indices');
            foreach ($category_ids as $category_id) {
                Event::dispatch('catalog.categories.mass-update.before', $category_id);
                $category = $this->category_repository->find($category_id);
                $category->status = $mass_update_request->input('value');
                $category->save();
                Event::dispatch('catalog.categories.mass-update.after', $category);
            }
            return new Json_Response(['message' => trans('admin::app.catalog.categories.update-success')]);
        } catch (\Exception $e) {
            return new Json_Response(['message' => $e->get_message()], 500);
        }
    }
    /**
     * Check whether the current category is deletable or not.
     *
     * This method will fetch all root category ids from the channel. If `id` is present,
     * then it is not deletable.
     *
     * @param  \Webkul\Category\Contracts\Category  $category
     * @return bool
     */
    private function is_category_deletable($category)
    {
        if ($category->id === 1) {
            return false;
        }
        return !$this->channel_repository->pluck('root_category_id')->contains($category->id);
    }
    /**
     * Get all categories in tree format.
     */
    public function tree(): Json_Resource
    {
        $categories = $this->category_repository->get_visible_category_tree(core()->get_requested_channel()->root_category_id);
        return Category_Tree_Resource::collection($categories);
    }
    /**
     * Get all the searched categories.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function search()
    {
        $categories = $this->category_repository->get_all(['name' => request()->input('query'), 'locale' => app()->get_locale()]);
        return response()->json($categories);
    }
}