<?php

use App\Enums\CashSettlementStatus;
use App\Enums\DeliveryStep;
use App\Livewire\Delivery\CashHistory;
use App\Livewire\Delivery\CashSummary;
use App\Livewire\Delivery\DeliveryHistory;
use App\Livewire\Delivery\DeliveryList;
use App\Livewire\Delivery\Profile;
use App\Support\Demo\DemoCash;
use App\Support\Demo\DemoDeliveries;
use Livewire\Livewire;

beforeEach(function () {
    $this->partner = signInDeliveryPartner();
    $this->partner->forceFill(['name' => 'Rahul Kumar', 'phone' => '9000011111'])->save();
});

it('opens the round grouped into to pick up, picked up, out for delivery and delivered', function () {
    $this->get('/delivery')
        ->assertOk()
        ->assertSee('Good to see you, Rahul')
        ->assertSee('Cash with you')
        ->assertSeeInOrder(['To pick up', 'Picked up', 'Out for delivery', 'Delivered'])
        ->assertSee('ORD-10252')
        ->assertSee('ORD-10245')
        ->assertSee('ORD-10246')
        ->assertSee('ORD-10243');
});

it('counts each group and can show one at a time', function () {
    Livewire::test(DeliveryList::class)
        ->assertSee('To pick up (2)')
        ->assertSee('Picked up (1)')
        ->assertSee('Out for delivery (1)')
        ->set('filter', 'Out for delivery')
        ->assertSee('ORD-10246')
        ->assertDontSee('ORD-10250')
        ->set('filter', 'nonsense')
        ->assertSee('ORD-10250');
});

it('points at the next thing to do', function () {
    Livewire::test(DeliveryList::class)
        ->assertSee('Next: Accept this delivery')
        ->assertSee('ORD-10252');
});

it('accepts a new delivery from the list', function () {
    Livewire::test(DeliveryList::class)
        ->call('accept', 'ORD-10252')
        ->assertDispatched('toast', tone: 'success')
        ->assertSee('Enter pickup codes (0/1)');

    expect(app(DemoDeliveries::class)->find('ORD-10252')->step)->toBe(DeliveryStep::Accepted);
});

it('starts the delivery only once every box is picked up', function () {
    Livewire::test(DeliveryList::class)
        ->call('startDelivery', 'ORD-10250')
        ->assertNotDispatched('toast');

    expect(app(DemoDeliveries::class)->find('ORD-10250')->step)->toBe(DeliveryStep::Accepted);

    Livewire::test(DeliveryList::class)
        ->call('startDelivery', 'ORD-10245')
        ->assertDispatched('toast', tone: 'success');

    expect(app(DemoDeliveries::class)->find('ORD-10245')->step)->toBe(DeliveryStep::OutForDelivery);
});

it('shows how many boxes of an order are still at the shop', function () {
    $this->get('/delivery')->assertSee('2 boxes');

    app(DemoDeliveries::class)->verifyPickup('ORD-10250', 'PKG-10250-1', '204877');

    $this->get('/delivery')->assertSee('1/2 boxes');
});

it('shows every panel screen in the bottom navigation', function () {
    $this->get('/delivery')
        ->assertSee('href="'.route('delivery.cash').'"', false)
        ->assertSee('href="'.route('delivery.history').'"', false)
        ->assertSee('href="'.route('delivery.profile').'"', false)
        ->assertSee('aria-current="page"', false);
});

it('keeps the cash collected today with the delivery boy until it is handed over', function () {
    Livewire::test(CashSummary::class)
        ->assertSee('Cash with you')
        ->assertSee('₹447')
        ->assertSee('ORD-10243')
        ->assertSee('Nothing is waiting to be counted');
});

it('hands the cash from the round to the shop as one batch', function () {
    Livewire::test(CashSummary::class)
        ->call('handOver')
        ->assertDispatched('toast', tone: 'success')
        ->assertDispatched('close-modal', 'hand-over')
        ->assertSee('CS-2041')
        ->assertSee('With the shop, being checked');

    $cash = app(DemoCash::class);
    expect($cash->withYouTotal())->toBe(0)
        ->and($cash->awaitingVerificationTotal())->toBe(44700)
        ->and($cash->find('CS-2041')->orderCount())->toBe(1);
});

it('does nothing when there is no cash to hand over', function () {
    app(DemoCash::class)->handOver();

    Livewire::test(CashSummary::class)
        ->call('handOver')
        ->assertDispatched('toast', tone: 'info');
});

it('settles a batch once the shop has counted it', function () {
    app(DemoCash::class)->handOver();

    Livewire::test(CashSummary::class)
        ->call('markVerified', 'CS-2041')
        ->assertDispatched('toast', tone: 'success')
        ->assertSee('Settled today');

    expect(app(DemoCash::class)->find('CS-2041')->status)->toBe(CashSettlementStatus::Settled)
        ->and(app(DemoCash::class)->awaitingVerification())->toBe([]);
});

it('keeps every handover in the cash history', function () {
    app(DemoCash::class)->handOver();

    Livewire::test(CashHistory::class)
        ->assertSee('CS-2041')
        ->assertSee('CS-2039')
        ->assertSee('Counted Yesterday')
        ->assertSee('Settled')
        ->assertSee('ORD-10243');
});

it('does not count cash twice once it has been handed over', function () {
    $cash = app(DemoCash::class);
    $cash->handOver();

    expect($cash->handOver())->toBeNull()
        ->and($cash->withYou())->toBe([]);
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
        ->assertSee('Cash with you');
});

it('signs out and returns to the sign-in screen', function () {
    Livewire::test(Profile::class)
        ->call('signOut')
        ->assertRedirect(route('delivery.login'));

    expect(auth()->check())->toBeFalse();
});
