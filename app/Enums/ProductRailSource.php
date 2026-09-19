<?php

namespace App\Enums;

/**
 * Where a row of products gets its products from (ADR-024).
 */
enum ProductRailSource: string
{
    case Bestsellers = 'bestsellers';
    case NewArrivals = 'new_arrivals';
    case Offers = 'offers';
    case Featured = 'featured';
    case Category = 'category';
    case Manual = 'manual';

    public function label(): string
    {
        return match ($this) {
            self::Bestsellers => 'Bestsellers',
            self::NewArrivals => 'New arrivals',
            self::Offers => 'Biggest discounts',
            self::Featured => 'Featured products',
            self::Category => 'Everything in one category',
            self::Manual => 'A list I choose',
        };
    }

    public function needsCategory(): bool
    {
        return $this === self::Category;
    }

    public function isManual(): bool
    {
        return $this === self::Manual;
    }

    /**
     * @return array<string, string>
     */
    public static function options(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn (self $source): array => [$source->value => $source->label()])
            ->all();
    }
}
