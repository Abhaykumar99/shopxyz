<?php

namespace App\Models;

use App\Support\IndianPhone;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * A customer's delivery address. Copied onto the order when it is placed, so a
 * later edit never changes where a past order went.
 *
 * @property int $id
 * @property int $user_id
 * @property string $label
 * @property string $recipient_name
 * @property string $phone
 * @property string $line1
 * @property string|null $line2
 * @property string|null $landmark
 * @property string $city
 * @property string $state
 * @property string $pincode
 * @property bool $is_default
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property \Illuminate\Support\Carbon|null $deleted_at
 */
class Address extends Model
{
    /** @use HasFactory<\Database\Factories\AddressFactory> */
    use HasFactory;

    use SoftDeletes;

    /** @var list<string> */
    protected $fillable = [
        'user_id',
        'label',
        'recipient_name',
        'phone',
        'line1',
        'line2',
        'landmark',
        'city',
        'state',
        'pincode',
        'is_default',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_default' => 'boolean',
        ];
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Address lines for display, without the pincode.
     *
     * @return list<string>
     */
    public function lines(): array
    {
        return array_values(array_filter([
            $this->line1,
            $this->line2,
            $this->landmark ? "Near {$this->landmark}" : null,
            "{$this->city}, {$this->state}",
        ]));
    }

    public function formattedPhone(): string
    {
        return IndianPhone::format($this->phone);
    }

    /**
     * The address as it is copied onto an order.
     *
     * @return array<string, string|null>
     */
    public function snapshot(): array
    {
        return [
            'ship_name' => $this->recipient_name,
            'ship_phone' => $this->phone,
            'ship_line1' => $this->line1,
            'ship_line2' => $this->line2,
            'ship_landmark' => $this->landmark,
            'ship_city' => $this->city,
            'ship_state' => $this->state,
            'ship_pincode' => $this->pincode,
        ];
    }
}
