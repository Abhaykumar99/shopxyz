<?php

namespace App\Models;

use App\Enums\WholesaleEnquiryStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * An optional wholesale quote request (ADR-019). Ordinary bulk orders go through
 * the shop; this is for custom pricing, packing or branding.
 *
 * @property int $id
 * @property string $reference
 * @property int|null $user_id
 * @property string $business_name
 * @property string $contact_name
 * @property string $phone
 * @property string|null $email
 * @property string|null $gstin
 * @property string $business_type
 * @property string $city
 * @property string $pincode
 * @property \Illuminate\Support\Carbon|null $needed_by
 * @property string|null $message
 * @property WholesaleEnquiryStatus $status
 * @property int|null $handled_by
 * @property array<int, array<string, mixed>>|null $items
 * @property int|null $estimate_paise
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 */
class WholesaleEnquiry extends Model
{
    /** @use HasFactory<\Database\Factories\WholesaleEnquiryFactory> */
    use HasFactory;

    /** @var list<string> */
    protected $fillable = [
        'reference',
        'user_id',
        'business_name',
        'contact_name',
        'phone',
        'email',
        'gstin',
        'business_type',
        'city',
        'pincode',
        'needed_by',
        'message',
        'status',
        'handled_by',
        'items',
        'estimate_paise',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => WholesaleEnquiryStatus::class,
            'needed_by' => 'date',
            'items' => 'array',
            'estimate_paise' => 'integer',
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
     * @return BelongsTo<User, $this>
     */
    public function handledBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'handled_by');
    }
}
