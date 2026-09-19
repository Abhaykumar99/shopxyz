<?php

namespace App\Livewire\Shop;

use App\Enums\ProductSort;
use App\Livewire\Concerns\AddsToCart;
use App\Livewire\Concerns\FiltersCatalog;
use App\Models\Category;
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
        abort_if(! Category::query()->active()->where('slug', $category)->exists(), 404);

        $this->slug = $category;
    }

    public function render(): View
    {
        $category = Category::query()
            ->active()
            ->with(['parent.children' => fn ($children) => $children->where('is_active', true)->orderBy('sort_order')])
            ->where('slug', $this->slug)
            ->firstOr(fn () => abort(404));

        $root = $category->parent ?? $category;
        $siblings = $root->relationLoaded('children')
            ? $root->children
            : $root->children()->where('is_active', true)->orderBy('sort_order')->get();

        $breadcrumb = ['Home' => route('shop.home')];

        if ($category->parent_id !== null) {
            $breadcrumb[$root->name] = route('shop.category', $root->slug);
        }

        $breadcrumb[$category->name] = null;

        return view('livewire.shop.category-show', [
            'category' => $category,
            'root' => $root,
            'sections' => $siblings,
            'breadcrumb' => $breadcrumb,
            'products' => $this->filteredProducts($category, null),
            'brandOptions' => $this->brandOptions($category, null),
            'priceRanges' => self::PRICE_RANGES,
            'sorts' => ProductSort::options(),
            'filterCount' => $this->activeFilterCount(),
        ])->layout('layouts::shop', [
            'title' => $category->name,
            'description' => $category->description,
            'active' => 'categories',
        ]);
    }
}
