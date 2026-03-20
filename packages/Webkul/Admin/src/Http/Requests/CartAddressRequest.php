<?php

declare (strict_types=1);
namespace Webkul\Admin\Http\Requests;

use Illuminate\Foundation\Http\Form_Request;
use Webkul\Core\Rules\Phone_Number;
use Webkul\Core\Rules\Post_Code;
class Cart_Address_Request extends Form_Request
{
    /**
     * Rules.
     *
     * @var array
     */
    protected $rules = [];
    /**
     * Determine if the product is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }
    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        if ($this->has('billing')) {
            $this->merge_address_rules('billing');
        }
        if (!$this->input('billing.use_for_shipping')) {
            $this->merge_address_rules('shipping');
        }
        return $this->rules;
    }
    /**
     * Merge new address rules.
     *
     * @return void
     */
    private function merge_address_rules(string $address_type)
    {
        $this->merge_with_rules(["{$address_type}.company_name" => ['nullable'], "{$address_type}.first_name" => ['required'], "{$address_type}.last_name" => ['required'], "{$address_type}.email" => ['required'], "{$address_type}.address" => ['required', 'array', 'min:1'], "{$address_type}.city" => ['required'], "{$address_type}.country" => ['required'], "{$address_type}.state" => ['required'], "{$address_type}.postcode" => ['required', new Post_Code()], "{$address_type}.phone" => ['required', new Phone_Number()]]);
    }
    /**
     * Merge additional rules.
     */
    private function merge_with_rules($rules): void
    {
        $this->rules = array_merge($this->rules, $rules);
    }
}