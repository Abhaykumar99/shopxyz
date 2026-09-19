<?php

namespace App\Livewire\Cart;

use App\Models\CartItem;
use App\Models\ProductVariant;
use App\Support\Cart\Bag;
use App\Support\Catalog\WholesaleItem;
use App\Support\Money;
use App\Support\ShopSettings;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;
use Livewire\Component;

class CartPage extends Component
{
    /** @var array<string, int> */
    public array $quantities = [];

    public function mount(Bag $bag): void
    {
        $this->syncQuantities($bag);
    }

    public function updatedQuantities(mixed $value, string $sku): void
    {
        $bag = app(Bag::class);
        $previous = $bag->quantityOf($sku);

        // Typing a quantity never adds a line that was not already in the bag.
        if ($previous === 0) {
            $this->syncQuantities($bag);

            return;
        }

        $variant = $this->variant($sku);

        if ($variant === null) {
            $this->syncQuantities($bag);

            return;
        }

        $requested = max(1, (int) $value);
        $stored = $bag->setQuantity($variant, $requested);
        $wholesale = WholesaleItem::for($variant);

        if ($stored < $requested) {
            $this->dispatch('toast', message: $wholesale !== null
                ? "The most we take in one order is {$stored}. Ask us for a quote for anything larger."
                : "We can only sell {$stored} of this item right now.", tone: 'warning');
        } elseif ($wholesale !== null && $previous >= $wholesale->moq() && $stored < $wholesale->moq()) {
            $this->dispatch('toast', message: "Below {$wholesale->moq()}, this line goes back to the retail price of ".Money::format($variant->price_paise).'.', tone: 'info');
        }

        $this->syncQuantities($bag);
        $this->dispatch('cart-updated');
    }

    public function remove(string $sku): void
    {
        $bag = app(Bag::class);

        if ($bag->quantityOf($sku) === 0) {
            return;
        }

        $variant = $this->variant($sku);

        if ($variant === null) {
            return;
        }

        $name = $variant->product->name;

        $bag->remove($variant);
        $this->syncQuantities($bag);
        $this->dispatch('cart-updated');
        $this->dispatch('toast', message: $name.' removed from your bag.', tone: 'info');
    }

    public function render(Bag $bag, ShopSettings $shop): View
    {
        $lines = $bag->lines();

        return view('livewire.cart.cart-page', [
            'lines' => $lines,
            'summary' => $bag->summary($shop),
            'blocked' => $bag->hasUnavailableLines(),
            'freeDeliveryProgress' => $this->freeDeliveryProgress($lines, $shop),
        ])->layout('layouts::shop', [
            'title' => 'Your bag',
            'active' => 'cart',
            'noindex' => true,
        ]);
    }

    /**
     * The variant behind a SKU the browser sent, resolved through the bag so a
     * SKU that is not in it cannot be acted on.
     */
    private function variant(string $sku): ?ProductVariant
    {
        return app(Bag::class)->lines()
            ->first(fn (CartItem $line): bool => $line->sku() === $sku)?->variant;
    }

    private function syncQuantities(Bag $bag): void
    {
        $this->quantities = $bag->lines()
            ->mapWithKeys(fn (CartItem $line): array => [$line->sku() => $line->quantity])
            ->all();
    }

    /**
     * @param  Collection<int, CartItem>  $lines
     */
    private function freeDeliveryProgress(Collection $lines, ShopSettings $shop): int
    {
        if ($shop->freeDeliveryAbovePaise <= 0) {
            return 100;
        }

        $subtotal = (int) $lines->sum(fn (CartItem $line): int => $line->total());

        return (int) min(100, floor($subtotal * 100 / $shop->freeDeliveryAbovePaise));
    }
}
