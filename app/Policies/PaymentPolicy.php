<?php

namespace App\Policies;

use App\Models\Payment;
use App\Models\User;

/**
 * Payment screenshots live on the private disk and are streamed only after a
 * policy check (ADR-009). Only the shop verifies payments, so only the shop
 * sees the proof — the customer who uploaded it already has their own copy.
 */
final class PaymentPolicy
{
    public function view(User $user, Payment $payment): bool
    {
        return $user->is_active && $user->isAdmin();
    }

    public function viewProof(User $user, Payment $payment): bool
    {
        return $this->view($user, $payment);
    }
}
