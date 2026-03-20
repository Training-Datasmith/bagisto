<?php

declare (strict_types=1);
namespace Webkul\Core\Rules;

use Closure;
use Illuminate\Contracts\Validation\Validation_Rule;
class Slug implements Validation_Rule
{
    /**
     * Run the validation rule.
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (!preg_match('/^[\p{L}\p{M}\p{N}]+(?:-[\p{L}\p{M}\p{N}]+)*$/u', $value)) {
            $fail('core::validation.slug')->translate();
        }
    }
}