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
        $price = $this->slabs[0]['paise'];

        foreach ($this->slabs as $slab) {
            if ($quantity >= $slab['min']) {
                $price = $slab['paise'];
            }
        }

        return $price;
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
