<?php

declare (strict_types=1);
namespace Webkul\Category\Models;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\Has_Factory;
use Illuminate\Database\Eloquent\Relations\Belongs_To_Many;
use Illuminate\Support\Facades\Storage;
use Kalnoy\Nestedset\Node_Trait;
use Shetabit\Visitor\Traits\Visitable;
use Webkul\Attribute\Models\Attribute_Proxy;
use Webkul\Category\Contracts\Category as CategoryContract;
use Webkul\Category\Database\Factories\Category_Factory;
use Webkul\Core\Eloquent\Translatable_Model;
use Webkul\Product\Models\Product_Proxy;
class Category extends Translatable_Model implements Category_Contract
{
    use Has_Factory;
    use Node_Trait;
    use Visitable;
    /**
     * Translated attributes.
     *
     * @var array
     */
    public $translated_attributes = ['name', 'description', 'slug', 'meta_title', 'meta_description', 'meta_keywords'];
    /**
     * Fillable.
     *
     * @var array
     */
    protected $fillable = ['position', 'status', 'display_mode', 'parent_id', 'additional'];
    /**
     * Eager loading.
     *
     * @var array
     */
    protected $with = ['translations'];
    /**
     * Appends.
     *
     * @var array
     */
    protected $appends = ['logo_url', 'banner_url', 'url'];
    /**
     * The products that belong to the category.
     */
    public function products(): Belongs_To_Many
    {
        return $this->belongs_to_many(Product_Proxy::model_class(), 'product_categories');
    }
    /**
     * The filterable attributes that belong to the category.
     */
    public function filterable_attributes(): Belongs_To_Many
    {
        return $this->belongs_to_many(Attribute_Proxy::model_class(), 'category_filterable_attributes')->with(['options' => function ($query) {
            $query->order_by('sort_order');
        }, 'translations', 'options.translations']);
    }
    /**
     * Get url attribute.
     *
     * @return string
     */
    public function get_url_attribute()
    {
        if ($category_translation = $this->translate(core()->get_current_locale()->code)) {
            return url($category_translation->slug);
        }
        return url($this->translate(core()->get_default_locale_code_from_default_channel())?->slug);
    }
    /**
     * Get image url for the category image.
     *
     * @return string
     */
    public function get_logo_url_attribute()
    {
        if (!$this->logo_path) {
            return;
        }
        return Storage::url($this->logo_path);
    }
    /**
     * Get banner url attribute.
     *
     * @return string
     */
    public function get_banner_url_attribute()
    {
        if (!$this->banner_path) {
            return;
        }
        return Storage::url($this->banner_path);
    }
    /**
     * Use fallback for category.
     */
    protected function use_fallback(): bool
    {
        return true;
    }
    /**
     * Get fallback locale for category.
     */
    protected function get_fallback_locale(?string $locale = null): ?string
    {
        if ($fallback = core()->get_default_locale_code_from_default_channel()) {
            return $fallback;
        }
        return parent::get_fallback_locale();
    }
    /**
     * Create a new factory instance for the model.
     */
    protected static function new_factory(): Factory
    {
        return Category_Factory::new();
    }
}