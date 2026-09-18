<?php

namespace App\Livewire\Shop;

use App\Support\Demo\DemoCatalog;
use Illuminate\Contracts\View\View;
use Livewire\Component;

class CategoryIndex extends Component
{
    public function render(): View
    {
        $categories = DemoCatalog::categories();
        $counts = [];

        foreach ($categories as $category) {
            $counts[$category->slug] = DemoCatalog::productCount($category);
            foreach ($category->children as $child) {
                $counts[$child->slug] = DemoCatalog::productCount($child);
            }
        }

        return view('livewire.shop.category-index', [
            'categories' => $categories,
            'counts' => $counts,
        ])->layout('layouts::shop', [
            'title' => 'All categories',
            'description' => 'Browse cosmetics, confectionery and gifts.',
            'active' => 'categories',
        ]);
    }
}
