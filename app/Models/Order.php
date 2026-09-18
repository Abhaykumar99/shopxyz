<?php

namespace App\Models;

use App\Enums\OrderStatus;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * A placed order. Product names, prices and the delivery address are copied in
 * so the order always shows what the customer actually bought.
 *
 * @property int $id
 * @property string $order_number
 * @property int $user_id
 * @property OrderStatus $status
 * @property PaymentMethod $payment_method
 * @property PaymentStatus $payment_status
 * @property string $ship_name
 * @property string $ship_phone
 * @property string $ship_line1
 * @property string|null $ship_line2
 * @property string|null $ship_landmark
 * @property string $ship_city
 * @property string $ship_state
 * @property string $ship_pincode
 * @property int $subtotal_paise
 * @property int $discount_paise
 * @property int $delivery_charge_paise
 * @property int $total_paise
 * @property bool $has_wholesale_items
 * @property string|null $customer_note
 * @property string|null $cancel_reason
 * @property \Illuminate\Support\Carbon $placed_at
 * @property \Illuminate\Support\Carbon|null $confirmed_at
 * @property \Illuminate\Support\Carbon|null $packed_at
 * @property \Illuminate\Support\Carbon|null $delivered_at
 * @property \Illuminate\Support\Carbon|null $cancelled_at
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 */
class Order extends Model
{
    /** @use HasFactory<\Database\Factories\OrderFactory> */
    use HasFactory;

    /** @var list<string> */
    protected $fillable = [
        'order_number',
        'user_id',
        'status',
        'payment_method',
        'payment_status',
        'ship_name',
        'ship_phone',
        'ship_line1',
        'ship_line2',
        'ship_landmark',
        'ship_city',
        'ship_state',
        'ship_pincode',
        'subtotal_paise',
        'discount_paise',
        'delivery_charge_paise',
        'total_paise',
        'has_wholesale_items',
        'customer_note',
        'cancel_reason',
        'placed_at',
        'confirmed_at',
        'packed_at',
        'delivered_at',
        'cancelled_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => OrderStatus::class,
            'payment_method' => PaymentMethod::class,
            'payment_status' => PaymentStatus::class,
            'subtotal_paise' => 'integer',
            'discount_paise' => 'integer',
            'delivery_charge_paise' => 'integer',
            'total_paise' => 'integer',
            'has_wholesale_items' => 'boolean',
            'placed_at' => 'datetime',
            'confirmed_at' => 'datetime',
            'packed_at' => 'datetime',
            'delivered_at' => 'datetime',
            'cancelled_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function customer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * @return HasMany<OrderItem, $this>
     */
    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    /**
     * @return HasMany<OrderPackage, $this>
     */
    public function packages(): HasMany
    {
        return $this->hasMany(OrderPackage::class);
    }

    /**
     * @return HasMany<OrderStatusHistory, $this>
     */
    public function statusHistories(): HasMany
    {
        return $this->hasMany(OrderStatusHistory::class);
    }

    /**
     * @return HasMany<Payment, $this>
     */
    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    /**
     * @return HasOne<Invoice, $this>
     */
    public function invoice(): HasOne
    {
        return $this->hasOne(Invoice::class);
    }

    /**
     * @return HasMany<DeliveryAssignment, $this>
     */
    public function assignments(): HasMany
    {
        return $this->hasMany(DeliveryAssignment::class);
    }

    /**
     * The delivery partner currently carrying this order, if any.
     *
     * @return HasOne<DeliveryAssignment, $this>
     */
    public function activeAssignment(): HasOne
    {
        return $this->hasOne(DeliveryAssignment::class)->where('is_active', true);
    }

    public function itemCount(): int
    {
        return (int) $this->items()->sum('quantity');
    }

    public function isCod(): bool
    {
        return $this->payment_method === PaymentMethod::Cod;
    }

    /**
     * Cash the delivery partner has to collect at the door.
     */
    public function codAmountPaise(): int
    {
        return $this->isCod() && $this->payment_status !== PaymentStatus::CodCollected ? $this->total_paise : 0;
    }
}
