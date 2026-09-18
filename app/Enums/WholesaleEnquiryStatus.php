<?php

namespace App\Enums;

/**
 * Where a wholesale quote request has got to (ADR-019).
 */
enum WholesaleEnquiryStatus: string
{
    case New = 'new';
    case Quoted = 'quoted';
    case Won = 'won';
    case Lost = 'lost';

    public function label(): string
    {
        return match ($this) {
            self::New => 'New',
            self::Quoted => 'Quote sent',
            self::Won => 'Ordered',
            self::Lost => 'Not going ahead',
        };
    }

    public function tone(): string
    {
        return match ($this) {
            self::New => 'info',
            self::Quoted => 'offer',
            self::Won => 'success',
            self::Lost => 'danger',
        };
    }

    public function isOpen(): bool
    {
        return $this === self::New || $this === self::Quoted;
    }

    /**
     * @return array<string, string>
     */
    public static function options(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn (self $status): array => [$status->value => $status->label()])
            ->all();
    }
}
