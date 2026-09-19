<?php

namespace App\Actions\Cart;

use App\Models\Cart;
use App\Models\ProductVariant;

/**
 * Takes a line out of the bag.
 */
final class RemoveFromCart
{
    public function handle(Cart $cart, ProductVariant $variant): void
    {
        $cart->items()->where('product_variant_id', $variant->getKey())->delete();
    }
}
