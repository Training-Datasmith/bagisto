<?php

declare (strict_types=1);
namespace Webkul\Admin\Http\Requests;

use Illuminate\Foundation\Http\Form_Request;
class Catalog_Rule_Request extends Form_Request
{
    /**
     * Determine if the user is authorized to make this request.
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
        $rules = ['name' => 'required', 'channels' => 'required|array|min:1', 'customer_groups' => 'required|array|min:1', 'starts_from' => 'nullable|date', 'ends_till' => 'nullable|date|after_or_equal:starts_from', 'action_type' => 'required', 'discount_amount' => 'required|numeric'];
        if (request()->has('action_type') && request()->action_type == 'by_percent') {
            $rules = array_merge($rules, ['discount_amount' => 'required|numeric|min:0|max:100']);
        }
        return $rules;
    }
}