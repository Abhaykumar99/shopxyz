<?php

namespace App\Enums;

/**
 * Where a batch of COD cash has got to on its way from the doorstep to the till
 * (ADR-022): collected → handed over at the shop → checked by the admin → settled.
 */
enum CashSettlementStatus: string
{
    case AwaitingVerification = 'awaiting_verification';
    case Settled = 'settled';
    case Short = 'short';

    public function label(): string
    {
        return match ($this) {
            self::AwaitingVerification => 'With the shop, being checked',
            self::Settled => 'Settled',
            self::Short => 'Short, being sorted out',
        };
    }

    public function tone(): string
    {
        return match ($this) {
            self::AwaitingVerification => 'offer',
            self::Settled => 'success',
            self::Short => 'danger',
        };
    }

    public function isOpen(): bool
    {
        return $this !== self::Settled;
    }
}
