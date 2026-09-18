<?php

use App\Enums\DeliveryStep;
use App\Livewire\Delivery\CashSummary;
use App\Livewire\Delivery\DeliveryHistory;
use App\Livewire\Delivery\DeliveryList;
use App\Livewire\Delivery\Profile;
use App\Support\Demo\DemoDeliveries;
use App\Support\Demo\DemoDeliveryBoy;
use Livewire\Livewire;

beforeEach(function () {
    signInDemoDeliveryBoy();
});

it('opens the round with what is left, what is done and the cash held', function () {
    $this->get('/delivery')
        ->assertOk()
        ->assertSee('Good to see you, Rahul')
        ->assertSee('To deliver')
        ->assertSee('ORD-10245')
        ->assertSee('Boring Road')
        ->assertSee('Finished today')
        ->assertSee('ORD-10243');
});

it('puts unfinished deliveries before finished ones', function () {
    $jobs = collect(app(DemoDeliveries::class)->today());

    expect($jobs->first()->isFinished())->toBeFalse()
        ->and($jobs->last()->isFinished())->toBeTrue();
});

it('accepts a new delivery from the list', function () {
    Livewire::test(DeliveryList::class)
        ->call('accept', 'ORD-10252')
        ->assertDispatched('toast', tone: 'success');

    expect(app(DemoDeliveries::class)->find('ORD-10252')->step)->toBe(DeliveryStep::Accepted);
});

it('shows every panel screen in the bottom navigation', function () {
    $this->get('/delivery')
        ->assertSee('href="'.route('delivery.cash').'"', false)
        ->assertSee('href="'.route('delivery.history').'"', false)
        ->assertSee('href="'.route('delivery.profile').'"', false)
        ->assertSee('aria-current="page"', false);
});

it('adds up the cash collected and what is still to hand over', function () {
    Livewire::test(CashSummary::class)
        ->assertSee('To hand over at the shop')
        ->assertSee('₹447')
        ->assertSee('ORD-10243')
        ->assertSee('Paid by UPI (no cash)');
});

it('counts a new cash delivery into the cash to hand over', function () {
    $deliveries = app(DemoDeliveries::class);
    $deliveries->deliver('ORD-10246', '905617', 139800);

    expect($deliveries->cash())
        ->collected->toBe(44700 + 139800)
        ->to_hand_over->toBe(44700 + 139800)
        ->deliveries->toBe(3);
});

it('lists finished deliveries with the day they happened', function () {
    Livewire::test(DeliveryHistory::class)
        ->assertSee('ORD-10243')
        ->assertSee('ORD-10198')
        ->assertSee('Could not deliver')
        ->assertSee('Yesterday');
});

it('searches the history by order number, customer or area', function () {
    Livewire::test(DeliveryHistory::class)
        ->set('search', '10198')
        ->assertSee('ORD-10198')
        ->assertDontSee('ORD-10243')
        ->set('search', 'digha')
        ->assertSee('ORD-10198')
        ->set('search', 'nothing here')
        ->assertSee('Nothing found');
});

it('shows the delivery boy their own details and today’s summary', function () {
    Livewire::test(Profile::class)
        ->assertSee('Rahul Kumar')
        ->assertSee('+91 90000 11111')
        ->assertSee('Boring Road and Bakerganj')
        ->assertSee('Cash to hand over');
});

it('signs out and returns to the sign-in screen', function () {
    Livewire::test(Profile::class)
        ->call('signOut')
        ->assertRedirect(route('delivery.login'));

    expect(app(DemoDeliveryBoy::class)->isSignedIn())->toBeFalse();
});
