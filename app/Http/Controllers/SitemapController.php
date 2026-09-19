<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Http\Response;

/**
 * A sitemap built from the catalogue the admin manages, so a new product is
 * discoverable without anyone editing a file. Staff areas are never listed and
 * are disallowed in robots.txt.
 */
final class SitemapController extends Controller
{
    public function __invoke(): Response
    {
        $urls = [
            ['loc' => route('shop.home'), 'priority' => '1.0', 'changefreq' => 'daily'],
            ['loc' => route('shop.categories'), 'priority' => '0.8', 'changefreq' => 'weekly'],
            ['loc' => route('wholesale.index'), 'priority' => '0.8', 'changefreq' => 'weekly'],
            ['loc' => route('wholesale.quote'), 'priority' => '0.5', 'changefreq' => 'monthly'],
        ];

        foreach (array_keys(InfoPageController::PAGES) as $page) {
            $urls[] = ['loc' => route('pages.show', $page), 'priority' => '0.3', 'changefreq' => 'yearly'];
        }

        foreach (Category::where('is_active', true)->orderBy('sort_order')->get() as $category) {
            $urls[] = [
                'loc' => route('shop.category', $category->slug),
                'priority' => '0.7',
                'changefreq' => 'weekly',
                'lastmod' => $category->updated_at?->toAtomString(),
            ];
        }

        foreach (Product::where('is_active', true)->orderBy('name')->get() as $product) {
            $urls[] = [
                'loc' => route('shop.product', $product->slug),
                'priority' => '0.6',
                'changefreq' => 'weekly',
                'lastmod' => $product->updated_at?->toAtomString(),
            ];
        }

        return response()
            ->view('sitemap', ['urls' => $urls])
            ->header('Content-Type', 'application/xml');
    }
}
