<?php

namespace App\Models;

use App\Support\Home\HomeContent;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * One hand-picked product or category inside a homepage section.
 *
 * @property int $id
 * @property int $home_section_id
 * @property int|null $product_id
 * @property int|null $category_id
 * @property int $sort_order
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 */
class HomeSectionItem extends Model
{
    /** @use HasFactory<\Database\Factories\HomeSectionItemFactory> */
    use HasFactory;

    /** @var list<string> */
    protected $fillable = ['home_section_id', 'product_id', 'category_id', 'sort_order'];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return ['sort_order' => 'integer'];
    }

    /**
     * @return BelongsTo<HomeSection, $this>
     */
    public function section(): BelongsTo
    {
        return $this->belongsTo(HomeSection::class, 'home_section_id');
    }

    /**
     * @return BelongsTo<Product, $this>
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * @return BelongsTo<Category, $this>
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
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
