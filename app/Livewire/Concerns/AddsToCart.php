<?php

namespace App\Livewire\Concerns;

use App\Support\Demo\DemoCart;
use App\Support\Demo\DemoCatalog;
use App\Support\Money;

/**
 * "Add to bag" for any page that lists products.
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
        $added = app(DemoCart::class)->add($sku, max(1, $quantity));

        if ($added === 0) {
            $this->dispatch('toast', message: $variant->inStock()
                ? "You already have the most we can sell of {$product->name}."
                : "{$product->name} is out of stock.", tone: 'warning');

            return;
        }

        $this->dispatch('cart-updated');
        $this->dispatch('toast', message: $added < $quantity
            ? "Added {$added}. That's all we have of {$product->name} right now."
            : "Added {$product->name} to your bag (".Money::format($variant->paise * $added).').', tone: 'success');
    }
}
