<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property int $order_id
 * @property int|null $product_id
 * @property int|null $product_variant_id
 * @property string $product_name
 * @property string $variant_name
 * @property string $sku
 * @property int $mrp_paise
 * @property int $unit_price_paise
 * @property int $quantity
 * @property int $line_total_paise
 * @property bool $is_wholesale
 * @property int|null $tax_rate_bp
 * @property string|null $hsn_code
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 */
class OrderItem extends Model
{
    /** @use HasFactory<\Database\Factories\OrderItemFactory> */
    use HasFactory;

    /** @var list<string> */
    protected $fillable = [
        'order_id',
        'product_id',
        'product_variant_id',
        'product_name',
        'variant_name',
        'sku',
        'mrp_paise',
        'unit_price_paise',
        'quantity',
        'line_total_paise',
        'is_wholesale',
        'tax_rate_bp',
        'hsn_code',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'mrp_paise' => 'integer',
            'unit_price_paise' => 'integer',
            'quantity' => 'integer',
            'line_total_paise' => 'integer',
            'is_wholesale' => 'boolean',
            'tax_rate_bp' => 'integer',
        ];
    }

    /**
     * @return BelongsTo<Order, $this>
     */
    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    /**
     * @return BelongsTo<Product, $this>
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * @return BelongsTo<ProductVariant, $this>
     */
    public function variant(): BelongsTo
    {
        return $this->belongsTo(ProductVariant::class, 'product_variant_id');
    }

    /**
     * @return HasMany<OrderPackageItem, $this>
     */
    public function packageItems(): HasMany
    {
        return $this->hasMany(OrderPackageItem::class);
    }
}
