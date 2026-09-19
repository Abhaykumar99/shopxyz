<?php

namespace App\Support\Home;

use App\Enums\BannerPlacement;
use App\Enums\HomeSectionType;
use App\Enums\ProductRailSource;
use App\Models\Banner;
use App\Models\HomeSection;
use App\Models\HomeSectionItem;
use App\Support\Demo\DemoCatalog;
use App\Support\Demo\DemoProduct;
use Illuminate\Support\Collection;

/**
 * The homepage, as the admin arranged it (ADR-024).
 *
 * Banners and sections are rows in the database; nothing on the page is
 * hardcoded. Which products a row shows is stored here too, while the product
 * details themselves still come from the sample catalogue until the shop reads
 * from the database (Phase 5) — at that point only `products()` below changes.
 */
final class HomeContent
{
    /**
     * Hero slides for desktop, in the admin's order.
     *
     * @return Collection<int, Banner>
     */
    public function desktopHero(): Collection
    {
        return $this->banners(BannerPlacement::DesktopHero);
    }

    /**
     * The single banner phones and tablets see.
     */
    public function mobileHero(): ?Banner
    {
        return $this->banners(BannerPlacement::MobileHero)->first();
    }

    /**
     * @return Collection<int, Banner>
     */
    public function promos(): Collection
    {
        return $this->banners(BannerPlacement::Promo);
    }

    /**
     * Every block of the homepage, in order, with its products resolved.
     *
     * @return Collection<int, array{section: HomeSection, products: Collection<int, DemoProduct>}>
     */
    public function sections(): Collection
    {
        $blocks = [];

        foreach (HomeSection::query()->live()->with('items.product', 'items.category')->orderBy('sort_order')->get() as $section) {
            $products = $this->products($section);

            if ($section->type === HomeSectionType::ProductRail && $products->isEmpty()) {
                continue;
            }

            $blocks[] = ['section' => $section, 'products' => $products];
        }

        return collect($blocks);
    }

    /**
     * A few products to dress a banner that has no picture of its own, taken
     * from wherever its button points, so the slide stays rich without anyone
     * writing products into the template (ADR-024).
     *
     * @return Collection<int, DemoProduct>
     */
    public function showcase(Banner $banner, int $limit = 3): Collection
    {
        if ($banner->image_path) {
            return new Collection;
        }

        $target = (string) ($banner->cta_url ?? '');
        $category = str_starts_with($target, '/c/') ? substr($target, 3) : null;

        $products = $category !== null
            ? DemoCatalog::query(category: $category, inStockOnly: true)
            : DemoCatalog::tagged('featured', 12);

        return $products->take($limit)->values();
    }

    /**
     * @return Collection<int, Banner>
     */
    private function banners(BannerPlacement $placement): Collection
    {
        return Banner::query()
            ->live()
            ->placement($placement)
            ->orderBy('sort_order')
            ->get();
    }

    /**
     * The products a row shows.
     *
     * @return Collection<int, DemoProduct>
     */
    private function products(HomeSection $section): Collection
    {
        if ($section->type !== HomeSectionType::ProductRail) {
            return new Collection;
        }

        $limit = $section->limit();

        return match ($section->source()) {
            ProductRailSource::Bestsellers => DemoCatalog::tagged('bestseller', $limit),
            ProductRailSource::NewArrivals => DemoCatalog::query(sort: 'newest', inStockOnly: true)->take($limit)->values(),
            ProductRailSource::Offers => DemoCatalog::offers($limit),
            ProductRailSource::Featured => DemoCatalog::tagged('featured', $limit),
            ProductRailSource::Category => DemoCatalog::query(category: $section->categorySlug(), inStockOnly: true)->take($limit)->values(),
            ProductRailSource::Manual => $this->pickedProducts($section, $limit),
            default => new Collection,
        };
    }

    /**
     * Products the admin chose by hand, in their order.
     *
     * @return Collection<int, DemoProduct>
     */
    private function pickedProducts(HomeSection $section, int $limit): Collection
    {
        return $section->items
            ->map(fn (HomeSectionItem $item): ?DemoProduct => $item->product ? DemoCatalog::product($item->product->slug) : null)
            ->filter()
            ->take($limit)
            ->values();
    }
}
