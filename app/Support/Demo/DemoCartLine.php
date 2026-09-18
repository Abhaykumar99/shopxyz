<?php

namespace App\Support\Demo;

/**
 * TEMPORARY (Phases 2–4): replaced by the CartItem model.
 *
 * A line is priced at the shop's retail price until it reaches the product's
 * minimum wholesale quantity, and at the matching slab price above it (ADR-019).
 */
final readonly class DemoCartLine
{
    public function __construct(
        public DemoProduct $product,
        public DemoVariant $variant,
        public int $quantity,
    ) {}

    /**
     * The wholesale slabs offered for this product, when it has any.
     */
    public function wholesale(): ?DemoWholesaleItem
    {
        return DemoWholesale::item($this->variant->sku);
    }

    /**
     * This line is large enough to earn a wholesale slab price.
     */
    public function isWholesale(): bool
    {
        $wholesale = $this->wholesale();

        return $wholesale !== null && $this->quantity >= $wholesale->moq();
    }

    public function unitPrice(): int
    {
        return $this->slab()['paise'] ?? $this->variant->paise;
    }

    /**
     * The slab this line is priced at, for the "20–49" style label.
     *
     * @return array{min: int, paise: int}|null
     */
    public function slab(): ?array
    {
        $wholesale = $this->wholesale();

        return $wholesale !== null && $this->quantity >= $wholesale->moq()
            ? $wholesale->slabFor($this->quantity)
            : null;
    }

    /**
     * The slab's quantity range, such as "20–49", for the bag line.
     */
    public function slabLabel(): ?string
    {
        $wholesale = $this->wholesale();
        $slab = $this->slab();

        if ($wholesale === null || $slab === null) {
            return null;
        }

        $index = array_search($slab['min'], array_column($wholesale->slabs, 'min'), true);

        return is_int($index) ? $wholesale->slabRange($index) : null;
    }

    /**
     * The next cheaper slab, so the bag can say "add 30 more to pay ₹840 each".
     *
     * @return array{min: int, paise: int}|null
     */
    public function nextSlab(): ?array
    {
        return $this->wholesale()?->slabAfter($this->quantity);
    }

    public function total(): int
    {
        return $this->unitPrice() * $this->quantity;
    }

    public function mrpTotal(): int
    {
        return ($this->variant->mrp ?? $this->variant->paise) * $this->quantity;
    }

    /**
     * Saving against the shop's retail price, which is what wholesale buyers care about.
     */
    public function wholesaleSaving(): int
    {
        return $this->isWholesale() ? ($this->variant->paise - $this->unitPrice()) * $this->quantity : 0;
    }

    /**
     * The shelf has enough stock for this line. Wholesale quantities are ordered
     * in for the customer, so they are never blocked by the shelf count.
     */
    public function isAvailable(): bool
    {
        return $this->isWholesale() || $this->variant->stock >= $this->quantity;
    }

    /**
     * More than the shelf holds: the shop confirms a date instead of shipping today.
     */
    public function isMadeToOrder(): bool
    {
        return $this->isWholesale() && $this->quantity > $this->variant->stock;
    }

    public function maxQuantity(): int
    {
        return $this->wholesale() !== null
            ? DemoWholesale::MAX_QUANTITY
            : max(1, min(DemoCart::MAX_PER_LINE, $this->variant->stock));
    }
}
