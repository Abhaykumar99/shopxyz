<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A issued invoice. Never edited once issued; a correction is a new document.
 *
 * @property int $id
 * @property int $order_id
 * @property string $invoice_number
 * @property \Illuminate\Support\Carbon $issued_at
 * @property string|null $pdf_path
 * @property int $subtotal_paise
 * @property int $discount_paise
 * @property int $delivery_charge_paise
 * @property int $total_paise
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 */
class Invoice extends Model
{
    /** @use HasFactory<\Database\Factories\InvoiceFactory> */
    use HasFactory;

    /** @var list<string> */
    protected $fillable = [
        'order_id',
        'invoice_number',
        'issued_at',
        'pdf_path',
        'subtotal_paise',
        'discount_paise',
        'delivery_charge_paise',
        'total_paise',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'issued_at' => 'datetime',
            'subtotal_paise' => 'integer',
            'discount_paise' => 'integer',
            'delivery_charge_paise' => 'integer',
            'total_paise' => 'integer',
        ];
    }

    /**
     * @return BelongsTo<Order, $this>
     */
    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }
}
