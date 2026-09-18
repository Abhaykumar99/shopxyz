<?php

namespace App\Models;

use App\Enums\HomeSectionType;
use App\Enums\ProductRailSource;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * One block of the homepage, in the order the admin arranged (ADR-024).
 *
 * @property int $id
 * @property string $key
 * @property HomeSectionType $type
 * @property string|null $title
 * @property string|null $subtitle
 * @property string|null $link_label
 * @property string|null $link_url
 * @property array<string, mixed>|null $settings
 * @property int $sort_order
 * @property bool $is_active
 * @property \Illuminate\Support\Carbon|null $starts_at
 * @property \Illuminate\Support\Carbon|null $ends_at
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 */
class HomeSection extends Model
{
    /** @use HasFactory<\Database\Factories\HomeSectionFactory> */
    use HasFactory;

    /** @var list<string> */
    protected $fillable = [
        'key', 'type', 'title', 'subtitle', 'link_label', 'link_url',
        'settings', 'sort_order', 'is_active', 'starts_at', 'ends_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'type' => HomeSectionType::class,
            'settings' => 'array',
            'sort_order' => 'integer',
            'is_active' => 'boolean',
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
        ];
    }

    /**
     * @return HasMany<HomeSectionItem, $this>
     */
    public function items(): HasMany
    {
        return $this->hasMany(HomeSectionItem::class)->orderBy('sort_order');
    }

    /**
     * @param  Builder<$this>  $query
     * @return Builder<$this>
     */
    public function scopeLive(Builder $query): Builder
    {
        return $query->where('is_active', true)
            ->where(fn (Builder $query): Builder => $query->whereNull('starts_at')->orWhere('starts_at', '<=', now()))
            ->where(fn (Builder $query): Builder => $query->whereNull('ends_at')->orWhere('ends_at', '>=', now()));
    }

    public function source(): ?ProductRailSource
    {
        $source = $this->settings['source'] ?? null;

        return is_string($source) ? ProductRailSource::tryFrom($source) : null;
    }

    public function limit(): int
    {
        return max(2, min(12, (int) ($this->settings['limit'] ?? 8)));
    }

    public function categorySlug(): ?string
    {
        $slug = $this->settings['category'] ?? null;

        return is_string($slug) && $slug !== '' ? $slug : null;
    }

    public function showsAsRail(): bool
    {
        return (bool) ($this->settings['rail'] ?? true);
    }

    public function liveState(): string
    {
        return match (true) {
            ! $this->is_active => 'Switched off',
            $this->starts_at !== null && $this->starts_at->isFuture() => 'Scheduled',
            $this->ends_at !== null && $this->ends_at->isPast() => 'Finished',
            default => 'Live',
        };
    }
}
