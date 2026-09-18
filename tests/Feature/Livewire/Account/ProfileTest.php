<?php

use App\Livewire\Account\Profile;
use App\Support\Demo\DemoCustomer;
use Livewire\Livewire;

it('greets the customer with their Google details', function () {
    signInDemoCustomer();

    $this->get('/account')
        ->assertOk()
        ->assertSee('Hello, Priya')
        ->assertSee('priya.sharma@example.com')
        ->assertSee('+91 98300 12345')
        ->assertSee('<meta name="robots" content="noindex, nofollow">', false);
});

it('asks for a mobile number when there is none', function () {
    signInDemoCustomer(phone: null);

    Livewire::test(Profile::class)
        ->assertSet('editingPhone', true)
        ->assertSee('Add your mobile number');
});

it('saves a valid mobile number', function () {
    signInDemoCustomer(phone: null);

    Livewire::test(Profile::class)
        ->set('phoneForm.phone', '070000 00000')
        ->call('savePhone')
        ->assertHasNoErrors()
        ->assertSet('editingPhone', false)
        ->assertDispatched('toast', tone: 'success');

    expect(app(DemoCustomer::class)->profile()['phone'])->toBe('7000000000');
});

it('rejects an invalid mobile number and keeps the old one', function () {
    signInDemoCustomer();

    Livewire::test(Profile::class)
        ->set('editingPhone', true)
        ->set('phoneForm.phone', '1234567890')
        ->call('savePhone')
        ->assertHasErrors(['phoneForm.phone']);

    expect(app(DemoCustomer::class)->profile()['phone'])->toBe('9830012345');
});

it('signs out and empties the bag', function () {
    signInDemoCustomer();
    fillDemoCart(['MG-KK-1' => 1]);

    $this->post('/logout')->assertRedirect(route('shop.home'));

    expect(app(DemoCustomer::class)->isSignedIn())->toBeFalse();
    $this->get('/account')->assertRedirect(route('auth.login'));
    $this->get('/cart')->assertSee('Your bag is empty');
});

it('only signs out with a POST request', function () {
    signInDemoCustomer();

    $this->get('/logout')
        ->assertMethodNotAllowed();
});
