<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * One packed box of an order, with the pickup code printed on its label (ADR-021).
 *
 * @property int $id
 * @property int $order_id
 * @property string $package_id
 * @property int $sequence
 * @property string $pickup_code
 * @property \Illuminate\Support\Carbon|null $picked_up_at
 * @property int|null $picked_up_by
 * @property int|null $weight_grams
 * @property \Illuminate\Support\Carbon|null $returned_at
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 */
class OrderPackage extends Model
{
    /** @use HasFactory<\Database\Factories\OrderPackageFactory> */
    use HasFactory;

    /** @var list<string> */
    protected $fillable = [
        'order_id',
        'package_id',
        'sequence',
        'pickup_code',
        'picked_up_at',
        'picked_up_by',
        'weight_grams',
        'returned_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'sequence' => 'integer',
            'picked_up_at' => 'datetime',
            'returned_at' => 'datetime',
            'weight_grams' => 'integer',
            'pickup_code' => 'encrypted',
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
     * @return HasMany<OrderPackageItem, $this>
     */
    public function items(): HasMany
    {
        return $this->hasMany(OrderPackageItem::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function pickedUpBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'picked_up_by');
    }

    public function isPickedUp(): bool
    {
        return $this->picked_up_at !== null;
    }

    public function label(): string
    {
        return "Box {$this->sequence} of ".$this->order->packages()->count();
    }

    /**
     * The pickup code is printed on the box label, so it has to stay readable to
     * be reprinted: it is stored encrypted, never shown in the delivery panel,
     * and compared in constant time (ADR-021).
     */
    public function pickupCodeMatches(string $code): bool
    {
        return hash_equals((string) $this->pickup_code, $code);
    }
}
