<?php

namespace App\Enums;

/**
 * Fulfilment status of an order (ADR-006, docs/erd.md).
 */
enum OrderStatus: string
{
    case Placed = 'placed';
    case Confirmed = 'confirmed';
    case Packing = 'packing';
    case Packed = 'packed';
    case Assigned = 'assigned';
    case OutForDelivery = 'out_for_delivery';
    case Delivered = 'delivered';
    case DeliveryFailed = 'delivery_failed';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::Placed => 'Placed',
            self::Confirmed => 'Confirmed',
            self::Packing => 'Being packed',
            self::Packed => 'Packed',
            self::Assigned => 'Ready for delivery',
            self::OutForDelivery => 'Out for delivery',
            self::Delivered => 'Delivered',
            self::DeliveryFailed => 'Delivery failed',
            self::Cancelled => 'Cancelled',
        };
    }

    /**
     * Status pill tone: info = new, offer = waiting, brand = on the move, success = done, danger = stopped.
     */
    public function tone(): string
    {
        return match ($this) {
            self::Placed, self::Confirmed => 'info',
            self::Packing, self::Packed => 'offer',
            self::Assigned, self::OutForDelivery => 'brand',
            self::Delivered => 'success',
            self::DeliveryFailed, self::Cancelled => 'danger',
        };
    }

    /**
     * @return list<self>
     */
    public function allowedTransitions(): array
    {
        return match ($this) {
            self::Placed => [self::Confirmed, self::Cancelled],
            self::Confirmed => [self::Packing, self::Cancelled],
            self::Packing => [self::Packed, self::Cancelled],
            self::Packed => [self::Assigned, self::Cancelled],
            self::Assigned => [self::OutForDelivery, self::Packed, self::Cancelled],
            self::OutForDelivery => [self::Delivered, self::DeliveryFailed],
            self::DeliveryFailed => [self::Assigned, self::Cancelled],
            self::Delivered, self::Cancelled => [],
        };
    }

    public function canTransitionTo(self $next): bool
    {
        return in_array($next, $this->allowedTransitions(), true);
    }

    public function isFinal(): bool
    {
        return $this->allowedTransitions() === [];
    }

    /**
     * Customers may cancel until the order is packed (client question 7, default).
     */
    public function isCancellableByCustomer(): bool
    {
        return in_array($this, [self::Placed, self::Confirmed, self::Packing], true);
    }

    /**
     * The simplified journey shown to customers in the order tracker.
     *
     * @return list<self>
     */
    public static function customerJourney(): array
    {
        return [self::Placed, self::Confirmed, self::Packed, self::OutForDelivery, self::Delivered];
    }

    /**
     * The journey step this status belongs to.
     */
    public function journeyStep(): self
    {
        return match ($this) {
            self::Packing => self::Confirmed,
            self::Assigned => self::Packed,
            self::DeliveryFailed => self::OutForDelivery,
            default => $this,
        };
    }
}
