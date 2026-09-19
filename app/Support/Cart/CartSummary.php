<?php

namespace App\Support\Cart;

use App\Models\CartItem;
use App\Support\ShopSettings;
use Illuminate\Support\Collection;

/**
 * What the bag adds up to. Every money rule it applies — the delivery charge,
 * the free-delivery threshold, the minimum order and the cash-on-delivery
 * ceiling — belongs to `ShopSettings`, so the shop owner changes them in the
 * admin panel rather than anyone changing them here (ADR-013).
 */
final class CartSummary
{
    /**
     * @param  Collection<int, CartItem>  $lines
     * @return array{mrp: int, subtotal: int, discount: int, delivery: int, total: int, free_delivery_shortfall: int, meets_minimum: bool, items: int, wholesale_lines: int, wholesale_saving: int, cod_available: bool}
     */
    public static function for(Collection $lines, ShopSettings $shop): array
    {
        $subtotal = (int) $lines->sum(fn (CartItem $line): int => $line->total());
        $mrp = (int) $lines->sum(fn (CartItem $line): int => $line->mrpTotal());
        $delivery = $lines->isEmpty() ? 0 : $shop->deliveryChargeFor($subtotal);
        $wholesale = $lines->filter(fn (CartItem $line): bool => $line->isWholesale());

        return [
            'mrp' => $mrp,
            'subtotal' => $subtotal,
            'discount' => $mrp - $subtotal,
            'delivery' => $delivery,
            'total' => $subtotal + $delivery,
            'free_delivery_shortfall' => $lines->isEmpty() ? 0 : $shop->freeDeliveryShortfall($subtotal),
            'meets_minimum' => $shop->meetsMinimumOrder($subtotal),
            'items' => (int) $lines->sum('quantity'),
            'wholesale_lines' => $wholesale->count(),
            'wholesale_saving' => (int) $wholesale->sum(fn (CartItem $line): int => $line->wholesaleSaving()),
            'cod_available' => $shop->allowsCodFor($subtotal + $delivery),
        ];
    }
}
