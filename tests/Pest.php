<?php

use App\Models\Address;
use App\Models\User;
use App\Support\Demo\DemoCart;
use Database\Seeders\CatalogSeeder;
use Database\Seeders\HomepageSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

/*
|--------------------------------------------------------------------------
| Test Case
|--------------------------------------------------------------------------
|
| Feature tests boot the Laravel app and run against the MySQL test
| database (see .env.testing locally, CI env vars in GitHub Actions).
|
*/

pest()->extend(TestCase::class)
    ->use(RefreshDatabase::class)
    ->in('Feature');

/*
|--------------------------------------------------------------------------
| Helpers (Phase 2 demo data)
|--------------------------------------------------------------------------
*/

/**
 * Signs in a customer the way Google sign-in leaves them (ADR-004). Pass
 * `phone: null` for someone who has not given a mobile number yet.
 */
function signInCustomer(?string $phone = '9830012345'): User
{
    $customer = User::factory()->googleCustomer()->create(['phone' => $phone]);

    test()->actingAs($customer);

    return $customer;
}

/**
 * Signs in a delivery partner: a staff account the admin created, with a
 * password and the `delivery` role.
 */
function signInDeliveryPartner(string $password = 'delivery-demo'): User
{
    $partner = User::factory()->deliveryPartner()->create([
        'password' => Hash::make($password),
    ]);

    test()->actingAs($partner);

    return $partner;
}

/**
 * A signed-in customer who already has a default delivery address inside the
 * served area, which is what most checkout tests need.
 */
function signInCustomerWithAddress(?string $phone = '9830012345'): User
{
    $customer = signInCustomer($phone);
    $customer->forceFill(['name' => 'Priya Sharma', 'email' => 'priya.sharma@example.com'])->save();

    Address::factory()->default()->for($customer)->create([
        'label' => 'Home',
        'recipient_name' => $customer->name,
        'phone' => $phone ?? '9830012345',
        'line1' => 'Flat 3B, Shanti Apartments',
        'line2' => 'Boring Road',
        'landmark' => 'Pani Tanki',
        'city' => 'Patna',
        'state' => 'Bihar',
        'pincode' => '800001',
    ]);

    return $customer->refresh();
}

/**
 * Puts SKUs in the bag: ['MG-KK-2' => 1].
 *
 * @param  array<string, int>  $lines
 */
function fillDemoCart(array $lines): DemoCart
{
    $cart = app(DemoCart::class);

    foreach ($lines as $sku => $quantity) {
        $cart->add($sku, $quantity);
    }

    return $cart;
}

/**
 * The shop's catalogue: categories, products, variants and wholesale bands.
 * The storefront reads the database from Phase 7, so any test that renders a
 * product needs this where it used to get the sample catalogue for free.
 */
function seedCatalog(): void
{
    app(CatalogSeeder::class)->run();
}

/**
 * The homepage banners and blocks the shop starts with (ADR-024). The blocks
 * point at products, so the catalogue comes first.
 */
function seedHomepage(): void
{
    seedCatalog();
    app(HomepageSeeder::class)->run();
}
