<?php

namespace App\Support\Demo;

use App\Support\Money;

/**
 * TEMPORARY (Phases 2–4): replaced by the ProductVariant model.
 */
final readonly class DemoVariant
{
    public function __construct(
        public string $sku,
        public string $name,
        public int $paise,
        public ?int $mrp = null,
        public int $stock = 20,
        public ?string $swatch = null,
    ) {}

    public function inStock(): bool
    {
        return $this->stock > 0;
    }

    public function isLowStock(): bool
    {
        return $this->stock > 0 && $this->stock <= 3;
    }

    public function discountPercent(): int
    {
        return Money::discountPercent($this->mrp ?? $this->paise, $this->paise);
    }
}
