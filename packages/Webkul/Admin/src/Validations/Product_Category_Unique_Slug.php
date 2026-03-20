<?php

declare (strict_types=1);
namespace Webkul\Admin\Validations;

use Closure;
use Illuminate\Contracts\Validation\Validation_Rule;
use Illuminate\Support\Facades\DB;
use Webkul\Category\Models\Category_Translation_Proxy;
use Webkul\Product\Repositories\Product_Repository;
class Product_Category_Unique_Slug implements Validation_Rule
{
    /**
     * Reserved slugs.
     *
     * @var array
     */
    protected $reserved_slugs = ['categories'];
    /**
     * Constructor.
     *
     * @param  string  $tableName
     * @param  string  $id
     */
    public function __construct(protected $table_name = null, protected $id = null)
    {
    }
    /**
     * Run the validation rule.
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (in_array($value, $this->reserved_slugs)) {
            $fail('admin::app.validations.slug-reserved')->translate();
            return;
        }
        if (!$this->is_slug_unique($value)) {
            $fail('admin::app.validations.slug-being-used')->translate();
        }
    }
    /**
     * Checks slug is unique or not.
     *
     * @param  string  $slug
     * @return bool
     */
    protected function is_slug_unique($slug)
    {
        return !$this->is_slug_exists_in_categories($slug) && !$this->is_slug_exists_in_products($slug);
    }
    /**
     * Is slug is exists in categories.
     *
     * @param  string  $slug
     * @return bool
     */
    protected function is_slug_exists_in_categories($slug)
    {
        if ($this->table_name && $this->id && $this->table_name === 'category_translations') {
            return Category_Translation_Proxy::model_class()::where('category_id', '<>', $this->id)->where('slug', $slug)->limit(1)->select(DB::raw(1))->exists();
        }
        return Category_Translation_Proxy::model_class()::where('slug', $slug)->limit(1)->select(DB::raw(1))->exists();
    }
    /**
     * Is slug is exists in products.
     *
     * @param  string  $slug
     * @return bool
     */
    protected function is_slug_exists_in_products($slug)
    {
        if (core()->get_config_data('catalog.products.search.engine') == 'elastic') {
            $search_engine = core()->get_config_data('catalog.products.search.storefront_mode');
        }
        $product = app(Product_Repository::class)->set_search_engine($search_engine ?? 'database')->find_by_slug($slug);
        if ($product && $this->table_name && $this->id && $this->table_name === 'products' && $this->id == $product->id) {
            $product = null;
        }
        return (bool) $product;
    }
}