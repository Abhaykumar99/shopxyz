<?php

namespace App\Policies;

use App\Models\Order;
use App\Models\User;

/**
 * The customer who placed an order can see it; so can the shop. Nobody else,
 * including a delivery partner, who works from their assignment rather than
 * from the order itself (ADR-020).
 */
final class OrderPolicy
{
    public function view(User $user, Order $order): bool
    {
        if (! $user->is_active) {
            return false;
        }

        return $user->isAdmin() || $user->getKey() === $order->user_id;
    }

    /**
     * Printing a label or an invoice is shop work.
     */
    public function print(User $user, Order $order): bool
    {
        return $user->is_active && $user->isAdmin();
    }
}
