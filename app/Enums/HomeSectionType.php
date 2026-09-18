<?php

namespace App\Enums;

/**
 * The kinds of block the homepage is built from (ADR-024). Each one knows where
 * its contents come from, so the admin only has to choose what to show.
 */
enum HomeSectionType: string
{
    case ProductRail = 'product_rail';
    case CategoryGrid = 'category_grid';
    case Promise = 'promise';
    case Promo = 'promo';
    case HowItWorks = 'how_it_works';

    public function label(): string
    {
        return match ($this) {
            self::ProductRail => 'Row of products',
            self::CategoryGrid => 'Shop by category',
            self::Promise => 'Three promises',
            self::Promo => 'Promotion banner',
            self::HowItWorks => 'How ordering works',
        };
    }

    public function hint(): string
    {
        return match ($this) {
            self::ProductRail => 'A swipeable row: bestsellers, offers, new arrivals or a list you pick yourself.',
            self::CategoryGrid => 'The category tiles with their subcategories.',
            self::Promise => 'The three reassurances under the banner (delivery, payment, freshness).',
            self::Promo => 'Shows the promotion cards managed under Banners.',
            self::HowItWorks => 'The numbered steps explaining how an order reaches the customer.',
        };
    }

    /**
     * Rows of products need a source; the other blocks build themselves.
     */
    public function needsSource(): bool
    {
        return $this === self::ProductRail;
    }

    /**
     * @return array<string, string>
     */
    public static function options(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn (self $type): array => [$type->value => $type->label()])
            ->all();
    }
}
