<?php

namespace App\Rules;

use App\Support\IndianPhone;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

final class IndianMobile implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_string($value) || ! IndianPhone::isValid($value)) {
            $fail('Enter a 10-digit mobile number starting with 6, 7, 8 or 9.');
        }
    }
}
