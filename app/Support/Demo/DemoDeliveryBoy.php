<?php

namespace App\Support\Demo;

use App\Support\IndianPhone;
use Illuminate\Contracts\Session\Session;

/**
 * TEMPORARY stand-in for the signed-in delivery boy (Phase 3).
 * Replaced in Phase 4 by a staff account (email/phone + password) with the
 * `delivery` role, created by the admin. Staff never use Google sign-in (ADR-004).
 */
final class DemoDeliveryBoy
{
    public const PHONE = '9000011111';

    public const PASSWORD = 'delivery-demo';

    private const KEY = 'demo.delivery_boy';

    public function __construct(private readonly Session $session) {}

    public function isSignedIn(): bool
    {
        return (bool) $this->session->get(self::KEY.'.signed_in', false);
    }

    /**
     * The sample credentials are the only ones this prototype accepts.
     */
    public function credentialsMatch(string $phone, string $password): bool
    {
        return IndianPhone::normalize($phone) === self::PHONE && $password === self::PASSWORD;
    }

    public function signIn(): void
    {
        $this->session->put(self::KEY.'.signed_in', true);
        $this->session->regenerate();
    }

    public function signOut(): void
    {
        $this->session->forget(self::KEY);
        $this->session->regenerate();
    }

    /**
     * @return array{name: string, phone: string, area: string, vehicle: string, since: string}
     */
    public function profile(): array
    {
        return [
            'name' => 'Rahul Kumar',
            'phone' => self::PHONE,
            'area' => 'Boring Road and Bakerganj',
            'vehicle' => 'Bike, BR 01 AB 1234',
            'since' => 'March 2026',
        ];
    }

    public function firstName(): string
    {
        return explode(' ', $this->profile()['name'])[0];
    }
}
