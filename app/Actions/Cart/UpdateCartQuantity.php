<?php

namespace App\Actions\Cart;

use App\Models\Cart;
use App\Models\CartItem;
use App\Models\ProductVariant;
use Illuminate\Support\Facades\DB;

/**
 * Sets a line to a quantity, capped by what the line will take. Zero or less
 * removes it. Returns the quantity actually stored, so the bag can tell the
 * customer when their number was reduced.
 */
final class UpdateCartQuantity
{
    public function handle(Cart $cart, ProductVariant $variant, int $quantity): int
    {
        return DB::transaction(function () use ($cart, $variant, $quantity): int {
            if ($quantity < 1) {
                $cart->items()->where('product_variant_id', $variant->getKey())->delete();

                return 0;
            }

            $line = $cart->items()->where('product_variant_id', $variant->getKey())->first();

            // Changing a quantity never adds a line that was not already there.
            if ($line === null) {
                return 0;
            }

            $line->setRelation('variant', $variant);
            $line->quantity = min($quantity, CartItem::ceilingFor($variant));
            $line->save();

            return $line->quantity;
        });
    }
}
