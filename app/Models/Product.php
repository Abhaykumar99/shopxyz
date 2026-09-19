<?php

namespace App\Models;

use App\Enums\OrderStatus;
use App\Enums\ProductSort;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * A product. Every product has at least one variant, so simple and multi-variant
 * products are handled the same way (docs/erd.md).
 *
 * @property int $id
 * @property int $category_id
 * @property string $name
 * @property string $slug
 * @property string|null $brand
 * @property string $variant_label
 * @property list<string>|null $highlights
 * @property string|null $short_description
 * @property string|null $description
 * @property bool $is_active
 * @property bool $is_featured
 * @property string|null $hsn_code
 * @property int|null $tax_rate_bp
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property \Illuminate\Support\Carbon|null $deleted_at
 */
class Product extends Model
{
    /** How far back "most popular" looks when it counts sales. */
    public const POPULAR_WINDOW_DAYS = 90;

    /** @use HasFactory<\Database\Factories\ProductFactory> */
    use HasFactory;

    use SoftDeletes;

    /** @var list<string> */
    protected $fillable = [
        'category_id',
        'name',
        'slug',
        'brand',
        'variant_label',
        'highlights',
        'short_description',
        'description',
        'is_active',
        'is_featured',
        'hsn_code',
        'tax_rate_bp',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'is_featured' => 'boolean',
            'highlights' => 'array',
            'tax_rate_bp' => 'integer',
        ];
    }

    /**
     * @return BelongsTo<Category, $this>
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    /**
     * @return HasMany<ProductVariant, $this>
     */
    public function variants(): HasMany
    {
        return $this->hasMany(ProductVariant::class);
    }

    /**
     * @return HasMany<ProductImage, $this>
     */
    public function images(): HasMany
    {
        return $this->hasMany(ProductImage::class);
    }

    /**
     * The variant a customer sees first.
     *
     * @return HasOne<ProductVariant, $this>
     */
    public function defaultVariant(): HasOne
    {
        return $this->hasOne(ProductVariant::class)->orderBy('sort_order')->orderBy('id');
    }

    /**
     * Reads the loaded relation rather than counting in the database, because a
     * grid renders this once per card. List queries load `variants_count`.
     */
    public function hasChoices(): bool
    {
        return $this->variantCount() > 1;
    }

    /**
     * The variant a card and the product page start on. Prefers the loaded
     * relation so a grid does not query once per product.
     */
    public function firstVariant(): ?ProductVariant
    {
        if ($this->relationLoaded('variants')) {
            return $this->variants->first();
        }

        return $this->defaultVariant;
    }

    /**
     * Whether the options are colours, which the product page shows as swatches
     * rather than a list of names.
     */
    public function hasSwatches(): bool
    {
        return $this->variants->contains(fn (ProductVariant $variant): bool => filled($variant->swatch_hex));
    }

    public function variantCount(): int
    {
        if ($this->relationLoaded('variants')) {
            return $this->variants->count();
        }

        return (int) ($this->variants_count ?? $this->variants()->count());
    }

    public function inStock(): bool
    {
        if ($this->relationLoaded('variants')) {
            return $this->variants->contains(fn (ProductVariant $variant): bool => $variant->inStock());
        }

        return (int) $this->variants()->sum('stock_quantity') > 0;
    }

    /**
     * The shop tints a product's placeholder image by its top-level category.
     */
    public function rootCategorySlug(): ?string
    {
        return $this->category?->rootSlug();
    }

    /*
    |--------------------------------------------------------------------------
    | Catalogue query
    |--------------------------------------------------------------------------
    |
    | One place builds every product list the shop shows: the category pages,
    | search, the homepage rails and wholesale. Filtering and sorting stay in
    | SQL so a page never loads the whole catalogue in order to slice it.
    |
    */

    /**
     * @param  Builder<$this>  $query
     * @return Builder<$this>
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    /**
     * Everything a product card needs, without a query per row.
     *
     * @param  Builder<$this>  $query
     * @return Builder<$this>
     */
    public function scopeForListing(Builder $query): Builder
    {
        return $query
            ->with([
                'category.parent',
                'variants' => fn ($variants) => $variants->where('is_active', true)->orderBy('sort_order')->orderBy('id'),
            ])
            ->withCount('variants');
    }

    /**
     * A category lists its own products and its subcategories', which is what a
     * customer expects from "Cosmetics".
     *
     * @param  Builder<$this>  $query
     * @return Builder<$this>
     */
    public function scopeInCategory(Builder $query, ?Category $category): Builder
    {
        if ($category === null) {
            return $query;
        }

        return $query->whereIn('category_id', $category->selfAndDescendantIds());
    }

    /**
     * Substring matching, term by term, across the product and its category —
     * the behaviour the shop has always had. The `fullText` index on
     * (name, brand) is deliberately not used: MySQL and MariaDB match whole
     * words there, so "lip" would stop finding "Velvet matte lipstick".
     *
     * @param  Builder<$this>  $query
     * @return Builder<$this>
     */
    public function scopeSearch(Builder $query, ?string $search): Builder
    {
        $terms = array_filter(explode(' ', Str::lower(trim((string) $search))));

        foreach ($terms as $term) {
            $like = '%'.addcslashes($term, '%_').'%';

            $query->where(function (Builder $query) use ($like): void {
                $query->where('name', 'like', $like)
                    ->orWhere('brand', 'like', $like)
                    ->orWhere('short_description', 'like', $like)
                    ->orWhereHas('category', fn (Builder $category) => $category->where('name', 'like', $like));
            });
        }

        return $query;
    }

    /**
     * @param  Builder<$this>  $query
     * @param  list<string>  $brands
     * @return Builder<$this>
     */
    public function scopeOfBrands(Builder $query, array $brands): Builder
    {
        return $brands === [] ? $query : $query->whereIn('brand', $brands);
    }

    /**
     * Filters on the product's cheapest active variant, which is the price the
     * card shows. Written as a grouped subquery so the aggregate sits in a
     * HAVING that both MySQL and MariaDB accept.
     *
     * @param  Builder<$this>  $query
     * @return Builder<$this>
     */
    public function scopePricedBetween(Builder $query, ?int $minPaise, ?int $maxPaise): Builder
    {
        if ($minPaise === null && $maxPaise === null) {
            return $query;
        }

        $cheapest = DB::table('product_variants')
            ->select('product_id')
            ->where('is_active', true)
            ->groupBy('product_id');

        if ($minPaise !== null) {
            $cheapest->havingRaw('min(price_paise) >= ?', [$minPaise]);
        }

        if ($maxPaise !== null) {
            $cheapest->havingRaw('min(price_paise) <= ?', [$maxPaise]);
        }

        return $query->whereIn('id', $cheapest);
    }

    /**
     * @param  Builder<$this>  $query
     * @return Builder<$this>
     */
    public function scopeInStock(Builder $query): Builder
    {
        return $query->whereHas('variants', fn (Builder $variants) => $variants
            ->where('is_active', true)
            ->where('stock_quantity', '>', 0));
    }

    /**
     * @param  Builder<$this>  $query
     * @return Builder<$this>
     */
    public function scopeSorted(Builder $query, ProductSort $sort): Builder
    {
        return match ($sort) {
            ProductSort::Newest => $query->orderByDesc('created_at')->orderByDesc('id'),
            ProductSort::PriceAsc => $query->withCheapestPrice()->orderBy('cheapest_paise')->orderBy('name'),
            ProductSort::PriceDesc => $query->withCheapestPrice()->orderByDesc('cheapest_paise')->orderBy('name'),
            ProductSort::Discount => $query->withBestDiscount()->orderByDesc('best_discount')->orderBy('name'),
            ProductSort::Popular => $query->withUnitsSold()
                ->orderByDesc('units_sold')
                ->orderByDesc('is_featured')
                ->orderBy('name'),
        };
    }

    /**
     * @param  Builder<$this>  $query
     * @return Builder<$this>
     */
    public function scopeWithCheapestPrice(Builder $query): Builder
    {
        return $query->addSelect(['cheapest_paise' => ProductVariant::query()
            ->selectRaw('min(price_paise)')
            ->whereColumn('product_variants.product_id', 'products.id')
            ->where('is_active', true)]);
    }

    /**
     * @param  Builder<$this>  $query
     * @return Builder<$this>
     */
    public function scopeWithBestDiscount(Builder $query): Builder
    {
        return $query->addSelect(['best_discount' => ProductVariant::query()
            ->selectRaw('coalesce(max(case when mrp_paise > price_paise then floor((mrp_paise - price_paise) * 100 / mrp_paise) else 0 end), 0)')
            ->whereColumn('product_variants.product_id', 'products.id')
            ->where('is_active', true)]);
    }

    /**
     * How many units the shop has actually sold recently, ignoring cancelled
     * orders. This is what "most popular" and the bestsellers rail mean now;
     * the sample catalogue's invented ranking is gone.
     *
     * @param  Builder<$this>  $query
     * @return Builder<$this>
     */
    public function scopeWithUnitsSold(Builder $query, int $days = self::POPULAR_WINDOW_DAYS): Builder
    {
        return $query->addSelect(['units_sold' => DB::table('order_items')
            ->selectRaw('coalesce(sum(order_items.quantity), 0)')
            ->join('orders', 'orders.id', '=', 'order_items.order_id')
            ->whereColumn('order_items.product_id', 'products.id')
            ->where('orders.status', '!=', OrderStatus::Cancelled->value)
            ->where('orders.placed_at', '>=', now()->subDays($days))]);
    }
}
