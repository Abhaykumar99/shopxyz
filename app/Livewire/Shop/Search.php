<?php

namespace App\Livewire\Shop;

use App\Livewire\Concerns\AddsToCart;
use App\Livewire\Concerns\FiltersCatalog;
use App\Support\Demo\DemoCatalog;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Str;
use Livewire\Attributes\Url;
use Livewire\Component;

class Search extends Component
{
    use AddsToCart;
    use FiltersCatalog;

    #[Url(except: '')]
    public string $q = '';

    public function updatedQ(): void
    {
        $this->q = Str::limit(trim($this->q), 80, '');
        $this->resetPage();
    }

    public function render(): View
    {
        $query = Str::limit(trim($this->q), 80, '');

        return view('livewire.shop.search', [
            'query' => $query,
            'products' => $this->filteredProducts(null, $query),
            'brandOptions' => DemoCatalog::brands(null, $query),
            'priceRanges' => self::PRICE_RANGES,
            'sorts' => DemoCatalog::SORTS,
            'filterCount' => $this->activeFilterCount(),
            'suggestions' => ['Kaju katli', 'Lipstick', 'Hamper', 'Chocolate', 'Kajal', 'Candle'],
            'categories' => DemoCatalog::categories(),
        ])->layout('layouts::shop', [
            'title' => $query !== '' ? "Search: {$query}" : 'Search',
            'description' => 'Search sweets, beauty products and gifts.',
            'search' => $query,
            'noindex' => $query !== '',
        ]);
    }
}
