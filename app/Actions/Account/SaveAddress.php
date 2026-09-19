<?php

namespace App\Actions\Account;

use App\Models\Address;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * Adds or edits one of the customer's delivery addresses, keeping the rule that
 * exactly one address is the default: the first one always is, and marking a
 * second one demotes the rest.
 */
final class SaveAddress
{
    /**
     * @param  array{label: string, recipient_name: string, phone: string, line1: string, line2: string|null, landmark: string|null, city: string, state: string, pincode: string, is_default: bool}  $data
     */
    public function handle(User $customer, array $data, ?Address $address = null): Address
    {
        return DB::transaction(function () use ($customer, $data, $address): Address {
            $isFirst = $customer->addresses()->count() === 0;
            $makeDefault = $data['is_default'] || $isFirst || (bool) $address?->is_default;
            $data['is_default'] = $makeDefault;

            $saved = $address === null
                ? $customer->addresses()->create($data)
                : tap($address)->update($data);

            if ($makeDefault) {
                $customer->addresses()->whereKeyNot($saved->getKey())->update(['is_default' => false]);
            }

            return $saved->refresh();
        });
    }
}
