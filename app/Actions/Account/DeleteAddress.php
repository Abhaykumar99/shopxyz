<?php

namespace App\Actions\Account;

use App\Models\Address;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * Removes an address. If it was the default one, the oldest of the remaining
 * addresses takes over, so the customer never ends up with none selected.
 */
final class DeleteAddress
{
    public function handle(User $customer, Address $address): void
    {
        DB::transaction(function () use ($customer, $address): void {
            $wasDefault = $address->is_default;
            $address->delete();

            if (! $wasDefault) {
                return;
            }

            $customer->addresses()->oldest('id')->first()?->update(['is_default' => true]);
        });
    }
}
