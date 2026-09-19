<?php

namespace App\Models;

use App\Enums\BannerPlacement;
use App\Support\Home\HomeContent;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * A homepage banner: a desktop hero slide, the phone hero or a promotion card
 * (ADR-024). Nothing on the homepage is hardcoded; it is all rows in here.
 *
 * @property int $id
 * @property BannerPlacement $placement
 * @property string|null $eyebrow
 * @property string $title
 * @property string|null $subtitle
 * @property string|null $body
 * @property string|null $cta_label
 * @property string|null $cta_url
 * @property string|null $secondary_cta_label
 * @property string|null $secondary_cta_url
 * @property string|null $image_path
 * @property string|null $image_alt
 * @property string $theme
 * @property int $sort_order
 * @property bool $is_active
 * @property \Illuminate\Support\Carbon|null $starts_at
 * @property \Illuminate\Support\Carbon|null $ends_at
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 */
class Banner extends Model
{
    /** @use HasFactory<\Database\Factories\BannerFactory> */
    use HasFactory;

    /** @var list<string> */
    protected $fillable = [
        'placement', 'eyebrow', 'title', 'subtitle', 'body', 'cta_label', 'cta_url',
        'secondary_cta_label', 'secondary_cta_url', 'image_path', 'image_alt', 'theme',
        'sort_order', 'is_active', 'starts_at', 'ends_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'placement' => BannerPlacement::class,
            'sort_order' => 'integer',
            'is_active' => 'boolean',
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
        ];
    }

    /**
     * Switched on, and inside its dates if it has any.
     *
     * @param  Builder<$this>  $query
     * @return Builder<$this>
     */
    public function scopeLive(Builder $query): Builder
    {
        return $query->where('is_active', true)
            ->where(fn (Builder $query): Builder => $query->whereNull('starts_at')->orWhere('starts_at', '<=', now()))
            ->where(fn (Builder $query): Builder => $query->whereNull('ends_at')->orWhere('ends_at', '>=', now()));
    }

    /**
     * @param  Builder<$this>  $query
     * @return Builder<$this>
     */
    public function scopePlacement(Builder $query, BannerPlacement $placement): Builder
    {
        return $query->where('placement', $placement);
    }

    public function isLive(): bool
    {
        return $this->is_active
            && ($this->starts_at === null || $this->starts_at->isPast())
            && ($this->ends_at === null || $this->ends_at->isFuture());
    }

    /**
     * Why a banner is not on the site right now, for the admin list.
     */
    public function liveState(): string
    {
        return match (true) {
            ! $this->is_active => 'Switched off',
            $this->starts_at !== null && $this->starts_at->isFuture() => 'Scheduled',
            $this->ends_at !== null && $this->ends_at->isPast() => 'Finished',
            default => 'Live',
        };
    }

    protected static function booted(): void
    {
        // HomeContent is resolved once per request and memoises what it
        // reads, so editing the homepage has to drop that instance — in a
        // test, under Octane, or anywhere else the container outlives one
        // request.
        static::saved(static function (): void {
            app()->forgetInstance(HomeContent::class);
        });

        static::deleted(static function (): void {
            app()->forgetInstance(HomeContent::class);
        });
    }
}
