<?php

namespace App\Models;

use App\Support\Catalog\WholesaleCatalog;
use App\Support\Catalog\WholesaleItem;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * One line of a bag.
 *
 * No price is stored here. Everything money-related is worked out live from the
 * variant and its wholesale bands, so a price change in the admin panel reaches
 * a bag that is already open, and only the order keeps a copy (ADR-005, ADR-019).
 *
 * @property int $id
 * @property int $cart_id
 * @property int $product_variant_id
 * @property int $quantity
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 */
class CartItem extends Model
{
    /** @use HasFactory<\Database\Factories\CartItemFactory> */
    use HasFactory;

    /**
     * The most of an ordinary product one line will take. Wholesale lines are
     * ordered in rather than taken off a shelf, so they are not capped here.
     */
    public const MAX_PER_LINE = 10;

    /** @var list<string> */
    protected $fillable = [
        'cart_id',
        'product_variant_id',
        'quantity',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'quantity' => 'integer',
        ];
    }

    /**
     * @return BelongsTo<Cart, $this>
     */
    public function cart(): BelongsTo
    {
        return $this->belongsTo(Cart::class);
    }

    /**
     * @return BelongsTo<ProductVariant, $this>
     */
    public function variant(): BelongsTo
    {
        return $this->belongsTo(ProductVariant::class, 'product_variant_id');
    }

    public function sku(): string
    {
        return (string) $this->variant?->sku;
    }

    /**
     * The wholesale bands for this line's product, or null when it is retail only.
     */
    public function wholesale(): ?WholesaleItem
    {
        return $this->variant === null ? null : WholesaleItem::for($this->variant);
    }

    /**
     * A line is priced at wholesale once it reaches the minimum quantity — no
     * account or approval, just the quantity (ADR-019).
     */
    public function isWholesale(): bool
    {
        $wholesale = $this->wholesale();

        return $wholesale !== null && $this->quantity >= $wholesale->moq();
    }

    public function unitPrice(): int
    {
        return $this->slab()['paise'] ?? (int) $this->variant?->price_paise;
    }

    /**
     * The band this line's quantity qualifies for, or null below the minimum.
     *
     * @return array{min: int, paise: int}|null
     */
    public function slab(): ?array
    {
        return $this->isWholesale() ? $this->wholesale()?->slabFor($this->quantity) : null;
    }

    /**
     * Label for the band this line is priced at, such as "20–49".
     */
    public function slabLabel(): ?string
    {
        $wholesale = $this->wholesale();
        $slab = $this->slab();

        if ($wholesale === null || $slab === null) {
            return null;
        }

        $index = array_search($slab, $wholesale->slabs, true);

        return $index === false ? null : $wholesale->slabRange((int) $index);
    }

    /**
     * The next cheaper band, so the bag can say "add 4 more for ₹309 each".
     *
     * @return array{min: int, paise: int}|null
     */
    public function nextSlab(): ?array
    {
        return $this->wholesale()?->slabAfter($this->quantity);
    }

    public function total(): int
    {
        return $this->unitPrice() * $this->quantity;
    }

    public function mrpTotal(): int
    {
        $variant = $this->variant;

        return (int) ($variant->mrp_paise ?? $variant->price_paise) * $this->quantity;
    }

    /**
     * What the wholesale bands save against the shop's own retail price.
     */
    public function wholesaleSaving(): int
    {
        if (! $this->isWholesale()) {
            return 0;
        }

        return ((int) $this->variant?->price_paise - $this->unitPrice()) * $this->quantity;
    }

    /**
     * An empty shelf blocks a retail line, but never a wholesale one: those are
     * ordered in rather than picked from stock.
     */
    public function isAvailable(): bool
    {
        return $this->isWholesale() || (int) $this->variant?->stock_quantity >= $this->quantity;
    }

    public function isMadeToOrder(): bool
    {
        return $this->isWholesale() && $this->quantity > (int) $this->variant?->stock_quantity;
    }

    /**
     * The most this line will take.
     */
    public function maxQuantity(): int
    {
        return $this->variant === null ? 1 : self::ceilingFor($this->variant);
    }

    /**
     * Shared with the pages that offer a quantity stepper before anything is in
     * the bag, so the same ceiling applies everywhere.
     */
    public static function ceilingFor(ProductVariant $variant): int
    {
        return WholesaleItem::for($variant) !== null
            ? WholesaleCatalog::MAX_QUANTITY
            : max(1, min(self::MAX_PER_LINE, (int) $variant->stock_quantity));
    }

    /**
     * Whether this variant can go in a bag at all.
     */
    public static function canBeOrdered(ProductVariant $variant): bool
    {
        return $variant->inStock() || WholesaleItem::for($variant) !== null;
    }
}
