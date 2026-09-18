<?php

namespace App\Enums;

/**
 * Why a variant's stock changed. Rows are only ever added, never edited, so the
 * shop can always explain the number on the shelf.
 */
enum InventoryMovementType: string
{
    case Sale = 'sale';
    case CancelRestore = 'cancel_restore';
    case Restock = 'restock';
    case Adjustment = 'adjustment';
    case DeliveryFailed = 'delivery_failed';

    public function label(): string
    {
        return match ($this) {
            self::Sale => 'Sold',
            self::CancelRestore => 'Order cancelled',
            self::Restock => 'Restocked',
            self::Adjustment => 'Manual adjustment',
            self::DeliveryFailed => 'Delivery failed, came back',
        };
    }

    public function tone(): string
    {
        return match ($this) {
            self::Sale => 'brand',
            self::Restock => 'success',
            self::CancelRestore, self::DeliveryFailed => 'info',
            self::Adjustment => 'offer',
        };
    }

    /**
     * Movements the admin may record by hand.
     *
     * @return array<string, string>
     */
    public static function manualOptions(): array
    {
        return [
            self::Restock->value => self::Restock->label(),
            self::Adjustment->value => self::Adjustment->label(),
        ];
    }
}
