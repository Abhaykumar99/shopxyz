<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

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
    public const CACHE_KEY = 'shop.settings.map';

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
     * Read on every request and changed only when the admin saves the settings
     * page, so it is cached and cleared on write rather than queried each time.
     *
     * @return array<string, mixed>
     */
    public static function map(): array
    {
        return Cache::rememberForever(self::CACHE_KEY, fn (): array => self::query()->pluck('value', 'key')->all());
    }

    public static function forgetCache(): void
    {
        Cache::forget(self::CACHE_KEY);
    }

    protected static function booted(): void
    {
        // Covers the settings page, a seeder and anyone at a tinker prompt.
        static::saved(static function (): void {
            self::forgetCache();
        });

        static::deleted(static function (): void {
            self::forgetCache();
        });
    }
}
