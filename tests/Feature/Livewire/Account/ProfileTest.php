<?php

use App\Livewire\Account\Profile;
use Livewire\Livewire;

it('greets the customer with their Google details', function () {
    signInCustomer()->forceFill([
        'name' => 'Priya Sharma',
        'email' => 'priya.sharma@example.com',
    ])->save();

    $this->get('/account')
        ->assertOk()
        ->assertSee('Hello, Priya')
        ->assertSee('priya.sharma@example.com')
        ->assertSee('+91 98300 12345')
        ->assertSee('<meta name="robots" content="noindex, nofollow">', false);
});

it('asks for a mobile number when there is none', function () {
    signInCustomer(phone: null);

    Livewire::test(Profile::class)
        ->assertSet('editingPhone', true)
        ->assertSee('Add your mobile number');
});

it('saves a valid mobile number', function () {
    $customer = signInCustomer(phone: null);

    Livewire::test(Profile::class)
        ->set('phoneForm.phone', '070000 00000')
        ->call('savePhone')
        ->assertHasNoErrors()
        ->assertSet('editingPhone', false)
        ->assertDispatched('toast', tone: 'success');

    expect($customer->fresh()->phone)->toBe('7000000000');
});

it('rejects an invalid mobile number and keeps the old one', function () {
    $customer = signInCustomer();

    Livewire::test(Profile::class)
        ->set('editingPhone', true)
        ->set('phoneForm.phone', '1234567890')
        ->call('savePhone')
        ->assertHasErrors(['phoneForm.phone']);

    expect($customer->fresh()->phone)->toBe('9830012345');
});

it('signs out and empties the bag', function () {
    signInCustomer();
    fillDemoCart(['MG-KK-1' => 1]);

    $this->post('/logout')->assertRedirect(route('shop.home'));

    expect(auth()->check())->toBeFalse();
    $this->get('/account')->assertRedirect(route('auth.login'));
    $this->get('/cart')->assertSee('Your bag is empty');
});

it('only signs out with a POST request', function () {
    signInCustomer();

    $this->get('/logout')
        ->assertMethodNotAllowed();
});

it('turns a guest away from the account pages', function () {
    $this->get('/account')->assertRedirect(route('auth.login'));
    $this->get('/account/addresses')->assertRedirect(route('auth.login'));
});

it('signs out an account the shop has switched off', function () {
    $customer = signInCustomer();

    $this->get('/account')->assertOk();

    $customer->forceFill(['is_active' => false])->save();

    $this->get('/account')->assertRedirect(route('auth.login'));
    expect(auth()->check())->toBeFalse();
});
