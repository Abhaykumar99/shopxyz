<?php

namespace App\Support\Home;

use App\Enums\BannerPlacement;
use App\Enums\HomeSectionType;
use App\Enums\ProductRailSource;
use App\Enums\ProductSort;
use App\Models\Banner;
use App\Models\Category;
use App\Models\HomeSection;
use App\Models\HomeSectionItem;
use App\Models\Product;
use Illuminate\Support\Collection;

/**
 * The homepage, as the admin arranged it (ADR-024).
 *
 * Banners and sections are rows in the database; nothing on the page is
 * hardcoded. Which products a row shows is stored here too, while the product
 * products themselves are read from the catalogue by `products()` below.
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
     * @return Collection<int, array{section: HomeSection, products: Collection<int, Product>}>
     */
    public function sections(): Collection
    {
        return once(fn (): Collection => $this->buildSections());
    }

    /**
     * @return Collection<int, array{section: HomeSection, products: Collection<int, Product>}>
     */
    private function buildSections(): Collection
    {
        $blocks = [];

        foreach (HomeSection::query()->live()->with([
            'items.product.category.parent',
            'items.product.variants' => fn ($variants) => $variants->where('is_active', true)->orderBy('sort_order')->orderBy('id'),
            'items.category',
        ])->orderBy('sort_order')->get() as $section) {
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
     * @return Collection<int, Product>
     */
    public function showcase(Banner $banner, int $limit = 3): Collection
    {
        return once(fn (): Collection => $this->buildShowcase($banner, $limit));
    }

    /**
     * @return Collection<int, Product>
     */
    private function buildShowcase(Banner $banner, int $limit): Collection
    {
        if ($banner->image_path) {
            return new Collection;
        }

        $target = (string) ($banner->cta_url ?? '');
        $category = str_starts_with($target, '/c/') ? substr($target, 3) : null;

        $products = Product::query()
            ->active()
            ->forListing()
            ->inStock()
            ->when(
                $category !== null,
                fn ($query) => $query->inCategory($this->category($category)),
                fn ($query) => $query->where('is_featured', true),
            )
            ->sorted(ProductSort::Popular)
            ->limit(12)
            ->get();

        return $products->take($limit)->values();
    }

    /**
     * Memoised for the request rather than cached across requests, the same way
     * `App\Support\Shop\Navigation` does it.
     *
     * Two reasons not to reach for `Cache` here. `config/cache.php` sets
     * `serializable_classes => false`, so Laravel refuses to deserialise objects
     * out of the cache at all — a deliberate hardening default worth keeping. And
     * `live()` is a function of `now()`, so a banner scheduled for this evening
     * must be able to appear on its own. What this removes is the repeated query
     * inside one render, which is where the cost actually was.
     *
     * @return Collection<int, Banner>
     */
    private function banners(BannerPlacement $placement): Collection
    {
        return once(fn (): Collection => Banner::query()
            ->live()
            ->placement($placement)
            ->orderBy('sort_order')
            ->get());
    }

    /**
     * The products a row shows.
     *
     * @return Collection<int, Product>
     */
    private function products(HomeSection $section): Collection
    {
        if ($section->type !== HomeSectionType::ProductRail) {
            return new Collection;
        }

        $limit = $section->limit();

        if ($section->source() === ProductRailSource::Manual) {
            return $this->pickedProducts($section, $limit);
        }

        $query = Product::query()->active()->forListing()->inStock();

        return match ($section->source()) {
            // Bestsellers is a real measurement now: what the shop has actually
            // sold recently, rather than a tag someone set by hand.
            ProductRailSource::Bestsellers => $query->sorted(ProductSort::Popular)->limit($limit)->get(),
            ProductRailSource::NewArrivals => $query->sorted(ProductSort::Newest)->limit($limit)->get(),
            ProductRailSource::Offers => $query->sorted(ProductSort::Discount)->limit($limit)->get(),
            ProductRailSource::Featured => $query->where('is_featured', true)->sorted(ProductSort::Popular)->limit($limit)->get(),
            ProductRailSource::Category => $query
                ->inCategory($this->category($section->categorySlug()))
                ->sorted(ProductSort::Popular)
                ->limit($limit)
                ->get(),
            default => new Collection,
        };
    }

    private function category(?string $slug): ?Category
    {
        return $slug === null ? null : Category::query()->active()->where('slug', $slug)->first();
    }

    /**
     * Products the admin chose by hand, in their order.
     *
     * @return Collection<int, Product>
     */
    private function pickedProducts(HomeSection $section, int $limit): Collection
    {
        return $section->items
            ->map(fn (HomeSectionItem $item): ?Product => $item->product)
            ->filter(fn (?Product $product): bool => $product !== null && $product->is_active)
            ->take($limit)
            ->values();
    }
}
