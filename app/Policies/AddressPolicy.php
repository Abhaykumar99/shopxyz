<?php

namespace App\Policies;

use App\Models\Address;
use App\Models\User;

/**
 * An address belongs to one customer and nobody else reads or changes it — not
 * another customer, and not staff, who have no reason to open the address book
 * (they see the copy taken onto the order instead).
 */
final class AddressPolicy
{
    public function view(User $user, Address $address): bool
    {
        return $this->owns($user, $address);
    }

    public function update(User $user, Address $address): bool
    {
        return $this->owns($user, $address);
    }

    public function delete(User $user, Address $address): bool
    {
        return $this->owns($user, $address);
    }

    private function owns(User $user, Address $address): bool
    {
        return $user->is_active && $user->getKey() === $address->user_id;
    }
}
