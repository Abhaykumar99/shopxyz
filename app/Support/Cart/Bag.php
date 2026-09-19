<?php

namespace App\Support\Cart;

use App\Actions\Cart\AddToCart;
use App\Actions\Cart\ClearCart;
use App\Actions\Cart\RemoveFromCart;
use App\Actions\Cart\UpdateCartQuantity;
use App\Models\Cart;
use App\Models\CartItem;
use App\Models\ProductVariant;
use App\Support\ShopSettings;
use Illuminate\Contracts\Session\Session;
use Illuminate\Support\Collection;

/**
 * The bag for whoever is browsing: a row against the signed-in customer, or
 * against the session for a guest, merged onto the account when they sign in
 * (docs/erd.md). Pages talk to this; the rules about what a line will take live
 * in the actions it calls.
 *
 * Bound once per request, so a page that asks for the bag several times — the
 * header count, the page itself, the summary — reads it once.
 */
final class Bag
{
    private ?Cart $cart = null;

    public function __construct(private readonly Session $session) {}

    /**
     * The bag row, created the first time something is put in it. Reading an
     * empty bag does not write anything.
     */
    public function model(): Cart
    {
        return $this->cart ??= $this->resolve();
    }

    /**
     * @return Collection<int, CartItem>
     */
    public function lines(): Collection
    {
        $cart = $this->model();

        if (! $cart->exists) {
            return new Collection;
        }

        if (! $cart->relationLoaded('items')) {
            $cart->load(['items' => fn ($items) => $items->orderBy('id'), 'items.variant.product.category.parent', 'items.variant.priceSlabs']);
        }

        return $cart->items->filter(fn (CartItem $line): bool => $line->variant !== null)->values();
    }

    public function count(): int
    {
        return (int) $this->lines()->sum('quantity');
    }

    public function quantityOf(string $sku): int
    {
        return (int) $this->lines()->firstWhere(fn (CartItem $line): bool => $line->sku() === $sku)?->quantity;
    }

    public function isEmpty(): bool
    {
        return $this->lines()->isEmpty();
    }

    /**
     * A line the shop can no longer fill — the shelf ran down after it went in
     * the bag. Checkout stops until it is fixed.
     */
    public function hasUnavailableLines(): bool
    {
        return $this->lines()->contains(fn (CartItem $line): bool => ! $line->isAvailable());
    }

    /**
     * @return array{mrp: int, subtotal: int, discount: int, delivery: int, total: int, free_delivery_shortfall: int, meets_minimum: bool, items: int, wholesale_lines: int, wholesale_saving: int, cod_available: bool}
     */
    public function summary(ShopSettings $shop): array
    {
        return CartSummary::for($this->lines(), $shop);
    }

    public function ceilingFor(ProductVariant $variant): int
    {
        return CartItem::ceilingFor($variant);
    }

    /**
     * Adds units and returns how many went in.
     */
    public function add(ProductVariant $variant, int $quantity = 1): int
    {
        $added = app(AddToCart::class)->handle($this->persisted(), $variant, $quantity);
        $this->forget();

        return $added;
    }

    /**
     * Sets a line's quantity and returns what was stored.
     */
    public function setQuantity(ProductVariant $variant, int $quantity): int
    {
        $stored = app(UpdateCartQuantity::class)->handle($this->persisted(), $variant, $quantity);
        $this->forget();

        return $stored;
    }

    public function remove(ProductVariant $variant): void
    {
        app(RemoveFromCart::class)->handle($this->persisted(), $variant);
        $this->forget();
    }

    public function clear(): void
    {
        $cart = $this->model();

        if ($cart->exists) {
            app(ClearCart::class)->handle($cart);
        }

        $this->forget();
    }

    /**
     * Finds this browser's bag without creating one, so a visitor who only
     * looks around never leaves a row behind.
     */
    private function resolve(): Cart
    {
        $userId = auth()->id();

        $cart = $userId !== null
            ? Cart::query()->where('user_id', $userId)->first()
            : Cart::query()->whereNull('user_id')->where('session_id', $this->session->getId())->first();

        return $cart ?? new Cart($userId !== null
            ? ['user_id' => $userId]
            : ['session_id' => $this->session->getId()]);
    }

    /**
     * The bag as a saved row, because something is about to go in it.
     */
    private function persisted(): Cart
    {
        $cart = $this->model();

        if (! $cart->exists) {
            $cart->save();
        }

        return $cart;
    }

    /**
     * Drops the loaded lines so the next read sees the change.
     */
    private function forget(): void
    {
        $this->cart?->unsetRelation('items');
    }
}
