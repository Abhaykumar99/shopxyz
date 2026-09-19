<?php

namespace App\Models;

use App\Enums\DeliveryFailureReason;
use App\Enums\DeliveryStep;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * One order in the hands of one delivery partner, from assignment to delivery
 * or failure (ADR-020, ADR-021). Only the milestones write to the order status.
 *
 * @property int $id
 * @property int $order_id
 * @property int $user_id
 * @property int|null $assigned_by
 * @property DeliveryStep $step
 * @property bool $is_active
 * @property \Illuminate\Support\Carbon $assigned_at
 * @property \Illuminate\Support\Carbon|null $accepted_at
 * @property \Illuminate\Support\Carbon|null $picked_up_at
 * @property \Illuminate\Support\Carbon|null $out_for_delivery_at
 * @property \Illuminate\Support\Carbon|null $delivered_at
 * @property \Illuminate\Support\Carbon|null $failed_at
 * @property DeliveryFailureReason|null $failure_reason
 * @property string|null $failure_note
 * @property string $otp
 * @property int $otp_attempts
 * @property int $pickup_attempts
 * @property int $cash_collected_paise
 * @property int|null $cod_settlement_id
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 */
class DeliveryAssignment extends Model
{
    /** @use HasFactory<\Database\Factories\DeliveryAssignmentFactory> */
    use HasFactory;

    /** @var list<string> */
    protected $fillable = [
        'order_id',
        'user_id',
        'assigned_by',
        'step',
        'is_active',
        'assigned_at',
        'accepted_at',
        'picked_up_at',
        'out_for_delivery_at',
        'delivered_at',
        'failed_at',
        'failure_reason',
        'failure_note',
        'otp',
        'otp_attempts',
        'pickup_attempts',
        'cash_collected_paise',
        'cod_settlement_id',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'step' => DeliveryStep::class,
            'failure_reason' => DeliveryFailureReason::class,
            'is_active' => 'boolean',
            'assigned_at' => 'datetime',
            'accepted_at' => 'datetime',
            'picked_up_at' => 'datetime',
            'out_for_delivery_at' => 'datetime',
            'delivered_at' => 'datetime',
            'failed_at' => 'datetime',
            'otp_attempts' => 'integer',
            'pickup_attempts' => 'integer',
            'cash_collected_paise' => 'integer',
            'otp' => 'encrypted',
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
     * @return BelongsTo<User, $this>
     */
    public function deliveryPartner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function assignedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_by');
    }

    /**
     * @return BelongsTo<CodSettlement, $this>
     */
    public function settlement(): BelongsTo
    {
        return $this->belongsTo(CodSettlement::class, 'cod_settlement_id');
    }

    /**
     * The customer reads this OTP on their order page, so it is stored encrypted
     * rather than hashed, and compared in constant time (ADR-021).
     */
    public function otpMatches(string $otp): bool
    {
        return hash_equals((string) $this->otp, $otp);
    }

    /**
     * Cash collected but not yet handed to the shop.
     */
    public function cashToHandOverPaise(): int
    {
        return $this->cod_settlement_id === null ? $this->cash_collected_paise : 0;
    }
}
