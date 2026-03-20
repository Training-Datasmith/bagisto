<?php

declare (strict_types=1);
namespace Webkul\Core\Rules;

use Closure;
use Illuminate\Contracts\Validation\Validation_Rule;
class Comma_Separated_Integer implements Validation_Rule
{
    /**
     * Run the validation rule.
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (!$this->is_comma_separated_integer($attribute, $value)) {
            $fail('core::validation.comma-separated-integer')->translate();
        }
    }
    /**
     * Determine if the value is comma separated integer.
     *
     * @param  string  $attribute
     * @param  mixed  $value
     * @return bool
     */
    public function is_comma_separated_integer($attribute, $value)
    {
        $integer_values = explode(',', $value);
        foreach ($integer_values as $integer_value) {
            if (!preg_match('/^[0-9]+$/', $integer_value)) {
                return false;
            }
        }
        return true;
    }
}