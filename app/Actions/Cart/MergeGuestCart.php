<?php

namespace App\Actions\Cart;

use App\Models\Cart;
use App\Models\CartItem;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * Moves the bag a visitor filled before signing in onto their account, which is
 * what the sign-in page promises: "Your bag stays with you."
 *
 * It has to be given the session id from **before** the session was regenerated,
 * because that is the one the guest bag is keyed on. Where a line exists in both
 * bags the larger quantity wins, capped at what the line will take, so signing
 * in can never quietly reduce what someone had chosen.
 */
final class MergeGuestCart
{
    public function handle(User $customer, ?string $guestSessionId): void
    {
        if ($guestSessionId === null || $guestSessionId === '') {
            return;
        }

        DB::transaction(function () use ($customer, $guestSessionId): void {
            $guest = Cart::query()
                ->whereNull('user_id')
                ->where('session_id', $guestSessionId)
                ->with('items.variant')
                ->first();

            if ($guest === null) {
                return;
            }

            $mine = Cart::query()->firstOrCreate(['user_id' => $customer->getKey()]);

            foreach ($guest->items as $line) {
                $variant = $line->variant;

                if ($variant === null) {
                    continue;
                }

                $existing = $mine->items()->where('product_variant_id', $variant->getKey())->first();
                $wanted = max($line->quantity, $existing === null ? 0 : $existing->quantity);

                $mine->items()->updateOrCreate(
                    ['product_variant_id' => $variant->getKey()],
                    ['quantity' => min($wanted, CartItem::ceilingFor($variant))],
                );
            }

            // The guest bag has served its purpose; leaving it would strand rows
            // against a session id nobody holds any more.
            $guest->delete();
        });
    }
}
