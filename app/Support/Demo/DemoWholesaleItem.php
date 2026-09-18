<?php

namespace App\Support\Demo;

use App\Support\Money;

/**
 * TEMPORARY (Phases 2–5): a product offered at wholesale prices.
 * Replaced by wholesale price slabs on the ProductVariant model.
 */
final readonly class DemoWholesaleItem
{
    /**
     * @param  list<array{min: int, paise: int}>  $slabs  ascending by `min`; the first `min` is the MOQ
     */
    public function __construct(
        public DemoProduct $product,
        public DemoVariant $variant,
        public string $unit,
        public array $slabs,
    ) {}

    public function sku(): string
    {
        return $this->variant->sku;
    }

    public function moq(): int
    {
        return $this->slabs[0]['min'];
    }

    /**
     * Per-unit price for a quantity (never below the MOQ slab).
     */
    public function unitPriceFor(int $quantity): int
    {
        return $this->slabFor($quantity)['paise'];
    }

    /**
     * The slab a quantity is priced at (the MOQ slab for anything smaller).
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
     * The next cheaper slab above a quantity, or null when it is already the best.
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

    /**
     * How many more units are needed for the next cheaper slab.
     */
    public function unitsToNextSlab(int $quantity): int
    {
        $next = $this->slabAfter($quantity);

        return $next === null ? 0 : $next['min'] - $quantity;
    }

    /**
     * Label for a slab, such as "20–49" or "50+".
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
        return max(0, $this->variant->paise - $this->unitPriceFor($quantity));
    }

    public function bestPrice(): int
    {
        return $this->slabs[array_key_last($this->slabs)]['paise'];
    }

    /**
     * Saving of the best slab against the shop's retail price.
     */
    public function bestSavingPercent(): int
    {
        return Money::discountPercent($this->variant->paise, $this->bestPrice());
    }
}
