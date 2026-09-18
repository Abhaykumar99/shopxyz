<?php

namespace App\Support;

/**
 * Indian mobile numbers: stored as 10 digits, shown as "+91 98300 12345".
 */
final class IndianPhone
{
    /**
     * Strips spaces, dashes, "+91", "91" and a leading "0" from a typed number.
     */
    public static function normalize(?string $input): string
    {
        $digits = preg_replace('/\D+/', '', (string) $input) ?? '';

        if (strlen($digits) === 12 && str_starts_with($digits, '91')) {
            return substr($digits, 2);
        }

        if (strlen($digits) === 11 && str_starts_with($digits, '0')) {
            return substr($digits, 1);
        }

        return $digits;
    }

    public static function isValid(?string $input): bool
    {
        return (bool) preg_match('/^[6-9]\d{9}$/', self::normalize($input));
    }

    public static function format(?string $input): string
    {
        $digits = self::normalize($input);

        return strlen($digits) === 10 ? '+91 '.substr($digits, 0, 5).' '.substr($digits, 5) : (string) $input;
    }
}
