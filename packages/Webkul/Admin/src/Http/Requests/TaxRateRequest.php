<?php

declare (strict_types=1);
namespace Webkul\Admin\Http\Requests;

use Illuminate\Foundation\Http\Form_Request;
class Tax_Rate_Request extends Form_Request
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
        $rules = ['is_zip' => 'sometimes', 'zip_code' => 'nullable', 'zip_from' => 'nullable|required_with:is_zip', 'zip_to' => 'nullable|required_with:is_zip,zip_from', 'country' => 'required|string', 'tax_rate' => 'required|numeric|min:0|max:100'];
        if ($this->id) {
            $rules['identifier'] = 'required|string|unique:tax_rates,identifier,' . $this->id;
        } else {
            $rules['identifier'] = 'required|string|unique:tax_rates,identifier';
        }
        return $rules;
    }
}