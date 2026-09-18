<?php

namespace App\Support\Demo;

/**
 * TEMPORARY (Phases 2–4): replaced by the Product model.
 */
final readonly class DemoProduct
{
    /**
     * @param  list<DemoVariant>  $variants
     * @param  list<string>  $highlights
     * @param  list<string>  $tags  featured | bestseller | festive
     */
    public function __construct(
        public string $slug,
        public string $name,
        public string $brand,
        public string $category,
        public string $subcategory,
        public string $summary,
        public string $description,
        public array $variants,
        public array $highlights = [],
        public array $tags = [],
        public int $rank = 50,
        public int $addedDaysAgo = 30,
        public string $variantLabel = 'Size',
    ) {}

    public function defaultVariant(): DemoVariant
    {
        foreach ($this->variants as $variant) {
            if ($variant->inStock()) {
                return $variant;
            }
        }

        return $this->variants[0];
    }

    public function variant(?string $sku): ?DemoVariant
    {
        foreach ($this->variants as $variant) {
            if ($variant->sku === $sku) {
                return $variant;
            }
        }

        return null;
    }

    public function inStock(): bool
    {
        foreach ($this->variants as $variant) {
            if ($variant->inStock()) {
                return true;
            }
        }

        return false;
    }

    public function lowestPrice(): int
    {
        return min(array_map(fn (DemoVariant $variant): int => $variant->paise, $this->variants));
    }

    public function bestDiscount(): int
    {
        return max(array_map(fn (DemoVariant $variant): int => $variant->discountPercent(), $this->variants));
    }

    public function hasChoices(): bool
    {
        return count($this->variants) > 1;
    }

    public function hasSwatches(): bool
    {
        return $this->variants[0]->swatch !== null;
    }

    public function hasTag(string $tag): bool
    {
        return in_array($tag, $this->tags, true);
    }
}
