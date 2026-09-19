<?php

namespace App\Livewire\Shop;

use App\Livewire\Concerns\AddsToCart;
use App\Support\Demo\DemoCatalog;
use App\Support\Home\HomeContent;
use App\Support\ShopSettings;
use Illuminate\Contracts\View\View;
use Livewire\Component;

/**
 * The homepage is whatever the admin arranged: banners and blocks come from the
 * database, in their order and within their dates (ADR-024).
 */
class Home extends Component
{
    use AddsToCart;

    public function render(HomeContent $content, ShopSettings $shop): View
    {
        $categories = DemoCatalog::categories();

        return view('livewire.shop.home', [
            'heroSlides' => $content->desktopHero(),
            'mobileHero' => $content->mobileHero(),
            'promos' => $content->promos(),
            'blocks' => $content->sections(),
            'categories' => $categories,
            'counts' => collect($categories)
                ->mapWithKeys(fn ($category): array => [$category->slug => DemoCatalog::productCount($category)])
                ->all(),
        ])->layout('layouts::shop', [
            'title' => null,
            'description' => $shop->tagline,
            'active' => 'home',
        ]);
    }
}
