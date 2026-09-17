<?php

namespace App\Livewire\Cart;

use App\Support\Demo\DemoCart;
use App\Support\Demo\DemoCartLine;
use App\Support\Demo\DemoCatalog;
use App\Support\ShopSettings;
use Illuminate\Contracts\View\View;
use Livewire\Component;

class CartPage extends Component
{
    /** @var array<string, int> */
    public array $quantities = [];

    public function mount(DemoCart $cart): void
    {
        $this->syncQuantities($cart);
    }

    public function updatedQuantities(mixed $value, string $sku): void
    {
        $cart = app(DemoCart::class);

        if ($cart->quantityOf($sku) === 0) {
            $this->syncQuantities($cart);

            return;
        }

        $requested = max(1, (int) $value);
        $stored = $cart->setQuantity($sku, $requested);

        if ($stored < $requested) {
            $this->dispatch('toast', message: "We can only sell {$stored} of this item right now.", tone: 'warning');
        }

        $this->syncQuantities($cart);
        $this->dispatch('cart-updated');
    }

    public function remove(string $sku): void
    {
        $cart = app(DemoCart::class);
        $found = DemoCatalog::findSku($sku);

        if ($cart->quantityOf($sku) === 0) {
            return;
        }

        $cart->remove($sku);
        $this->syncQuantities($cart);
        $this->dispatch('cart-updated');
        $this->dispatch('toast', message: ($found ? $found[0]->name : 'Item').' removed from your bag.', tone: 'info');
    }

    public function render(DemoCart $cart, ShopSettings $shop): View
    {
        $lines = $cart->lines();

        return view('livewire.cart.cart-page', [
            'lines' => $lines,
            'summary' => $cart->summary($shop),
            'blocked' => $cart->hasUnavailableLines(),
            'freeDeliveryProgress' => $this->freeDeliveryProgress($lines, $shop),
        ])->layout('layouts::shop', [
            'title' => 'Your bag',
            'active' => 'cart',
            'noindex' => true,
        ]);
    }

    private function syncQuantities(DemoCart $cart): void
    {
        $this->quantities = collect($cart->lines())
            ->mapWithKeys(fn (DemoCartLine $line): array => [$line->variant->sku => $line->quantity])
            ->all();
    }

    /**
     * @param  list<DemoCartLine>  $lines
     */
    private function freeDeliveryProgress(array $lines, ShopSettings $shop): int
    {
        if ($shop->freeDeliveryAbovePaise <= 0) {
            return 100;
        }

        $subtotal = array_sum(array_map(fn (DemoCartLine $line): int => $line->total(), $lines));

        return (int) min(100, floor($subtotal * 100 / $shop->freeDeliveryAbovePaise));
    }
}
