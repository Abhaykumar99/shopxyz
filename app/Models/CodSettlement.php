<?php

namespace App\Models;

use App\Enums\CashSettlementStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * One handover of COD cash from a delivery partner to the shop (ADR-022).
 *
 * @property CashSettlementStatus $status
 * @property int $amount_paise
 * @property int|null $counted_paise
 * @property \Carbon\CarbonImmutable $handed_over_at
 */
class CodSettlement extends Model
{
    /** @use HasFactory<\Database\Factories\CodSettlementFactory> */
    use HasFactory;

    /** @var list<string> */
    protected $fillable = [
        'reference',
        'user_id',
        'amount_paise',
        'status',
        'handed_over_at',
        'verified_by',
        'verified_at',
        'counted_paise',
        'note',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => CashSettlementStatus::class,
            'amount_paise' => 'integer',
            'counted_paise' => 'integer',
            'handed_over_at' => 'datetime',
            'verified_at' => 'datetime',
        ];
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
    public function verifiedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'verified_by');
    }

    /**
     * @return HasMany<DeliveryAssignment, $this>
     */
    public function assignments(): HasMany
    {
        return $this->hasMany(DeliveryAssignment::class);
    }

    public function orderCount(): int
    {
        return $this->assignments()->count();
    }

    /**
     * What the shop counted against what the delivery partner handed in.
     */
    public function differencePaise(): int
    {
        return $this->counted_paise === null ? 0 : $this->counted_paise - $this->amount_paise;
    }
}
