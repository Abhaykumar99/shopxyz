<?php

namespace App\Livewire\Wholesale;

use App\Livewire\Concerns\AddsToCart;
use App\Support\Demo\DemoCart;
use App\Support\Demo\DemoWholesale;
use App\Support\Demo\DemoWholesaleItem;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Str;
use Livewire\Attributes\Url;
use Livewire\Component;

/**
 * Wholesale catalogue: quantity price slabs and minimum quantities, ordered
 * through the ordinary bag and checkout (ADR-019). Buyers who need custom
 * pricing, packing or branding use the separate quote page.
 */
class WholesalePage extends Component
{
    use AddsToCart;

    public const CATEGORIES = [
        '' => 'All products',
        'confectionery' => 'Sweets and chocolates',
        'gifts' => 'Gifts and hampers',
        'cosmetics' => 'Cosmetics',
    ];

    #[Url(as: 'category', except: '')]
    public string $category = '';

    #[Url(as: 'q', except: '')]
    public string $search = '';

    /** @var array<string, int|string> Quantities typed next to each product */
    public array $quantities = [];

    public function mount(): void
    {
        foreach (DemoWholesale::items() as $item) {
            $this->quantities[$item->sku()] = $item->moq();
        }
    }

    /**
     * Adds a bulk quantity to the bag, never below the minimum for that product.
     */
    public function addBulkToCart(string $sku): void
    {
        $item = DemoWholesale::item($sku);

        if ($item === null) {
            return;
        }

        $requested = (int) ($this->quantities[$sku] ?? $item->moq());
        $quantity = min(DemoWholesale::MAX_QUANTITY, max($item->moq(), $requested));

        if ($requested < $item->moq()) {
            $this->dispatch('toast', message: "The wholesale minimum for {$item->product->name} is {$item->moq()} {$item->unit}s. We have added that many.", tone: 'info');
        }

        $this->quantities[$sku] = $quantity;
        $this->addToCart($sku, $quantity);
    }

    public function render(DemoCart $cart): View
    {
        $search = Str::limit(trim($this->search), 60, '');

        return view('livewire.wholesale.wholesale-page', [
            'items' => DemoWholesale::query(
                array_key_exists($this->category, self::CATEGORIES) && $this->category !== '' ? $this->category : null,
                $search,
            ),
            'featured' => DemoWholesale::item('MG-KK-3') ?? DemoWholesale::items()[0],
            'categories' => self::CATEGORIES,
            'inBag' => collect(DemoWholesale::items())
                ->mapWithKeys(fn (DemoWholesaleItem $item): array => [$item->sku() => $cart->quantityOf($item->sku())])
                ->all(),
            'bagCount' => $cart->count(),
        ])->layout('layouts::shop', [
            'title' => 'Wholesale',
            'description' => 'Wholesale prices on sweets, gifts and cosmetics for shops, events and corporate gifting. Order online with COD or UPI.',
            'active' => 'wholesale',
        ]);
    }
}
