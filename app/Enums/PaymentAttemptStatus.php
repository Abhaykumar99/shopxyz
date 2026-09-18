<?php

namespace App\Enums;

/**
 * One payment attempt against an order. A UPI payment can be submitted, checked
 * against the shop's account and then verified or rejected; the customer may
 * submit fresh proof after a rejection (client question 9).
 */
enum PaymentAttemptStatus: string
{
    case Submitted = 'submitted';
    case Verified = 'verified';
    case Rejected = 'rejected';

    public function label(): string
    {
        return match ($this) {
            self::Submitted => 'Waiting to be checked',
            self::Verified => 'Verified',
            self::Rejected => 'Rejected',
        };
    }

    public function tone(): string
    {
        return match ($this) {
            self::Submitted => 'offer',
            self::Verified => 'success',
            self::Rejected => 'danger',
        };
    }

    public function isOpen(): bool
    {
        return $this === self::Submitted;
    }
}
