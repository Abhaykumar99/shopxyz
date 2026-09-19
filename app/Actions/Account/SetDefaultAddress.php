<?php

namespace App\Actions\Account;

use App\Models\Address;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * Makes one address the default and demotes the others, so checkout always has
 * exactly one to pre-select.
 */
final class SetDefaultAddress
{
    public function handle(User $customer, Address $address): void
    {
        DB::transaction(function () use ($customer, $address): void {
            $customer->addresses()->whereKeyNot($address->getKey())->update(['is_default' => false]);
            $address->update(['is_default' => true]);
        });
    }
}
