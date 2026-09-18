<?php

use App\Livewire\Delivery\SignIn;
use App\Support\Demo\DemoDeliveryBoy;
use Illuminate\Support\Facades\RateLimiter;
use Livewire\Livewire;

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

it('signs the delivery boy in and returns to where they were going', function () {
    $this->get('/delivery/cash')->assertRedirect(route('delivery.login'));

    Livewire::test(SignIn::class)
        ->set('phone', '90000 11111')
        ->set('password', DemoDeliveryBoy::PASSWORD)
        ->call('submit')
        ->assertHasNoErrors()
        ->assertRedirect(route('delivery.cash'));

    expect(app(DemoDeliveryBoy::class)->isSignedIn())->toBeTrue();
});

it('keeps a signed-in delivery boy out of the sign-in screen', function () {
    signInDemoDeliveryBoy();

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
        ->set('password', DemoDeliveryBoy::PASSWORD)
        ->call('submit')
        ->assertHasErrors(['phone']);
});

it('refuses the wrong password without saying which part was wrong', function () {
    Livewire::test(SignIn::class)
        ->set('phone', DemoDeliveryBoy::PHONE)
        ->set('password', 'not-the-password')
        ->call('submit')
        ->assertHasErrors(['phone'])
        ->assertSee('do not match')
        ->assertSet('password', '');

    expect(app(DemoDeliveryBoy::class)->isSignedIn())->toBeFalse();
});

it('locks sign-in after five wrong attempts', function () {
    foreach (range(1, 5) as $attempt) {
        RateLimiter::hit('delivery-login:127.0.0.1', 900);
    }

    Livewire::test(SignIn::class)
        ->set('phone', DemoDeliveryBoy::PHONE)
        ->set('password', DemoDeliveryBoy::PASSWORD)
        ->call('submit')
        ->assertHasErrors(['phone'])
        ->assertSee('Too many attempts');

    expect(app(DemoDeliveryBoy::class)->isSignedIn())->toBeFalse();
});
