<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

/**
 * A product photo, optionally tied to one variant (a shade's own picture).
 *
 * @property int $id
 * @property int $product_id
 * @property int|null $product_variant_id
 * @property string $path
 * @property string|null $thumbnail_path
 * @property string|null $alt
 * @property int $sort_order
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 */
class ProductImage extends Model
{
    /** @use HasFactory<\Database\Factories\ProductImageFactory> */
    use HasFactory;

    /** @var list<string> */
    protected $fillable = [
        'product_id',
        'product_variant_id',
        'path',
        'thumbnail_path',
        'alt',
        'sort_order',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'sort_order' => 'integer',
        ];
    }

    /**
     * @return BelongsTo<Product, $this>
     */
    /**
     * A browsable URL for the full image.
     */
    public function url(): string
    {
        return Storage::disk('public')->url($this->path);
    }

    /**
     * The card-sized copy, falling back to the full one for a photo stored
     * before the two sizes existed.
     */
    public function thumbnailUrl(): string
    {
        return Storage::disk('public')->url($this->thumbnail_path ?? $this->path);
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
}
