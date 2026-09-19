<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Admin-editable system settings. Read only through `App\Support\ShopSettings`,
 * which falls back to `config/shop.php` (ADR-013).
 *
 * @property int $id
 * @property string $key
 * @property mixed $value
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 */
class Setting extends Model
{
    /** @use HasFactory<\Database\Factories\SettingFactory> */
    use HasFactory;

    /** @var list<string> */
    protected $fillable = [
        'key',
        'value',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'value' => 'array',
        ];
    }

    /**
     * Every setting as a flat map, for `App\Support\ShopSettings` (ADR-013).
     *
     * @return array<string, mixed>
     */
    public static function map(): array
    {
        return self::query()->pluck('value', 'key')->all();
    }
}
