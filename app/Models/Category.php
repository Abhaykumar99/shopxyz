<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * A shop category, optionally nested one level (Cosmetics > Lips).
 *
 * @property int $id
 * @property int|null $parent_id
 * @property string $name
 * @property string $slug
 * @property string|null $description
 * @property string|null $image_path
 * @property int $sort_order
 * @property bool $is_active
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 */
class Category extends Model
{
    /** @use HasFactory<\Database\Factories\CategoryFactory> */
    use HasFactory;

    /** @var list<string> */
    protected $fillable = [
        'parent_id',
        'name',
        'slug',
        'description',
        'image_path',
        'sort_order',
        'is_active',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    /**
     * @return BelongsTo<Category, $this>
     */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(Category::class, 'parent_id');
    }

    /**
     * @return HasMany<Category, $this>
     */
    public function children(): HasMany
    {
        return $this->hasMany(Category::class, 'parent_id');
    }

    /**
     * @return HasMany<Product, $this>
     */
    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }

    /**
     * @param  Builder<$this>  $query
     * @return Builder<$this>
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    /**
     * @param  Builder<$this>  $query
     * @return Builder<$this>
     */
    public function scopeRoots(Builder $query): Builder
    {
        return $query->whereNull('parent_id');
    }

    /**
     * The top-level category this one sits under, or itself when it is already
     * top level. The shop tints product images by the root category.
     */
    public function rootSlug(): string
    {
        if ($this->parent_id === null) {
            return $this->slug;
        }

        return $this->parent->slug;
    }

    /**
     * This category and its children. A parent category lists everything in its
     * subcategories too, which is what a customer expects from "Cosmetics".
     *
     * @return list<int>
     */
    public function selfAndDescendantIds(): array
    {
        return [
            $this->getKey(),
            ...$this->children()->pluck('id')->all(),
        ];
    }

    /**
     * How many products a customer would see in this category, counting its
     * subcategories.
     */
    public function productCount(): int
    {
        return Product::query()
            ->active()
            ->whereIn('category_id', $this->selfAndDescendantIds())
            ->count();
    }
}
