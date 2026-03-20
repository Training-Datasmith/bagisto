<?php

declare (strict_types=1);
namespace Webkul\Category\Repositories;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Intervention\Image\Image_Manager;
use Webkul\Category\Contracts\Category;
use Webkul\Category\Models\Category_Translation_Proxy;
use Webkul\Core\Eloquent\Repository;
class Category_Repository extends Repository
{
    /**
     * Specify model class name.
     */
    public function model(): string
    {
        return Category::class;
    }
    /**
     * Get categories.
     *
     * @return void
     */
    public function get_all(array $params = [])
    {
        $query_builder = $this->query()->select('categories.*')->left_join('category_translations', 'category_translations.category_id', '=', 'categories.id');
        foreach ($params as $key => $value) {
            switch ($key) {
                case 'name':
                    $query_builder->where('category_translations.name', 'like', '%' . urldecode($value) . '%');
                    break;
                case 'description':
                    $query_builder->where('category_translations.description', 'like', '%' . urldecode($value) . '%');
                    break;
                case 'status':
                    $query_builder->where('categories.status', $value);
                    break;
                case 'only_children':
                    $query_builder->where_not_null('categories.parent_id');
                    break;
                case 'parent_id':
                    $parent_ids = array_filter(array_map('trim', explode(',', $value)));
                    $query_builder->where_in('categories.parent_id', $parent_ids);
                    break;
                case 'locale':
                    $query_builder->where('category_translations.locale', $value);
                    break;
            }
        }
        return $query_builder->paginate($params['limit'] ?? 10);
    }
    /**
     * Create category.
     *
     * @return \Webkul\Category\Contracts\Category
     */
    public function create(array $data)
    {
        if (isset($data['locale']) && $data['locale'] == 'all') {
            $model = app()->make($this->model());
            foreach (core()->get_all_locales() as $locale) {
                foreach ($model->translated_attributes as $attribute) {
                    if (isset($data[$attribute])) {
                        $data[$locale->code][$attribute] = $data[$attribute];
                        $data[$locale->code]['locale_id'] = $locale->id;
                    }
                }
            }
        }
        $category = $this->model->create($data);
        $this->upload_images($data, $category);
        $this->upload_images($data, $category, 'banner_path');
        if (isset($data['attributes'])) {
            $category->filterable_attributes()->sync($data['attributes']);
        }
        return $category;
    }
    /**
     * Update category.
     *
     * @param  int  $id
     * @param  string  $attribute
     * @return \Webkul\Category\Contracts\Category
     */
    public function update(array $data, $id)
    {
        $category = $this->find($id);
        $data = $this->set_same_attribute_value_to_all_locale($data, 'slug');
        $category->update($data);
        $this->upload_images($data, $category);
        $this->upload_images($data, $category, 'banner_path');
        if (isset($data['attributes'])) {
            $category->filterable_attributes()->sync($data['attributes']);
        }
        return $category;
    }
    /**
     * Specify category tree.
     *
     * @return \Webkul\Category\Contracts\Category
     */
    public function get_category_tree(?int $id = null)
    {
        return $id ? $this->model::order_by('position', 'ASC')->where('id', '!=', $id)->get()->to_tree() : $this->model::order_by('position', 'ASC')->get()->to_tree();
    }
    /**
     * Specify category tree.
     *
     * @return \Illuminate\Support\Collection
     */
    public function get_category_tree_without_descendant(?int $id = null)
    {
        return $id ? $this->model::order_by('position', 'ASC')->where('id', '!=', $id)->where_not_descendant_of($id)->get()->to_tree() : $this->model::order_by('position', 'ASC')->get()->to_tree();
    }
    /**
     * Get root categories.
     *
     * @return \Illuminate\Support\Collection
     */
    public function get_root_categories()
    {
        return $this->get_model()->where('parent_id', null)->get();
    }
    /**
     * Get child categories.
     *
     * @return \Illuminate\Support\Collection
     */
    public function get_child_categories($parent_id)
    {
        return $this->get_model()->where('parent_id', $parent_id)->get();
    }
    /**
     * get visible category tree.
     *
     * @param  int  $id
     * @return \Illuminate\Support\Collection
     */
    public function get_visible_category_tree($id = null)
    {
        return $id ? $this->model::order_by('position', 'ASC')->where('status', 1)->descendants_and_self($id)->to_tree($id) : $this->model::order_by('position', 'ASC')->where('status', 1)->get()->to_tree();
    }
    /**
     * Checks slug is unique or not based on locale.
     *
     * @param  int  $id
     * @param  string  $slug
     * @return bool
     */
    public function is_slug_unique($id, $slug)
    {
        $exists = Category_Translation_Proxy::model_class()::where('category_id', '<>', $id)->where('slug', $slug)->limit(1)->select(DB::raw(1))->exists();
        return !$exists;
    }
    /**
     * Retrieve category from slug.
     *
     * @param  string  $slug
     * @return \Webkul\Category\Contracts\Category
     */
    public function find_by_slug($slug)
    {
        if ($category = $this->model->where_translation('slug', $slug)->first()) {
            return $category;
        }
    }
    /**
     * Retrieve category from slug.
     *
     * @param  string  $slug
     * @return \Webkul\Category\Contracts\Category
     */
    public function find_by_slug_or_fail($slug)
    {
        return $this->model->where_translation('slug', $slug)->first_or_fail();
    }
    /**
     * Upload category's images.
     *
     * @param  array  $data
     * @param  \Webkul\Category\Contracts\Category  $category
     * @param  string  $type
     * @return void
     */
    public function upload_images($data, $category, $type = 'logo_path')
    {
        if (isset($data[$type])) {
            foreach ($data[$type] as $image_id => $image) {
                $file = $type . '.' . $image_id;
                if (request()->has_file($file)) {
                    if ($category->{$type}) {
                        Storage::delete($category->{$type});
                    }
                    $manager = new Image_Manager();
                    $image = $manager->make(request()->file($file))->encode('webp');
                    $category->{$type} = 'category/' . $category->id . '/' . Str::random(40) . '.webp';
                    Storage::put($category->{$type}, $image);
                    $category->save();
                }
            }
        } else {
            if ($category->{$type}) {
                Storage::delete($category->{$type});
            }
            $category->{$type} = null;
            $category->save();
        }
    }
    /**
     * Get partials.
     *
     * @param  array|null  $columns
     * @return array
     */
    public function get_partial($columns = null)
    {
        $categories = $this->model->all();
        $trimmed = [];
        foreach ($categories as $key => $category) {
            if (!empty($category->name)) {
                $trimmed[$key] = ['id' => $category->id, 'name' => $category->name, 'slug' => $category->slug];
            }
        }
        return $trimmed;
    }
    /**
     * Set same value to all locales in category.
     *
     * To Do: Move column from the `category_translations` to `category` table. And remove
     * this created method.
     *
     * @param  string  $attributeNames
     * @return array
     */
    private function set_same_attribute_value_to_all_locale(array $data, ...$attribute_names)
    {
        $requested_locale = core()->get_requested_locale_code();
        $model = app()->make($this->model());
        foreach ($attribute_names as $attribute_name) {
            foreach (core()->get_all_locales() as $locale) {
                if ($requested_locale == $locale->code) {
                    foreach ($model->translated_attributes as $attribute) {
                        if ($attribute === $attribute_name) {
                            $data[$locale->code][$attribute] = $data[$requested_locale][$attribute] ?? $data[$data['locale']][$attribute];
                        }
                    }
                }
            }
        }
        return $data;
    }
}