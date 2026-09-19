<?php

use App\Livewire\Delivery\SignIn;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Livewire\Livewire;

const PARTNER_PHONE = '9000011111';
const PARTNER_PASSWORD = 'delivery-demo';

beforeEach(function () {
    $this->partner = User::factory()->deliveryPartner()->create([
        'phone' => PARTNER_PHONE,
        'password' => Hash::make(PARTNER_PASSWORD),
    ]);
});

it('shows the sign-in screen for the delivery panel', function () {
    $this->get('/delivery/login')
        ->assertOk()
        ->assertSee('Delivery panel')
        ->assertSee('Mobile number')
        ->assertSee('<meta name="robots" content="noindex, nofollow">', false);
});

it('sends anyone who is not signed in to the sign-in screen', function (string $path) {
    $this->get($path)->assertRedirect(route('delivery.login'));
})->with(['/delivery', '/delivery/cash', '/delivery/history', '/delivery/profile', '/delivery/ORD-10245']);

it('signs the delivery partner in and returns to where they were going', function () {
    $this->get('/delivery/cash')->assertRedirect(route('delivery.login'));

    Livewire::test(SignIn::class)
        ->set('phone', '90000 11111')
        ->set('password', PARTNER_PASSWORD)
        ->call('submit')
        ->assertHasNoErrors()
        ->assertRedirect(route('delivery.cash'));

    expect(auth()->id())->toBe($this->partner->id)
        ->and($this->partner->fresh()->last_login_at)->not->toBeNull();
});

it('keeps a signed-in delivery partner out of the sign-in screen', function () {
    signInDeliveryPartner();

    $this->get('/delivery/login')->assertRedirect(route('delivery.index'));
});

it('asks for both the number and the password', function () {
    Livewire::test(SignIn::class)
        ->call('submit')
        ->assertHasErrors(['phone' => 'required', 'password' => 'required']);
});

it('rejects an invalid mobile number', function () {
    Livewire::test(SignIn::class)
        ->set('phone', '12345')
        ->set('password', PARTNER_PASSWORD)
        ->call('submit')
        ->assertHasErrors(['phone']);
});

it('refuses the wrong password without saying which part was wrong', function () {
    Livewire::test(SignIn::class)
        ->set('phone', PARTNER_PHONE)
        ->set('password', 'not-the-password')
        ->call('submit')
        ->assertHasErrors(['phone'])
        ->assertSee('do not match')
        ->assertSet('password', '');

    expect(auth()->check())->toBeFalse();
});

it('gives the same answer for a number nobody has', function () {
    Livewire::test(SignIn::class)
        ->set('phone', '9888877777')
        ->set('password', PARTNER_PASSWORD)
        ->call('submit')
        ->assertHasErrors(['phone'])
        ->assertSee('do not match');

    expect(auth()->check())->toBeFalse();
});

it('refuses a partner the shop has switched off', function () {
    $this->partner->forceFill(['is_active' => false])->save();

    Livewire::test(SignIn::class)
        ->set('phone', PARTNER_PHONE)
        ->set('password', PARTNER_PASSWORD)
        ->call('submit')
        ->assertHasErrors(['phone']);

    expect(auth()->check())->toBeFalse();
});

it('refuses a customer and an admin with the same number', function (string $state) {
    $this->partner->forceDelete();

    User::factory()->{$state}()->create([
        'phone' => PARTNER_PHONE,
        'password' => Hash::make(PARTNER_PASSWORD),
    ]);

    Livewire::test(SignIn::class)
        ->set('phone', PARTNER_PHONE)
        ->set('password', PARTNER_PASSWORD)
        ->call('submit')
        ->assertHasErrors(['phone']);

    expect(auth()->check())->toBeFalse();
})->with(['admin', 'googleCustomer']);

it('locks sign-in after five wrong attempts', function () {
    foreach (range(1, 5) as $attempt) {
        RateLimiter::hit('delivery-login:127.0.0.1', 900);
    }

    Livewire::test(SignIn::class)
        ->set('phone', PARTNER_PHONE)
        ->set('password', PARTNER_PASSWORD)
        ->call('submit')
        ->assertHasErrors(['phone'])
        ->assertSee('Too many attempts');

    expect(auth()->check())->toBeFalse();
});

it('keeps a customer and an admin out of the delivery panel', function (string $state) {
    $this->actingAs(User::factory()->{$state}()->create());

    $this->get('/delivery')->assertForbidden();
})->with(['admin', 'googleCustomer']);

it('sends a deactivated partner back to the delivery sign-in, not the shop one', function () {
    signInDeliveryPartner();

    $this->get('/delivery')->assertOk();

    auth()->user()->forceFill(['is_active' => false])->save();

    $this->get('/delivery')->assertRedirect(route('delivery.login'));
    expect(auth()->check())->toBeFalse();
});
