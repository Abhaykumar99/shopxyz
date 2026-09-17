<?php

namespace App\Support;

use Illuminate\Support\Number;

/**
 * Formatting for amounts stored as integer paise (ADR-005).
 */
final class Money
{
    /**
     * Format paise as Indian rupees: 12500050 → "₹1,25,000.50", 19900 → "₹199".
     */
    public static function format(int $paise): string
    {
        $precision = $paise % 100 === 0 ? 0 : 2;

        return (string) Number::currency($paise / 100, 'INR', 'en_IN', $precision);
    }

    /**
     * Whole-number percentage saved between MRP and selling price.
     */
    public static function discountPercent(int $mrpPaise, int $pricePaise): int
    {
        if ($mrpPaise <= 0 || $pricePaise >= $mrpPaise) {
            return 0;
        }

        return (int) floor(($mrpPaise - $pricePaise) * 100 / $mrpPaise);
    }
}
