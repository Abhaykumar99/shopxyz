<?php

namespace App\Livewire\Concerns;

use App\Support\Demo\DemoCart;
use App\Support\Demo\DemoCartLine;
use App\Support\Demo\DemoCatalog;
use App\Support\Money;

/**
 * "Add to bag" for any page that lists products, retail or wholesale. A line
 * that reaches the product's minimum wholesale quantity is priced at its slab
 * price, so the confirmation says so (ADR-019).
 */
trait AddsToCart
{
    public function addToCart(string $sku, int $quantity = 1): void
    {
        $found = DemoCatalog::findSku($sku);

        if ($found === null) {
            return;
        }

        [$product, $variant] = $found;
        $cart = app(DemoCart::class);
        $requested = max(1, $quantity);
        $added = $cart->add($sku, $requested);

        if ($added === 0) {
            $this->dispatch('toast', message: $variant->inStock()
                ? "You already have the most we can sell of {$product->name}."
                : "{$product->name} is out of stock.", tone: 'warning');

            return;
        }

        $line = new DemoCartLine($product, $variant, $cart->quantityOf($sku));
        $this->dispatch('cart-updated');
        $this->dispatch('toast', message: match (true) {
            $line->isWholesale() => "{$product->name}: {$line->quantity} in your bag at ".Money::format($line->unitPrice()).' each (wholesale price).',
            $added < $requested => "Added {$added}. That's all we have of {$product->name} right now.",
            default => "Added {$product->name} to your bag (".Money::format($variant->paise * $added).').',
        }, tone: 'success');
    }
}
