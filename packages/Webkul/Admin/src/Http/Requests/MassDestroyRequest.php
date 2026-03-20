<?php

declare (strict_types=1);
namespace Webkul\Admin\Http\Requests;

use Illuminate\Foundation\Http\Form_Request;
class Mass_Destroy_Request extends Form_Request
{
    /**
     * Determine if the request is authorized or not.
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
        return ['indices' => ['required', 'array'], 'indices.*' => ['integer']];
    }
}