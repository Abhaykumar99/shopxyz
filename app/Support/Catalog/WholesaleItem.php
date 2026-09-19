<?php

namespace App\Support\Catalog;

use App\Models\Product;
use App\Models\ProductVariant;
use App\Support\Money;

/**
 * A variant offered at wholesale prices, read from its price slabs (ADR-019).
 *
 * The slabs live in `price_slabs` and the pricing rules on `ProductVariant`;
 * this wraps them in the shape the wholesale screens read — the slab table, the
 * bulk block on a product page and the cart line all ask the same questions of
 * it. Building it once, from a variant with `priceSlabs` already loaded, keeps
 * those screens off a query per row.
 */
final readonly class WholesaleItem
{
    /**
     * @param  list<array{min: int, paise: int}>  $slabs  ascending by `min`; the first `min` is the minimum order
     */
    public function __construct(
        public Product $product,
        public ProductVariant $variant,
        public string $unit,
        public array $slabs,
    ) {}

    /**
     * Null when the variant is retail only, which is most of the catalogue.
     */
    public static function for(ProductVariant $variant): ?self
    {
        $slabs = $variant->priceSlabs
            ->where('is_active', true)
            ->sortBy('min_quantity')
            ->map(fn ($slab): array => ['min' => (int) $slab->min_quantity, 'paise' => (int) $slab->unit_price_paise])
            ->values()
            ->all();

        if ($slabs === []) {
            return null;
        }

        return new self(
            product: $variant->product,
            variant: $variant,
            unit: $variant->unit ?: 'unit',
            slabs: $slabs,
        );
    }

    public function sku(): string
    {
        return $this->variant->sku;
    }

    /**
     * The minimum order quantity: the smallest band.
     */
    public function moq(): int
    {
        return $this->slabs[0]['min'];
    }

    /**
     * Per-unit price for a quantity, never below the minimum band.
     */
    public function unitPriceFor(int $quantity): int
    {
        return $this->slabFor($quantity)['paise'];
    }

    /**
     * The band a quantity is priced at (the minimum band for anything smaller).
     *
     * @return array{min: int, paise: int}
     */
    public function slabFor(int $quantity): array
    {
        $match = $this->slabs[0];

        foreach ($this->slabs as $slab) {
            if ($quantity >= $slab['min']) {
                $match = $slab;
            }
        }

        return $match;
    }

    /**
     * The next cheaper band above a quantity, or null when it is already the best.
     *
     * @return array{min: int, paise: int}|null
     */
    public function slabAfter(int $quantity): ?array
    {
        foreach ($this->slabs as $slab) {
            if ($quantity < $slab['min']) {
                return $slab;
            }
        }

        return null;
    }

    public function unitsToNextSlab(int $quantity): int
    {
        $next = $this->slabAfter($quantity);

        return $next === null ? 0 : $next['min'] - $quantity;
    }

    /**
     * Label for a band, such as "20–49" or "50+".
     */
    public function slabRange(int $index): string
    {
        $slab = $this->slabs[$index];
        $next = $this->slabs[$index + 1]['min'] ?? null;

        return $next === null ? "{$slab['min']}+" : "{$slab['min']}–".($next - 1);
    }

    /**
     * Saving per unit at a quantity, against the shop's retail price.
     */
    public function savingFor(int $quantity): int
    {
        return max(0, $this->variant->price_paise - $this->unitPriceFor($quantity));
    }

    public function bestPrice(): int
    {
        return $this->slabs[array_key_last($this->slabs)]['paise'];
    }

    public function bestSavingPercent(): int
    {
        return Money::discountPercent($this->variant->price_paise, $this->bestPrice());
    }
}
