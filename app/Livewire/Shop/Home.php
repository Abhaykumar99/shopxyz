<?php

namespace App\Livewire\Shop;

use App\Livewire\Concerns\AddsToCart;
use App\Support\Demo\DemoCatalog;
use App\Support\ShopSettings;
use Illuminate\Contracts\View\View;
use Livewire\Component;

class Home extends Component
{
    use AddsToCart;

    public function render(ShopSettings $shop): View
    {
        $categories = DemoCatalog::categories();

        return view('livewire.shop.home', [
            'categories' => $categories,
            'counts' => collect($categories)->mapWithKeys(fn ($category): array => [$category->slug => DemoCatalog::productCount($category)])->all(),
            'festive' => DemoCatalog::tagged('festive'),
            'offers' => DemoCatalog::offers(),
            'bestsellers' => DemoCatalog::tagged('bestseller'),
        ])->layout('layouts::shop', [
            'title' => null,
            'description' => $shop->tagline,
            'active' => 'home',
        ]);
    }
}
