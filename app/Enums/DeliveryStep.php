<?php

namespace App\Enums;

/**
 * Where a delivery boy has got to with one assigned order (ADR-020, ADR-022).
 *
 * The panel groups the round by these steps: **to pick up** (assigned and
 * accepted), **picked up** (every box verified at the counter), **out for
 * delivery** (on the way), **delivered** and **failed**.
 *
 * The customer only ever sees the matching `OrderStatus`, so their timeline
 * stays short: the order goes out for delivery when the boy leaves the shop with
 * every box, and ends when it is delivered or fails.
 */
enum DeliveryStep: string
{
    case Assigned = 'assigned';
    case Accepted = 'accepted';
    case PickedUp = 'picked_up';
    case OutForDelivery = 'out_for_delivery';
    case Delivered = 'delivered';
    case Failed = 'failed';

    public function label(): string
    {
        return match ($this) {
            self::Assigned => 'New',
            self::Accepted => 'To pick up',
            self::PickedUp => 'Picked up',
            self::OutForDelivery => 'Out for delivery',
            self::Delivered => 'Delivered',
            self::Failed => 'Could not deliver',
        };
    }

    /**
     * Heading for the group this step belongs to on the round screen.
     */
    public function group(): string
    {
        return match ($this) {
            self::Assigned, self::Accepted => 'To pick up',
            self::PickedUp => 'Picked up',
            self::OutForDelivery => 'Out for delivery',
            self::Delivered => 'Delivered',
            self::Failed => 'Failed delivery',
        };
    }

    /**
     * Status pill tone, matching `OrderStatus::tone()`.
     */
    public function tone(): string
    {
        return match ($this) {
            self::Assigned => 'info',
            self::Accepted => 'offer',
            self::PickedUp, self::OutForDelivery => 'brand',
            self::Delivered => 'success',
            self::Failed => 'danger',
        };
    }

    /**
     * The button that moves this delivery on, or null when nothing is left to do.
     */
    public function nextAction(): ?string
    {
        return match ($this) {
            self::Assigned => 'Accept this delivery',
            self::Accepted => 'Enter the pickup codes',
            self::PickedUp => 'Start delivery',
            self::OutForDelivery => 'Confirm delivery',
            self::Delivered, self::Failed => null,
        };
    }

    /**
     * Steps a delivery boy can reach with a single tap. Picking up needs the
     * pickup code on every box, and delivering needs the customer's OTP (ADR-021).
     */
    public function isReachedByTapping(): bool
    {
        return $this === self::Accepted || $this === self::OutForDelivery;
    }

    public function next(): ?self
    {
        return match ($this) {
            self::Assigned => self::Accepted,
            self::Accepted => self::PickedUp,
            self::PickedUp => self::OutForDelivery,
            self::OutForDelivery => self::Delivered,
            self::Delivered, self::Failed => null,
        };
    }

    /**
     * What the delivery boy should do now, shown under the step.
     */
    public function hint(): string
    {
        return match ($this) {
            self::Assigned => 'Accept it so the shop knows it is with you.',
            self::Accepted => 'At the counter, enter the pickup code printed on each box label.',
            self::PickedUp => 'Every box is with you. Start the delivery when you leave the shop.',
            self::OutForDelivery => 'At the address, ask the customer for their 6-digit OTP, collect the cash if it is a COD order, then confirm.',
            self::Delivered => 'Done. Hand the cash over at the shop.',
            self::Failed => 'Take the boxes back to the shop. They will call the customer and reassign the order.',
        };
    }

    /**
     * The order status this step moves the order to, if any.
     */
    public function orderStatus(): ?OrderStatus
    {
        return match ($this) {
            self::OutForDelivery => OrderStatus::OutForDelivery,
            self::Delivered => OrderStatus::Delivered,
            self::Failed => OrderStatus::DeliveryFailed,
            self::Assigned, self::Accepted, self::PickedUp => null,
        };
    }

    public function isFinished(): bool
    {
        return $this === self::Delivered || $this === self::Failed;
    }

    /**
     * The order is still at the shop waiting to be collected.
     */
    public function isBeforePickup(): bool
    {
        return $this === self::Assigned || $this === self::Accepted;
    }

    /**
     * Steps shown in the delivery panel's own progress list.
     *
     * @return list<self>
     */
    public static function journey(): array
    {
        return [self::Assigned, self::Accepted, self::PickedUp, self::OutForDelivery, self::Delivered];
    }

    /**
     * The groups the round screen is split into, in order.
     *
     * @return list<self>
     */
    public static function groups(): array
    {
        return [self::Accepted, self::PickedUp, self::OutForDelivery, self::Delivered, self::Failed];
    }

    public function position(): int
    {
        $index = array_search($this, self::journey(), true);

        return is_int($index) ? $index : count(self::journey()) - 1;
    }
}
