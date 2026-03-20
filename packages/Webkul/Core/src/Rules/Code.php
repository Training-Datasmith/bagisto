<?php

declare (strict_types=1);
namespace Webkul\Core\Rules;

use Closure;
use Illuminate\Contracts\Validation\Validation_Rule;
class Code implements Validation_Rule
{
    /**
     * Run the validation rule.
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (!preg_match('/^[a-zA-Z]+[a-zA-Z0-9_]+$/', $value)) {
            $fail('core::validation.code')->translate();
        }
    }
}