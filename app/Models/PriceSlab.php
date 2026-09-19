<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A wholesale price band: from `min_quantity` units, each unit costs
 * `unit_price_paise` (ADR-019). The smallest band is the minimum order.
 *
 * @property int $id
 * @property int $product_variant_id
 * @property int $min_quantity
 * @property int $unit_price_paise
 * @property bool $is_active
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 */
class PriceSlab extends Model
{
    /** @use HasFactory<\Database\Factories\PriceSlabFactory> */
    use HasFactory;

    /** @var list<string> */
    protected $fillable = [
        'product_variant_id',
        'min_quantity',
        'unit_price_paise',
        'is_active',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'min_quantity' => 'integer',
            'unit_price_paise' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    /**
     * @return BelongsTo<ProductVariant, $this>
     */
    public function variant(): BelongsTo
    {
        return $this->belongsTo(ProductVariant::class, 'product_variant_id');
    }
}
