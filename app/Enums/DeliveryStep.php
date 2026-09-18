<?php

namespace App\Enums;

/**
 * Where a delivery boy has got to with one assigned order (ADR-020).
 *
 * These steps belong to the delivery panel. The customer only ever sees the
 * matching `OrderStatus`, so the timeline they follow stays short: the order
 * goes out for delivery when the parcel is picked up, and is delivered (or
 * failed) at the end. `accepted` and `reached` leave the order status alone.
 */
enum DeliveryStep: string
{
    case Assigned = 'assigned';
    case Accepted = 'accepted';
    case PickedUp = 'picked_up';
    case Reached = 'reached';
    case Delivered = 'delivered';
    case Failed = 'failed';

    public function label(): string
    {
        return match ($this) {
            self::Assigned => 'New',
            self::Accepted => 'To collect',
            self::PickedUp => 'On the way',
            self::Reached => 'At the address',
            self::Delivered => 'Delivered',
            self::Failed => 'Could not deliver',
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
            self::PickedUp, self::Reached => 'brand',
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
            self::PickedUp => 'I have reached the address',
            self::Reached => 'Confirm delivery',
            self::Delivered, self::Failed => null,
        };
    }

    /**
     * Steps a delivery boy can reach with a single tap. Picking up needs the
     * pickup code on every box, and delivering needs the customer's OTP (ADR-021).
     */
    public function isReachedByTapping(): bool
    {
        return $this === self::Accepted || $this === self::Reached;
    }

    public function next(): ?self
    {
        return match ($this) {
            self::Assigned => self::Accepted,
            self::Accepted => self::PickedUp,
            self::PickedUp => self::Reached,
            self::Reached => self::Delivered,
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
            self::PickedUp => 'Ride to the address. Call the customer if you cannot find it.',
            self::Reached => 'Ask the customer for their 6-digit OTP, collect the cash if it is a COD order, then confirm.',
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
            self::PickedUp => OrderStatus::OutForDelivery,
            self::Delivered => OrderStatus::Delivered,
            self::Failed => OrderStatus::DeliveryFailed,
            self::Assigned, self::Accepted, self::Reached => null,
        };
    }

    public function isFinished(): bool
    {
        return $this === self::Delivered || $this === self::Failed;
    }

    /**
     * Steps shown in the delivery panel's own progress list.
     *
     * @return list<self>
     */
    public static function journey(): array
    {
        return [self::Assigned, self::Accepted, self::PickedUp, self::Reached, self::Delivered];
    }

    public function position(): int
    {
        $index = array_search($this, self::journey(), true);

        return is_int($index) ? $index : count(self::journey()) - 1;
    }
}
