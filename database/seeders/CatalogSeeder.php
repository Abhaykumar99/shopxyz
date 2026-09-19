<?php

namespace Database\Seeders;

use App\Enums\InventoryMovementType;
use App\Models\Category;
use App\Models\InventoryMovement;
use App\Models\PriceSlab;
use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

/**
 * The shop's opening catalogue, read from `data/catalog.php`.
 *
 * This is the stock a new install starts with, not a fixture: from here the
 * admin panel is what adds, edits and retires products. Every variant gets an
 * opening stock movement, so the running total in `inventory_movements` is
 * complete from the first day.
 */
class CatalogSeeder extends Seeder
{
    public function run(): void
    {
        /** @var array{categories: list<array<string, mixed>>, products: list<array<string, mixed>>} $catalog */
        $catalog = require __DIR__.'/data/catalog.php';

        $categories = $this->seedCategories($catalog['categories']);
        $this->seedProducts($catalog['products'], $categories);
    }

    /**
     * @param  list<array<string, mixed>>  $rows
     * @return array<string, Category>
     */
    private function seedCategories(array $rows): array
    {
        $saved = [];

        // Parents are listed before their children, so a child always finds its
        // parent already saved.
        foreach ($rows as $row) {
            $saved[$row['slug']] = Category::create([
                'parent_id' => $row['parent'] === null ? null : $saved[$row['parent']]->id,
                'name' => $row['name'],
                'slug' => $row['slug'],
                'description' => $row['description'],
                'sort_order' => $row['sort_order'],
                'is_active' => true,
            ]);
        }

        return $saved;
    }

    /**
     * @param  list<array<string, mixed>>  $rows
     * @param  array<string, Category>  $categories
     */
    private function seedProducts(array $rows, array $categories): void
    {
        foreach ($rows as $row) {
            $addedAt = now()->subDays($row['added_days_ago']);

            $product = Product::create([
                'category_id' => $categories[$row['category']]->id,
                'name' => $row['name'],
                'slug' => $row['slug'],
                'brand' => $row['brand'],
                'variant_label' => $row['variant_label'],
                'short_description' => $row['short_description'],
                'description' => $row['description'],
                'highlights' => $row['highlights'],
                'is_active' => true,
                'is_featured' => $row['is_featured'],
                'created_at' => $addedAt,
            ]);

            foreach ($row['variants'] as $variantRow) {
                $this->seedVariant($product, $variantRow, $addedAt);
            }
        }
    }

    /**
     * @param  array<string, mixed>  $row
     */
    private function seedVariant(Product $product, array $row, Carbon $addedAt): void
    {
        $variant = $product->variants()->create([
            'sku' => $row['sku'],
            'name' => $row['name'],
            'unit' => $row['unit'],
            'mrp_paise' => $row['mrp_paise'],
            'price_paise' => $row['price_paise'],
            'stock_quantity' => $row['stock_quantity'],
            'low_stock_threshold' => 5,
            'swatch_hex' => $row['swatch_hex'],
            'is_active' => true,
            'sort_order' => $row['sort_order'],
        ]);

        InventoryMovement::create([
            'product_variant_id' => $variant->id,
            'type' => InventoryMovementType::Restock,
            'quantity_change' => $variant->stock_quantity,
            'stock_after' => $variant->stock_quantity,
            'note' => 'Opening stock',
            'created_at' => $addedAt,
        ]);

        $this->seedSlabs($variant, $row['slabs']);
    }

    /**
     * Wholesale price bands (ADR-019).
     *
     * @param  list<array{min: int, paise: int}>  $slabs
     */
    private function seedSlabs(ProductVariant $variant, array $slabs): void
    {
        foreach ($slabs as $slab) {
            PriceSlab::create([
                'product_variant_id' => $variant->id,
                'min_quantity' => $slab['min'],
                'unit_price_paise' => $slab['paise'],
                'is_active' => true,
            ]);
        }
    }
}
