<?php

use App\Models\User;

beforeEach(function () {
    $this->customer = User::factory()->googleCustomer()->create();
});

it('signs in a seeded customer and returns to a same-site path', function () {
    $this->get('/dev/ui/as/customer?return=/cart')->assertRedirect('/cart');

    expect(auth()->id())->toBe($this->customer->id);
});

it('signs in a seeded delivery partner', function () {
    $partner = User::factory()->deliveryPartner()->create();

    $this->get('/dev/ui/as/delivery')->assertRedirect(route('delivery.index'));

    expect(auth()->id())->toBe($partner->id);
});

it('never redirects to another site', function (string $return) {
    $this->get('/dev/ui/as/customer?return='.urlencode($return))
        ->assertRedirect(route('account.profile'));
})->with(['https://evil.example', '//evil.example', '/\evil.example']);

it('switches back to a guest', function () {
    signInCustomer();

    $this->get('/dev/ui/as/guest')->assertRedirect(route('shop.home'));

    expect(auth()->check())->toBeFalse();
});

it('says so when nothing is seeded yet', function () {
    $this->customer->forceDelete();

    $this->get('/dev/ui/as/customer')
        ->assertRedirect(route('shop.home'))
        ->assertSessionHas('toast');

    expect(auth()->check())->toBeFalse();
});

it('never signs in an account the shop has switched off', function () {
    $this->customer->forceFill(['is_active' => false])->save();

    $this->get('/dev/ui/as/customer')->assertRedirect(route('shop.home'));

    expect(auth()->check())->toBeFalse();
});

it('does not exist in production', function () {
    app()->detectEnvironment(fn (): string => 'production');

    $this->get('/dev/ui/as/customer')->assertNotFound();
});

it('only accepts the roles it knows', function () {
    $this->get('/dev/ui/as/admin')->assertNotFound();
});
