<?php

namespace App\Enums;

enum PaymentMethod: string
{
    case Cod = 'cod';
    case Upi = 'upi';

    public function label(): string
    {
        return match ($this) {
            self::Cod => 'Cash on delivery',
            self::Upi => 'UPI',
        };
    }

    public function initialPaymentStatus(): PaymentStatus
    {
        return match ($this) {
            self::Cod => PaymentStatus::CodPending,
            self::Upi => PaymentStatus::AwaitingProof,
        };
    }
}
