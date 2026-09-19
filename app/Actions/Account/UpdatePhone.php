<?php

namespace App\Actions\Account;

use App\Models\User;
use App\Support\IndianPhone;

/**
 * Saves the customer's mobile number. Google never gives us one, and delivery
 * cannot happen without it, so it is asked for before the first order (ADR-004).
 */
final class UpdatePhone
{
    public function handle(User $customer, string $phone): User
    {
        $customer->forceFill(['phone' => IndianPhone::normalize($phone)])->save();

        return $customer;
    }
}
