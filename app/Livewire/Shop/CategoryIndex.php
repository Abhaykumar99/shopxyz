<?php

namespace App\Livewire\Shop;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;
use Livewire\Component;

class CategoryIndex extends Component
{
    public function render(): View
    {
        $categories = Category::query()
            ->active()
            ->roots()
            ->with(['children' => fn ($children) => $children->where('is_active', true)->orderBy('sort_order')])
            ->orderBy('sort_order')
            ->get();

        return view('livewire.shop.category-index', [
            'categories' => $categories,
            'counts' => $this->counts($categories),
        ])->layout('layouts::shop', [
            'title' => 'All categories',
            'description' => 'Browse cosmetics, confectionery and gifts.',
            'active' => 'categories',
        ]);
    }

    /**
     * How many products sit under each category, counting subcategories, in one
     * query rather than one per tile.
     *
     * @param  Collection<int, Category>  $categories
     * @return array<string, int>
     */
    private function counts(Collection $categories): array
    {
        $perCategory = Product::query()
            ->active()
            ->selectRaw('category_id, count(*) as total')
            ->groupBy('category_id')
            ->pluck('total', 'category_id');

        $counts = [];

        foreach ($categories as $category) {
            $own = (int) ($perCategory[$category->id] ?? 0);

            foreach ($category->children as $child) {
                $counts[$child->slug] = (int) ($perCategory[$child->id] ?? 0);
                $own += $counts[$child->slug];
            }

            $counts[$category->slug] = $own;
        }

        return $counts;
    }
}
