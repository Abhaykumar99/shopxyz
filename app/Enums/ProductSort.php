<?php

namespace App\Enums;

/**
 * How a customer can order a list of products. `Popular` is the default and,
 * unlike the others, is a real measurement: how much of each product the shop
 * has actually sold recently (ADR-027).
 */
enum ProductSort: string
{
    case Popular = 'popular';
    case Newest = 'newest';
    case PriceAsc = 'price_asc';
    case PriceDesc = 'price_desc';
    case Discount = 'discount';

    public function label(): string
    {
        return match ($this) {
            self::Popular => 'Most popular',
            self::Newest => 'Newest',
            self::PriceAsc => 'Price: low to high',
            self::PriceDesc => 'Price: high to low',
            self::Discount => 'Biggest discount',
        };
    }

    /**
     * An unknown sort from the query string falls back to the default rather
     * than erroring, because it arrives from a URL anyone can edit.
     */
    public static function fromRequest(?string $value): self
    {
        return self::tryFrom((string) $value) ?? self::Popular;
    }

    /**
     * @return array<string, string>
     */
    public static function options(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn (self $sort): array => [$sort->value => $sort->label()])
            ->all();
    }
}
