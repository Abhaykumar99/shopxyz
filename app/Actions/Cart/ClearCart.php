<?php

namespace App\Actions\Cart;

use App\Models\Cart;

/**
 * Empties the bag, keeping the bag itself. Used when an order is placed and
 * when someone signs out on a shared phone.
 */
final class ClearCart
{
    public function handle(Cart $cart): void
    {
        $cart->items()->delete();
        $cart->unsetRelation('items');
    }
}
