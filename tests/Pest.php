<?php

use App\Support\Demo\DemoCart;
use App\Support\Demo\DemoCustomer;
use Illuminate\Foundation\Testing\RefreshDatabase;
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
 * Signs in the sample customer. With `$phone` the profile already has a mobile number.
 */
function signInDemoCustomer(?string $phone = '9830012345'): DemoCustomer
{
    $customer = app(DemoCustomer::class);
    $customer->signIn();

    if ($phone !== null) {
        $customer->updatePhone($phone);
    }

    return $customer;
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
