<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $order_package_id
 * @property int $order_item_id
 * @property int $quantity
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 */
class OrderPackageItem extends Model
{
    /** @use HasFactory<\Database\Factories\OrderPackageItemFactory> */
    use HasFactory;

    /** @var list<string> */
    protected $fillable = [
        'order_package_id',
        'order_item_id',
        'quantity',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'quantity' => 'integer',
        ];
    }

    /**
     * @return BelongsTo<OrderPackage, $this>
     */
    public function package(): BelongsTo
    {
        return $this->belongsTo(OrderPackage::class, 'order_package_id');
    }

    /**
     * @return BelongsTo<OrderItem, $this>
     */
    public function item(): BelongsTo
    {
        return $this->belongsTo(OrderItem::class, 'order_item_id');
    }
}
