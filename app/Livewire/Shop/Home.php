<?php

namespace App\Livewire\Shop;

use App\Livewire\Concerns\AddsToCart;
use App\Models\Category;
use App\Models\Product;
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
        $categories = Category::query()
            ->active()
            ->roots()
            ->with(['children' => fn ($children) => $children->where('is_active', true)])
            ->orderBy('sort_order')
            ->get();

        // One query for every tile's count, rather than one per tile.
        $perCategory = Product::query()
            ->active()
            ->selectRaw('category_id, count(*) as total')
            ->groupBy('category_id')
            ->pluck('total', 'category_id');

        return view('livewire.shop.home', [
            'heroSlides' => $content->desktopHero(),
            'mobileHero' => $content->mobileHero(),
            'promos' => $content->promos(),
            'blocks' => $content->sections(),
            'categories' => $categories,
            'counts' => $categories
                ->mapWithKeys(fn (Category $category): array => [
                    $category->slug => (int) ($perCategory[$category->id] ?? 0)
                        + (int) $category->children->sum(fn (Category $child): int => (int) ($perCategory[$child->id] ?? 0)),
                ])
                ->all(),
        ])->layout('layouts::shop', [
            'title' => null,
            'description' => $shop->tagline,
            'active' => 'home',
        ]);
    }
}
