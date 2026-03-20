<?php

declare (strict_types=1);
namespace Webkul\Admin\Http\Requests;

use Illuminate\Foundation\Http\Form_Request;
use Webkul\Core\Rules\Decimal;
use Webkul\Core\Rules\Phone_Number;
use Webkul\Core\Rules\Post_Code;
class Configuration_Form extends Form_Request
{
    /**
     * Determine if the Configuration is authorized to make this request.
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
        return collect(request()->input('keys', []))->map_with_keys(function ($item) {
            $data = json_decode($item, true);
            return collect($data['fields'])->map_with_keys(function ($field) use ($data) {
                $key = "{$data['key']}.{$field['name']}";
                // Check delete key exist in the request
                if (!$this->has("{$key}.delete")) {
                    return [$key => $this->get_validation_rules($field['validation'] ?? 'nullable')];
                }
                return [];
            })->to_array();
        })->to_array();
    }
    /**
     * Transform validation rules into an array and map custom validation rules
     *
     * @param  string|array  $validation
     * @return array
     */
    protected function get_validation_rules($validation)
    {
        $validations = is_array($validation) ? $validation : explode('|', $validation);
        return array_map(function ($rule) {
            return match ($rule) {
                'phone' => new Phone_Number(),
                'postcode' => new Post_Code(),
                'decimal' => new Decimal(),
                default => $rule,
            };
        }, $validations);
    }
}