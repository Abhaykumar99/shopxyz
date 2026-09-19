<?php

namespace App\Livewire\Shop;

use App\Enums\ProductSort;
use App\Livewire\Concerns\AddsToCart;
use App\Livewire\Concerns\FiltersCatalog;
use App\Models\Category;
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
            'brandOptions' => $this->brandOptions(null, $query),
            'priceRanges' => self::PRICE_RANGES,
            'sorts' => ProductSort::options(),
            'filterCount' => $this->activeFilterCount(),
            'suggestions' => ['Kaju katli', 'Lipstick', 'Hamper', 'Chocolate', 'Kajal', 'Candle'],
            'categories' => Category::query()->active()->roots()->orderBy('sort_order')->get(),
        ])->layout('layouts::shop', [
            'title' => $query !== '' ? "Search: {$query}" : 'Search',
            'description' => 'Search sweets, beauty products and gifts.',
            'search' => $query,
            'noindex' => $query !== '',
        ]);
    }
}
