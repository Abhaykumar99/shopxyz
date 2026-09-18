<?php

namespace App\Enums;

/**
 * Money status of an order, independent of fulfilment (ADR-006).
 */
enum PaymentStatus: string
{
    case CodPending = 'cod_pending';
    case CodCollected = 'cod_collected';
    case AwaitingProof = 'awaiting_proof';
    case PendingVerification = 'pending_verification';
    case Verified = 'verified';
    case Rejected = 'rejected';
    case Refunded = 'refunded';

    public function label(): string
    {
        return match ($this) {
            self::CodPending => 'Pay on delivery',
            self::CodCollected => 'Paid in cash',
            self::AwaitingProof => 'Payment details needed',
            self::PendingVerification => 'Checking payment',
            self::Verified => 'Paid by UPI',
            self::Rejected => 'Payment not matched',
            self::Refunded => 'Refunded',
        };
    }

    public function tone(): string
    {
        return match ($this) {
            self::CodPending => 'neutral',
            self::AwaitingProof, self::PendingVerification => 'offer',
            self::CodCollected, self::Verified => 'success',
            self::Rejected => 'danger',
            self::Refunded => 'info',
        };
    }

    /**
     * The customer can (re)submit a UPI screenshot and UTR.
     */
    public function acceptsProof(): bool
    {
        return in_array($this, [self::AwaitingProof, self::Rejected], true);
    }
}
