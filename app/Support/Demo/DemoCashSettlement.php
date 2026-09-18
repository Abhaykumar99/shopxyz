<?php

namespace App\Support\Demo;

use App\Enums\CashSettlementStatus;
use Carbon\CarbonImmutable;

/**
 * TEMPORARY (Phase 3): one handover of COD cash from a delivery boy to the shop
 * (ADR-022). Replaced in Phase 9 by a `cod_settlements` row.
 */
final readonly class DemoCashSettlement
{
    /**
     * @param  list<array{number: string, customer: string, paise: int}>  $collections
     */
    public function __construct(
        public string $reference,
        public CashSettlementStatus $status,
        public int $amountPaise,
        public array $collections,
        public string $handedOverAt,
        public ?string $verifiedAt = null,
        public ?string $verifiedBy = null,
        public ?string $note = null,
    ) {}

    public function orderCount(): int
    {
        return count($this->collections);
    }

    public function handedOverOn(): string
    {
        return $this->day(CarbonImmutable::parse($this->handedOverAt));
    }

    public function handedOverTime(): string
    {
        return CarbonImmutable::parse($this->handedOverAt)->format('g:i a');
    }

    public function verifiedOn(): ?string
    {
        return $this->verifiedAt === null ? null : $this->day(CarbonImmutable::parse($this->verifiedAt)).', '.CarbonImmutable::parse($this->verifiedAt)->format('g:i a');
    }

    public function isOpen(): bool
    {
        return $this->status->isOpen();
    }

    private function day(CarbonImmutable $moment): string
    {
        return match (true) {
            $moment->isToday() => 'Today',
            $moment->isYesterday() => 'Yesterday',
            default => $moment->format('D, j M'),
        };
    }
}
