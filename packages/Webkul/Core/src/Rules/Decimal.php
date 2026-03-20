<?php

declare (strict_types=1);
namespace Webkul\Core\Rules;

use Closure;
use Illuminate\Contracts\Validation\Validation_Rule;
class Decimal implements Validation_Rule
{
    /**
     * Run the validation rule.
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (!preg_match('/^\d*(\.\d{1,4})?$/', $value)) {
            $fail('core::validation.decimal')->translate();
        }
    }
}