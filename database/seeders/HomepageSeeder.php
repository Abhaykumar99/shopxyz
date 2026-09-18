<?php

namespace Database\Seeders;

use App\Enums\BannerPlacement;
use App\Enums\HomeSectionType;
use App\Enums\ProductRailSource;
use App\Models\Banner;
use App\Models\HomeSection;
use Illuminate\Database\Seeder;

/**
 * The homepage the shop starts with (ADR-024): the same banners and blocks the
 * prototype showed, now as rows the admin can edit, reorder, schedule or
 * switch off.
 */
class HomepageSeeder extends Seeder
{
    public function run(): void
    {
        $this->banners();
        $this->sections();
    }

    private function banners(): void
    {
        $slides = [
            [
                'eyebrow' => 'Fresh today',
                'title' => 'Sweets, beauty and gifts, delivered today.',
                'subtitle' => 'Mithai made this morning, beauty favourites and ready-to-give hampers, at your door in hours.',
                'cta_label' => 'Start shopping',
                'cta_url' => '/categories',
                'secondary_cta_label' => 'Today\'s offers',
                'secondary_cta_url' => '/search?sort=discount',
                'theme' => 'brand',
            ],
            [
                'eyebrow' => 'Festive',
                'title' => 'Hampers worth unwrapping.',
                'subtitle' => 'Dry fruit boxes, brass diyas and candles, packed and ribboned by hand.',
                'cta_label' => 'See gift hampers',
                'cta_url' => '/c/gifts',
                'theme' => 'accent',
            ],
            [
                'eyebrow' => 'Beauty',
                'title' => 'Up to 30% off beauty.',
                'subtitle' => 'Lipsticks, kajal and skincare from the brands our regulars keep coming back for.',
                'cta_label' => 'Shop cosmetics',
                'cta_url' => '/c/cosmetics',
                'theme' => 'ink',
            ],
            [
                'eyebrow' => 'Wholesale',
                'title' => 'Buying in bulk? Order at trade prices.',
                'subtitle' => 'Price slabs for shops, weddings, hotels and corporate gifting, on the same bag and checkout.',
                'cta_label' => 'See wholesale prices',
                'cta_url' => '/wholesale',
                'secondary_cta_label' => 'Request a quote',
                'secondary_cta_url' => '/wholesale/quote',
                'theme' => 'ink',
            ],
        ];

        foreach ($slides as $index => $slide) {
            Banner::create([
                ...$slide,
                'placement' => BannerPlacement::DesktopHero,
                'sort_order' => ($index + 1) * 10,
                'is_active' => true,
            ]);
        }

        Banner::create([
            'placement' => BannerPlacement::MobileHero,
            'eyebrow' => 'Fresh today',
            'title' => 'Sweets, beauty and gifts, delivered today.',
            'subtitle' => 'Order before 5 pm for same-day delivery across the city.',
            'cta_label' => 'Start shopping',
            'cta_url' => '/categories',
            'secondary_cta_label' => 'Today\'s offers',
            'secondary_cta_url' => '/search?sort=discount',
            'theme' => 'brand',
            'sort_order' => 10,
        ]);

        Banner::create([
            'placement' => BannerPlacement::Promo,
            'eyebrow' => 'Wholesale',
            'title' => 'Buying in bulk? Order at trade prices.',
            'body' => 'Price slabs for shops, weddings, hotels and corporate gifting, ordered through the same bag and checkout.',
            'cta_label' => 'See wholesale prices',
            'cta_url' => '/wholesale',
            'secondary_cta_label' => 'Request a quote',
            'secondary_cta_url' => '/wholesale/quote',
            'theme' => 'ink',
            'sort_order' => 10,
        ]);
    }

    private function sections(): void
    {
        $sections = [
            [
                'key' => 'promises',
                'type' => HomeSectionType::Promise,
                'sort_order' => 10,
            ],
            [
                'key' => 'festive',
                'type' => HomeSectionType::ProductRail,
                'title' => 'Festive gifting',
                'subtitle' => 'Hampers, sweets and candles, ready to give.',
                'link_label' => 'All gifts',
                'link_url' => '/c/gifts',
                'settings' => ['source' => ProductRailSource::Category->value, 'category' => 'gifts', 'limit' => 8, 'rail' => true],
                'sort_order' => 20,
            ],
            [
                'key' => 'categories',
                'type' => HomeSectionType::CategoryGrid,
                'title' => 'Shop by category',
                'link_label' => 'All categories',
                'link_url' => '/categories',
                'sort_order' => 30,
            ],
            [
                'key' => 'offers',
                'type' => HomeSectionType::ProductRail,
                'title' => "Today's offers",
                'subtitle' => 'Everyday favourites at a better price.',
                'link_label' => 'More offers',
                'link_url' => '/search?sort=discount',
                'settings' => ['source' => ProductRailSource::Offers->value, 'limit' => 8, 'rail' => true],
                'sort_order' => 40,
            ],
            [
                'key' => 'wholesale-promo',
                'type' => HomeSectionType::Promo,
                'sort_order' => 50,
            ],
            [
                'key' => 'bestsellers',
                'type' => HomeSectionType::ProductRail,
                'title' => 'Bestsellers',
                'subtitle' => 'What our regulars order again and again.',
                'link_label' => 'Shop all',
                'link_url' => '/search',
                'settings' => ['source' => ProductRailSource::Bestsellers->value, 'limit' => 8, 'rail' => true],
                'sort_order' => 60,
            ],
            [
                'key' => 'how-it-works',
                'type' => HomeSectionType::HowItWorks,
                'title' => 'How ordering works',
                'subtitle' => 'From your bag to your door, with a delivery code at the end.',
                'sort_order' => 70,
            ],
        ];

        foreach ($sections as $section) {
            HomeSection::create([...$section, 'is_active' => true]);
        }
    }
}
