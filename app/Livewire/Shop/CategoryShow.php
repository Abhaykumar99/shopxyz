<?php

namespace App\Livewire\Shop;

use App\Livewire\Concerns\AddsToCart;
use App\Livewire\Concerns\FiltersCatalog;
use App\Support\Demo\DemoCatalog;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Locked;
use Livewire\Component;

class CategoryShow extends Component
{
    use AddsToCart;
    use FiltersCatalog;

    #[Locked]
    public string $slug;

    public function mount(string $category): void
    {
        abort_if(DemoCatalog::category($category) === null, 404);

        $this->slug = $category;
    }

    public function render(): View
    {
        $category = DemoCatalog::category($this->slug);
        abort_if($category === null, 404);

        $root = DemoCatalog::category($category->rootSlug());
        $siblings = $root->children ?? [];

        $breadcrumb = ['Home' => route('shop.home')];
        if ($category->parentSlug && $root) {
            $breadcrumb[$root->name] = route('shop.category', $root->slug);
        }
        $breadcrumb[$category->name] = null;

        return view('livewire.shop.category-show', [
            'category' => $category,
            'root' => $root,
            'sections' => $siblings,
            'breadcrumb' => $breadcrumb,
            'products' => $this->filteredProducts($category->slug, null),
            'brandOptions' => DemoCatalog::brands($category->slug),
            'priceRanges' => self::PRICE_RANGES,
            'sorts' => DemoCatalog::SORTS,
            'filterCount' => $this->activeFilterCount(),
        ])->layout('layouts::shop', [
            'title' => $category->name,
            'description' => $category->description,
            'active' => 'categories',
        ]);
    }
}
