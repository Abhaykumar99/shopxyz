<?php

use App\Support\Demo\DemoCustomer;

it('signs in the sample customer and returns to a same-site path', function () {
    $this->get('/dev/ui/as/customer?return=/cart')->assertRedirect('/cart');

    expect(app(DemoCustomer::class)->isSignedIn())->toBeTrue();
});

it('never redirects to another site', function (string $return) {
    $this->get('/dev/ui/as/customer?return='.urlencode($return))
        ->assertRedirect(route('account.profile'));
})->with(['https://evil.example', '//evil.example', '/\\evil.example']);

it('switches back to a guest', function () {
    signInDemoCustomer();

    $this->get('/dev/ui/as/guest')->assertRedirect(route('shop.home'));

    expect(app(DemoCustomer::class)->isSignedIn())->toBeFalse();
});

it('does not exist in production', function () {
    app()->detectEnvironment(fn (): string => 'production');

    $this->get('/dev/ui/as/customer')->assertNotFound();
});

it('only accepts the guest and customer roles', function () {
    $this->get('/dev/ui/as/admin')->assertNotFound();
});
