<?php

namespace App\Rules;

use App\Support\ShopSettings;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * A valid Indian pincode inside the shop's delivery area.
 */
final class ServedPincode implements ValidationRule
{
    public function __construct(private readonly ShopSettings $shop) {}

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_string($value) || ! preg_match('/^[1-9]\d{5}$/', $value)) {
            $fail('Enter a 6-digit pincode.');

            return;
        }

        if (! $this->shop->servesPincode($value)) {
            $fail($this->shop->deliveryArea
                ? "We don't deliver to {$value} yet. We currently deliver within {$this->shop->deliveryArea}."
                : "We don't deliver to {$value} yet.");
        }
    }
}
