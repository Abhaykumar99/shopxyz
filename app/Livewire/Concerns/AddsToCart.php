<?php

namespace App\Livewire\Concerns;

use App\Models\ProductVariant;
use App\Support\Cart\Bag;
use App\Support\Money;

/**
 * "Add to bag" for any page that lists products, retail or wholesale. A line
 * that reaches the product's minimum wholesale quantity is priced at its band
 * price, so the confirmation says so (ADR-019).
 */
trait AddsToCart
{
    public function addToCart(string $sku, int $quantity = 1): void
    {
        $variant = ProductVariant::query()
            ->where('sku', $sku)
            ->where('is_active', true)
            ->with(['product', 'priceSlabs'])
            ->first();

        if ($variant === null || ! $variant->product?->is_active) {
            return;
        }

        $bag = app(Bag::class);
        $requested = max(1, $quantity);
        $added = $bag->add($variant, $requested);

        if ($added === 0) {
            $this->dispatch('toast', message: $variant->inStock()
                ? "You already have the most we can sell of {$variant->product->name}."
                : "{$variant->product->name} is out of stock.", tone: 'warning');

            return;
        }

        $line = $bag->lines()->firstWhere(fn ($line): bool => $line->sku() === $sku);
        $name = $variant->product->name;

        $this->dispatch('cart-updated');
        $this->dispatch('toast', message: match (true) {
            $line !== null && $line->isWholesale() => "{$name}: {$line->quantity} in your bag at ".Money::format($line->unitPrice()).' each (wholesale price).',
            $added < $requested => "Added {$added}. That's all we have of {$name} right now.",
            default => "Added {$name} to your bag (".Money::format($variant->price_paise * $added).').',
        }, tone: 'success');
    }
}
