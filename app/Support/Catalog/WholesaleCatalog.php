<?php

namespace App\Support\Catalog;

use App\Models\Category;
use App\Models\ProductVariant;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

/**
 * Every variant the shop sells at wholesale prices: the ones with price bands
 * (ADR-019). Reads the same catalogue as the rest of the shop, so a product the
 * admin switches off disappears from here too.
 */
final class WholesaleCatalog
{
    /**
     * The largest quantity the bag will take on one line, so a typo cannot
     * order a warehouse.
     */
    public const MAX_QUANTITY = 10000;

    /**
     * @return Collection<int, WholesaleItem>
     */
    public static function query(?string $categorySlug = null, ?string $search = null): Collection
    {
        // The wholesale page asks for the same list three times in one render —
        // the grid, the featured pick and what is already in the bag.
        return once(fn (): Collection => self::build($categorySlug, $search));
    }

    /**
     * @return Collection<int, WholesaleItem>
     */
    private static function build(?string $categorySlug, ?string $search): Collection
    {
        $category = $categorySlug === null || $categorySlug === ''
            ? null
            : Category::query()->active()->where('slug', $categorySlug)->first();

        $variants = ProductVariant::query()
            ->where('is_active', true)
            ->whereHas('priceSlabs', fn ($slabs) => $slabs->where('is_active', true))
            ->whereHas('product', function ($product) use ($category): void {
                $product->where('is_active', true);

                if ($category !== null) {
                    $product->whereIn('category_id', $category->selfAndDescendantIds());
                }
            })
            ->with(['product.category.parent', 'priceSlabs'])
            ->orderBy('product_id')
            ->orderBy('sort_order')
            ->get();

        return $variants
            ->map(fn (ProductVariant $variant): ?WholesaleItem => WholesaleItem::for($variant))
            ->filter()
            ->filter(fn (WholesaleItem $item): bool => self::matches($item, $search))
            ->values();
    }

    public static function find(string $sku): ?WholesaleItem
    {
        $variant = ProductVariant::query()
            ->where('sku', $sku)
            ->where('is_active', true)
            ->with(['product.category.parent', 'priceSlabs'])
            ->first();

        return $variant === null ? null : WholesaleItem::for($variant);
    }

    /**
     * The one the page leads with: the biggest saving on offer.
     */
    public static function featured(): ?WholesaleItem
    {
        return self::query()->sortByDesc(fn (WholesaleItem $item): int => $item->bestSavingPercent())->first();
    }

    /**
     * Substring matching, term by term, the same way the catalogue search works.
     */
    private static function matches(WholesaleItem $item, ?string $search): bool
    {
        $terms = array_filter(explode(' ', Str::lower(trim((string) $search))));

        if ($terms === []) {
            return true;
        }

        $haystack = Str::lower(implode(' ', array_filter([
            $item->product->name,
            $item->product->brand,
            $item->variant->name,
            $item->unit,
        ])));

        foreach ($terms as $term) {
            if (! str_contains($haystack, $term)) {
                return false;
            }
        }

        return true;
    }
}
