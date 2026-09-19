<?php

namespace App\Enums;

/**
 * Who a user is (ADR-004). Customers sign in with Google and have no password;
 * staff accounts are created by the admin and never use Google.
 */
enum UserRole: string
{
    case Customer = 'customer';
    case Admin = 'admin';
    case Delivery = 'delivery';

    public function label(): string
    {
        return match ($this) {
            self::Customer => 'Customer',
            self::Admin => 'Admin',
            self::Delivery => 'Delivery partner',
        };
    }

    public function tone(): string
    {
        return match ($this) {
            self::Customer => 'info',
            self::Admin => 'brand',
            self::Delivery => 'offer',
        };
    }

    /**
     * Staff sign in with a password and can reach an admin or delivery panel.
     */
    public function isStaff(): bool
    {
        return $this !== self::Customer;
    }

    /**
     * @return array<string, string>
     */
    public static function options(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn (self $role): array => [$role->value => $role->label()])
            ->all();
    }
}
