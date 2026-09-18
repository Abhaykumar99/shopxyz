<?php

namespace App\Models;

use App\Support\Money;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * One buyable option of a product: a shade, a weight or simply "Standard".
 * Money is integer paise (ADR-005); wholesale slabs live in `price_slabs` (ADR-019).
 *
 * @property int $id
 * @property int $product_id
 * @property string $sku
 * @property string $name
 * @property int|null $mrp_paise
 * @property int $price_paise
 * @property int $stock_quantity
 * @property int $low_stock_threshold
 * @property int|null $weight_grams
 * @property string|null $swatch_hex
 * @property bool $is_active
 * @property int $sort_order
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 */
class ProductVariant extends Model
{
    /** @use HasFactory<\Database\Factories\ProductVariantFactory> */
    use HasFactory;

    /** @var list<string> */
    protected $fillable = [
        'product_id',
        'sku',
        'name',
        'mrp_paise',
        'price_paise',
        'stock_quantity',
        'low_stock_threshold',
        'weight_grams',
        'swatch_hex',
        'is_active',
        'sort_order',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'mrp_paise' => 'integer',
            'price_paise' => 'integer',
            'stock_quantity' => 'integer',
            'low_stock_threshold' => 'integer',
            'weight_grams' => 'integer',
            'is_active' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    /**
     * @return BelongsTo<Product, $this>
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * @return HasMany<PriceSlab, $this>
     */
    public function priceSlabs(): HasMany
    {
        return $this->hasMany(PriceSlab::class);
    }

    /**
     * @return HasMany<InventoryMovement, $this>
     */
    public function inventoryMovements(): HasMany
    {
        return $this->hasMany(InventoryMovement::class);
    }

    public function inStock(): bool
    {
        return $this->stock_quantity > 0;
    }

    public function isLowStock(): bool
    {
        return $this->stock_quantity > 0 && $this->stock_quantity <= $this->low_stock_threshold;
    }

    public function discountPercent(): int
    {
        return $this->mrp_paise ? Money::discountPercent($this->mrp_paise, $this->price_paise) : 0;
    }

    /**
     * The smallest wholesale quantity, or null when this variant is retail only (ADR-019).
     */
    public function minimumWholesaleQuantity(): ?int
    {
        $slab = $this->priceSlabs()->where('is_active', true)->orderBy('min_quantity')->first();

        return $slab?->min_quantity;
    }

    /**
     * Unit price for a quantity: the matching slab at or above the minimum, retail below it.
     */
    public function unitPriceFor(int $quantity): int
    {
        $slab = $this->priceSlabs()
            ->where('is_active', true)
            ->where('min_quantity', '<=', $quantity)
            ->orderByDesc('min_quantity')
            ->first();

        return $slab->unit_price_paise ?? $this->price_paise;
    }
}
