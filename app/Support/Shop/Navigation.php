<?php

namespace App\Support\Shop;

use App\Models\Category;
use Illuminate\Support\Collection;

/**
 * The top-level categories the header and footer link to. They come from the
 * catalogue the admin manages, so adding a category is enough to see it in the
 * navigation (ADR-024). Memoised per request because every page renders it.
 */
final class Navigation
{
    /**
     * Top-level active categories as `label => url`.
     *
     * @return Collection<string, string>
     */
    public static function categories(int $limit = 6): Collection
    {
        return once(fn (): Collection => Category::query()
            ->whereNull('parent_id')
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('name')
            ->limit($limit)
            ->get()
            ->mapWithKeys(fn (Category $category): array => [
                $category->name => route('shop.category', $category->slug),
            ]));
    }
}
