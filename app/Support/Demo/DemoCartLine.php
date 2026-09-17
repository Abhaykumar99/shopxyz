<?php

namespace App\Support\Demo;

/**
 * TEMPORARY (Phases 2–4): replaced by the CartItem model.
 */
final readonly class DemoCartLine
{
    public function __construct(
        public DemoProduct $product,
        public DemoVariant $variant,
        public int $quantity,
    ) {}

    public function total(): int
    {
        return $this->variant->paise * $this->quantity;
    }

    public function mrpTotal(): int
    {
        return ($this->variant->mrp ?? $this->variant->paise) * $this->quantity;
    }

    /**
     * The shelf has enough stock for this line.
     */
    public function isAvailable(): bool
    {
        return $this->variant->stock >= $this->quantity;
    }

    public function maxQuantity(): int
    {
        return max(1, min(DemoCart::MAX_PER_LINE, $this->variant->stock));
    }
}
