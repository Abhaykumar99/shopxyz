<?php

namespace App\Enums;

/**
 * Where a homepage banner appears (ADR-024). The desktop hero is a carousel of
 * slides; phones get their own single hero and promo cards, so the shop can say
 * something different on a small screen without cramming the desktop slide in.
 */
enum BannerPlacement: string
{
    case DesktopHero = 'desktop_hero';
    case MobileHero = 'mobile_hero';
    case Promo = 'promo';

    public function label(): string
    {
        return match ($this) {
            self::DesktopHero => 'Desktop hero slide',
            self::MobileHero => 'Phone hero',
            self::Promo => 'Promotion card',
        };
    }

    public function hint(): string
    {
        return match ($this) {
            self::DesktopHero => 'Slides in the big carousel, from 1024px up. Two to five slides work best.',
            self::MobileHero => 'The single banner phones and tablets see at the top of the homepage.',
            self::Promo => 'A card further down the homepage: festive pushes, wholesale, anything seasonal.',
        };
    }

    /**
     * @return array<string, string>
     */
    public static function options(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn (self $placement): array => [$placement->value => $placement->label()])
            ->all();
    }
}
