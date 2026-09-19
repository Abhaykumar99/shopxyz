<?php

namespace App\Actions\Cart;

use App\Models\Cart;
use App\Models\CartItem;
use App\Models\ProductVariant;
use Illuminate\Support\Facades\DB;

/**
 * Puts units of a variant in the bag, never more than the line will take.
 * Returns how many were actually added, which is how the shop knows whether to
 * say "added" or "that is all we have".
 */
final class AddToCart
{
    public function handle(Cart $cart, ProductVariant $variant, int $quantity = 1): int
    {
        if ($quantity < 1 || ! CartItem::canBeOrdered($variant)) {
            return 0;
        }

        return DB::transaction(function () use ($cart, $variant, $quantity): int {
            $line = $cart->items()->firstOrNew(['product_variant_id' => $variant->getKey()]);
            $line->setRelation('variant', $variant);

            $current = (int) ($line->quantity ?? 0);
            $target = min($current + $quantity, CartItem::ceilingFor($variant));

            if ($target <= $current) {
                return 0;
            }

            $line->quantity = $target;
            $cart->items()->save($line);

            return $target - $current;
        });
    }
}
