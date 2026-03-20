<?php

declare (strict_types=1);
namespace Webkul\Admin\Http\Requests;

use Illuminate\Foundation\Http\Form_Request;
use Illuminate\Support\Str;
use Webkul\Admin\Validations\Product_Category_Unique_Slug;
use Webkul\Attribute\Enums\Attribute_Type_Enum;
use Webkul\Core\Rules\Decimal;
use Webkul\Core\Rules\Slug;
use Webkul\Product\Repositories\Product_Attribute_Value_Repository;
use Webkul\Product\Repositories\Product_Repository;
class Product_Form extends Form_Request
{
    /**
     * Rules.
     *
     * @var array
     */
    protected $rules;
    /**
     * Product instance.
     *
     * @var \Webkul\Product\Contracts\Product
     */
    protected $product;
    /**
     * Product editable attributes.
     *
     * @var \Illuminate\Database\Eloquent\Collection
     */
    protected $product_editable_attributes;
    /**
     * Max video upload size.
     *
     * @var int
     */
    protected $max_video_file_size;
    /**
     * Create a new form request instance.
     *
     * @return void
     */
    public function __construct(protected Product_Repository $product_repository, protected Product_Attribute_Value_Repository $product_attribute_value_repository)
    {
        $this->max_video_file_size = core()->get_config_data('catalog.products.attribute.file_attribute_upload_size') ?: '2048';
    }
    /**
     * Determine if the product is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        $this->product = $this->product_repository->find($this->id);
        $this->rules = array_merge($this->product->get_type_instance()->get_type_validation_rules(), ['sku' => ['required', 'unique:products,sku,' . $this->id, new Slug()], 'url_key' => ['required', new Product_Category_Unique_Slug('products', $this->id)], 'images.files.*' => ['nullable', 'mimes:bmp,jpeg,jpg,png,webp'], 'images.positions.*' => ['nullable', 'integer'], 'videos.files.*' => ['nullable', 'mimetypes:application/octet-stream,video/mp4,video/webm,video/quicktime', 'max:' . $this->max_video_file_size], 'videos.positions.*' => ['nullable', 'integer'], 'special_price_from' => ['nullable', 'date'], 'special_price_to' => ['nullable', 'date', 'after_or_equal:special_price_from'], 'special_price' => ['nullable', new Decimal(), 'lt:price'], 'visible_individually' => ['sometimes', 'required', 'in:0,1'], 'status' => ['sometimes', 'required', 'in:0,1'], 'guest_checkout' => ['sometimes', 'required', 'in:0,1'], 'new' => ['sometimes', 'required', 'in:0,1'], 'featured' => ['sometimes', 'required', 'in:0,1']]);
        if (request()->images) {
            foreach (request()->images['files'] as $key => $file) {
                if (Str::contains($key, 'image_')) {
                    $this->rules = array_merge($this->rules, ['images.files.' . $key => ['required', 'mimes:bmp,jpeg,jpg,png,webp']]);
                }
            }
        }
        $this->product_editable_attributes = $this->product->get_editable_attributes();
        foreach ($this->product_editable_attributes as $attribute) {
            if (in_array($attribute->code, ['sku', 'url_key']) || $attribute->type == Attribute_Type_Enum::BOOLEAN->value) {
                continue;
            }
            $validations = [];
            if (!isset($this->rules[$attribute->code])) {
                $validations[] = $attribute->is_required ? 'required' : 'nullable';
            } else {
                $validations = $this->rules[$attribute->code];
            }
            if ($attribute->type == Attribute_Type_Enum::TEXT->value && $attribute->validation) {
                if ($attribute->validation === 'decimal') {
                    $validations[] = new Decimal();
                } elseif ($attribute->validation === 'regex') {
                    $validations[] = 'regex:' . $attribute->regex;
                } else {
                    $validations[] = $attribute->validation;
                }
            }
            if ($attribute->type == Attribute_Type_Enum::PRICE->value) {
                $validations[] = new Decimal();
            }
            if ($attribute->is_unique) {
                array_push($validations, function ($field, $value, $fail) use ($attribute) {
                    if (!$this->product_attribute_value_repository->is_value_unique($this->id, $attribute->id, $attribute->column_name, request($attribute->code))) {
                        $fail(trans('admin::app.catalog.products.index.already-taken', ['name' => ':attribute']));
                    }
                });
            }
            $this->rules[$attribute->code] = $validations;
        }
        return $this->rules;
    }
    /**
     * Custom message for validation.
     *
     * @return array
     */
    public function messages()
    {
        return ['variants.*.sku.unique' => trans('admin::app.catalog.products.index.already-taken', ['name' => ':attribute']), 'videos.files.*' => trans('admin::app.catalog.products.edit.videos.error', ['max' => $this->max_video_file_size])];
    }
    /**
     * Attributes.
     *
     * @return array
     */
    public function attributes()
    {
        return ['images.files.*' => 'image', 'videos.files.*' => 'video', 'variants.*.sku' => 'sku'];
    }
    /**
     * Handle a passed validation attempt.
     *
     * @return void
     */
    protected function passed_validation()
    {
        $tiny_mce_fields = $this->product_editable_attributes->filter(fn($attribute) => $attribute->type === Attribute_Type_Enum::TEXTAREA->value && $attribute->enable_wysiwyg)->pluck('code')->to_array();
        foreach ($tiny_mce_fields as $field) {
            if ($this->has($field)) {
                $this->merge([$field => clean_content($this->get($field))]);
            }
        }
    }
}