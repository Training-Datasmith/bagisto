<?php

declare (strict_types=1);
namespace Webkul\Admin\Http\Requests;

use Illuminate\Foundation\Http\Form_Request;
class Inventory_Request extends Form_Request
{
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
        return ['inventories' => 'required|array', 'inventories.*' => 'required|numeric|min:0'];
    }
    /**
     * Custom message for validation.
     *
     * @return array
     */
    public function messages()
    {
        return ['inventories.*.required' => trans('admin::app.catalog.products.validations.quantity-required'), 'inventories.*.integer' => trans('admin::app.catalog.products.validations.quantity-integer'), 'inventories.*.min' => trans('admin::app.catalog.products.validations.quantity-min-zero')];
    }
}