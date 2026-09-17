<?php

namespace App\Support\Demo;

use Illuminate\Contracts\Session\Session;
use Illuminate\Support\Str;

/**
 * TEMPORARY stand-in for the signed-in customer (Phase 2).
 * Replaced by Google sign-in (Phase 4) and the User/Address models.
 */
final class DemoCustomer
{
    private const KEY = 'demo.customer';

    public function __construct(private readonly Session $session) {}

    public function isSignedIn(): bool
    {
        return (bool) $this->session->get(self::KEY.'.signed_in', false);
    }

    public function signIn(): void
    {
        if (! $this->session->has(self::KEY.'.profile')) {
            $this->session->put(self::KEY.'.profile', [
                'name' => 'Priya Sharma',
                'email' => 'priya.sharma@example.com',
                'phone' => null,
            ]);
            $this->session->put(self::KEY.'.addresses', self::seedAddresses());
        }

        $this->session->put(self::KEY.'.signed_in', true);
        $this->session->regenerate();
    }

    public function signOut(): void
    {
        $this->session->forget(self::KEY);
        $this->session->forget('demo.cart');
        $this->session->regenerate();
    }

    /**
     * @return array{name: string, email: string, phone: string|null}
     */
    public function profile(): array
    {
        $profile = (array) $this->session->get(self::KEY.'.profile', []);

        return [
            'name' => (string) ($profile['name'] ?? ''),
            'email' => (string) ($profile['email'] ?? ''),
            'phone' => $profile['phone'] ?? null,
        ];
    }

    public function firstName(): string
    {
        return Str::before($this->profile()['name'], ' ');
    }

    public function initials(): string
    {
        $words = array_slice(preg_split('/\s+/', trim($this->profile()['name'])) ?: [], 0, 2);

        return mb_strtoupper(implode('', array_map(fn (string $word): string => mb_substr($word, 0, 1), $words)));
    }

    public function hasPhone(): bool
    {
        return ! empty($this->profile()['phone']);
    }

    public function updatePhone(string $phone): void
    {
        $this->session->put(self::KEY.'.profile.phone', $phone);
    }

    /**
     * @return list<DemoAddress>
     */
    public function addresses(): array
    {
        $addresses = array_map(
            fn (array $data): DemoAddress => DemoAddress::fromArray($data),
            (array) $this->session->get(self::KEY.'.addresses', []),
        );
        usort($addresses, fn (DemoAddress $a, DemoAddress $b): int => $b->isDefault <=> $a->isDefault);

        return array_values($addresses);
    }

    public function address(?string $id): ?DemoAddress
    {
        foreach ($this->addresses() as $address) {
            if ($address->id === $id) {
                return $address;
            }
        }

        return null;
    }

    public function defaultAddress(): ?DemoAddress
    {
        return $this->addresses()[0] ?? null;
    }

    /**
     * Creates or updates an address. The first address, or one marked default, becomes the default.
     *
     * @param  array{label: string, name: string, phone: string, line1: string, line2?: string|null, landmark?: string|null, city: string, state: string, pincode: string, is_default?: bool}  $data
     */
    public function saveAddress(array $data, ?string $id = null): DemoAddress
    {
        $all = (array) $this->session->get(self::KEY.'.addresses', []);
        $id = $id !== null && isset($all[$id]) ? $id : 'addr_'.Str::lower(Str::random(8));
        $makeDefault = ($data['is_default'] ?? false) || $all === [] || ($all[$id]['is_default'] ?? false);

        if ($makeDefault) {
            foreach ($all as $key => $existing) {
                $all[$key]['is_default'] = false;
            }
        }

        $all[$id] = [...$data, 'id' => $id, 'is_default' => $makeDefault];
        $this->session->put(self::KEY.'.addresses', $all);

        return DemoAddress::fromArray($all[$id]);
    }

    public function deleteAddress(string $id): void
    {
        $all = (array) $this->session->get(self::KEY.'.addresses', []);
        $wasDefault = $all[$id]['is_default'] ?? false;
        unset($all[$id]);

        if ($wasDefault && $all !== []) {
            $all[array_key_first($all)]['is_default'] = true;
        }

        $this->session->put(self::KEY.'.addresses', $all);
    }

    public function setDefaultAddress(string $id): void
    {
        $all = (array) $this->session->get(self::KEY.'.addresses', []);

        if (! isset($all[$id])) {
            return;
        }

        foreach ($all as $key => $existing) {
            $all[$key]['is_default'] = $key === $id;
        }

        $this->session->put(self::KEY.'.addresses', $all);
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    private static function seedAddresses(): array
    {
        return [
            'addr_home' => [
                'id' => 'addr_home', 'label' => 'Home', 'name' => 'Priya Sharma', 'phone' => '9830012345',
                'line1' => 'Flat 3B, Shanti Apartments', 'line2' => 'Boring Road', 'landmark' => 'Pani Tanki',
                'city' => 'Patna', 'state' => 'Bihar', 'pincode' => '800001', 'is_default' => true,
            ],
            'addr_work' => [
                'id' => 'addr_work', 'label' => 'Work', 'name' => 'Priya Sharma', 'phone' => '9830012345',
                'line1' => '2nd floor, Lalit Bhawan', 'line2' => 'Bailey Road', 'landmark' => null,
                'city' => 'Patna', 'state' => 'Bihar', 'pincode' => '800014', 'is_default' => false,
            ],
        ];
    }
}
