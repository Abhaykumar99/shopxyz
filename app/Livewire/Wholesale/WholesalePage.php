<?php

namespace App\Livewire\Wholesale;

use App\Livewire\Concerns\AddsToCart;
use App\Models\Category;
use App\Support\Catalog\WholesaleCatalog;
use App\Support\Catalog\WholesaleItem;
use App\Support\Demo\DemoCart;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Str;
use Livewire\Attributes\Url;
use Livewire\Component;

/**
 * Wholesale catalogue: quantity price bands and minimum quantities, ordered
 * through the ordinary bag and checkout (ADR-019). Buyers who need custom
 * pricing, packing or branding use the separate quote page.
 */
class WholesalePage extends Component
{
    use AddsToCart;

    #[Url(as: 'category', except: '')]
    public string $category = '';

    #[Url(as: 'q', except: '')]
    public string $search = '';

    /** @var array<string, int|string> Quantities typed next to each product */
    public array $quantities = [];

    public function mount(): void
    {
        foreach (WholesaleCatalog::query() as $item) {
            $this->quantities[$item->sku()] = $item->moq();
        }
    }

    /**
     * Adds a bulk quantity to the bag, never below the minimum for that product.
     */
    public function addBulkToCart(string $sku): void
    {
        $item = WholesaleCatalog::find($sku);

        if ($item === null) {
            return;
        }

        $requested = (int) ($this->quantities[$sku] ?? $item->moq());
        $quantity = min(WholesaleCatalog::MAX_QUANTITY, max($item->moq(), $requested));

        if ($requested < $item->moq()) {
            $this->dispatch('toast', message: "The wholesale minimum for {$item->product->name} is {$item->moq()} {$item->unit}s. We have added that many.", tone: 'info');
        }

        $this->quantities[$sku] = $quantity;
        $this->addToCart($sku, $quantity);
    }

    public function render(DemoCart $cart): View
    {
        $search = Str::limit(trim($this->search), 60, '');
        $items = WholesaleCatalog::query($this->category, $search);

        return view('livewire.wholesale.wholesale-page', [
            'items' => $items,
            'featured' => WholesaleCatalog::featured(),
            'categories' => $this->categories(),
            'inBag' => WholesaleCatalog::query()
                ->mapWithKeys(fn (WholesaleItem $item): array => [$item->sku() => $cart->quantityOf($item->sku())])
                ->all(),
            'bagCount' => $cart->count(),
        ])->layout('layouts::shop', [
            'title' => 'Wholesale',
            'description' => 'Wholesale prices on sweets, gifts and cosmetics for shops, events and corporate gifting. Order online with COD or UPI.',
            'active' => 'wholesale',
        ]);
    }

    /**
     * The filter tabs, read from the categories the admin manages rather than a
     * list kept here.
     *
     * @return array<string, string>
     */
    private function categories(): array
    {
        return ['' => 'All products'] + Category::query()
            ->active()
            ->roots()
            ->orderBy('sort_order')
            ->pluck('name', 'slug')
            ->all();
    }
}
