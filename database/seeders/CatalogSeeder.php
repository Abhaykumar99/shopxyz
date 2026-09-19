<?php

namespace Database\Seeders;

use App\Enums\InventoryMovementType;
use App\Models\Category;
use App\Models\InventoryMovement;
use App\Models\PriceSlab;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Support\Demo\DemoCatalog;
use App\Support\Demo\DemoCategory;
use App\Support\Demo\DemoProduct;
use App\Support\Demo\DemoVariant;
use App\Support\Demo\DemoWholesale;
use App\Support\Demo\DemoWholesaleItem;
use Illuminate\Database\Seeder;

/**
 * Turns the prototype's sample catalogue (ADR-016) into real rows, so the admin
 * panel and the shop show the same products they always have. Both this seeder
 * and `App\Support\Demo` disappear together once the shop reads from the
 * database (Phase 5).
 */
class CatalogSeeder extends Seeder
{
    public function run(): void
    {
        $categories = $this->seedCategories();
        $this->seedProducts($categories);
        $this->seedWholesaleSlabs();
    }

    /**
     * @return array<string, Category>
     */
    private function seedCategories(): array
    {
        $saved = [];
        $sort = 0;

        foreach (DemoCatalog::categories() as $parent) {
            $saved[$parent->slug] = Category::create([
                'name' => $parent->name,
                'slug' => $parent->slug,
                'description' => $parent->description,
                'sort_order' => $sort += 10,
                'is_active' => true,
            ]);

            $childSort = 0;

            foreach ($parent->children as $child) {
                /** @var DemoCategory $child */
                $saved[$child->slug] = Category::create([
                    'parent_id' => $saved[$parent->slug]->id,
                    'name' => $child->name,
                    'slug' => $child->slug,
                    'description' => $child->description,
                    'sort_order' => $childSort += 10,
                    'is_active' => true,
                ]);
            }
        }

        return $saved;
    }

    /**
     * @param  array<string, Category>  $categories
     */
    private function seedProducts(array $categories): void
    {
        foreach (DemoCatalog::products() as $demo) {
            /** @var DemoProduct $demo */
            $category = $categories[$demo->subcategory] ?? $categories[$demo->category];

            $product = Product::create([
                'category_id' => $category->id,
                'name' => $demo->name,
                'slug' => $demo->slug,
                'brand' => $demo->brand,
                'short_description' => $demo->summary,
                'description' => $demo->description,
                'is_active' => true,
                'is_featured' => in_array('featured', $demo->tags, true),
                'created_at' => now()->subDays($demo->addedDaysAgo),
            ]);

            foreach ($demo->variants as $index => $demoVariant) {
                /** @var DemoVariant $demoVariant */
                $variant = $product->variants()->create([
                    'sku' => $demoVariant->sku,
                    'name' => $demoVariant->name,
                    'mrp_paise' => $demoVariant->mrp,
                    'price_paise' => $demoVariant->paise,
                    'stock_quantity' => $demoVariant->stock,
                    'low_stock_threshold' => 5,
                    'swatch_hex' => $demoVariant->swatch,
                    'is_active' => true,
                    'sort_order' => $index * 10,
                ]);

                InventoryMovement::create([
                    'product_variant_id' => $variant->id,
                    'type' => InventoryMovementType::Restock,
                    'quantity_change' => $demoVariant->stock,
                    'stock_after' => $demoVariant->stock,
                    'note' => 'Opening stock',
                    'created_at' => now()->subDays($demo->addedDaysAgo),
                ]);
            }
        }
    }

    /**
     * Wholesale price bands for the twelve bulk-ready products (ADR-019).
     */
    private function seedWholesaleSlabs(): void
    {
        foreach (DemoWholesale::items() as $item) {
            /** @var DemoWholesaleItem $item */
            $variant = ProductVariant::where('sku', $item->sku())->first();

            if ($variant === null) {
                continue;
            }

            foreach ($item->slabs as $slab) {
                PriceSlab::create([
                    'product_variant_id' => $variant->id,
                    'min_quantity' => $slab['min'],
                    'unit_price_paise' => $slab['paise'],
                    'is_active' => true,
                ]);
            }
        }
    }
}
