<?php

declare (strict_types=1);
namespace Webkul\Admin\Http\Requests;

use Illuminate\Foundation\Http\Form_Request;
use Webkul\Core\Rules\Phone_Number;
use Webkul\Core\Rules\Post_Code;
use Webkul\Customer\Rules\Vat_Id_Rule;
class Address_Request extends Form_Request
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
        return ['company_name' => ['nullable'], 'first_name' => ['required'], 'last_name' => ['required'], 'address' => ['required', 'array', 'min:1'], 'country' => ['required'], 'state' => ['required'], 'city' => ['required'], 'postcode' => ['required', new Post_Code()], 'phone' => ['required', new Phone_Number()], 'vat_id' => [(new Vat_Id_Rule())->set_country($this->input('country'))], 'email' => ['required'], 'default_address' => ['sometimes', 'required', 'in:0,1']];
    }
    /**
     * Attributes.
     *
     * @return array
     */
    public function attributes()
    {
        return ['address.*' => 'address'];
    }
}