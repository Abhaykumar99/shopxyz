<?php

use App\Enums\OrderStatus;
use App\Livewire\Account\OrderShow;
use App\Support\Demo\DemoCart;
use App\Support\Demo\DemoOrders;
use Livewire\Livewire;

beforeEach(function () {
    signInDemoCustomer();
});

it('shows an order on its way with the delivery code and partner', function () {
    $this->get(route('account.order', 'ORD-10245'))
        ->assertOk()
        ->assertSee('Your delivery code')
        ->assertSee('482915')
        ->assertSee('Rahul Kumar')
        ->assertSee('Keep ₹2,117 ready in cash')
        ->assertSeeInOrder(['Completed:', 'Order placed', 'Current step:', 'Out for delivery', 'Next:', 'Delivered']);
});

it('hides the delivery code once the order is delivered', function () {
    $this->get(route('account.order', 'ORD-10231'))
        ->assertOk()
        ->assertDontSee('Your delivery code')
        ->assertSee('Paid by UPI');
});

it('asks for new payment details when the UPI payment was not matched', function () {
    $this->get(route('account.order', 'ORD-10247'))
        ->assertSee('We couldn&#039;t match your payment', false)
        ->assertSee('Send payment details again')
        ->assertSee('href="'.route('orders.pay', 'ORD-10247').'"', false);
});

it('explains a failed delivery', function () {
    $this->get(route('account.order', 'ORD-10198'))
        ->assertSee('Delivery failed')
        ->assertSee('Nobody was home.');
});

it('returns 404 for an unknown order', function () {
    $this->get(route('account.order', 'ORD-99999'))->assertNotFound();
});

it('cancels an order before it is packed', function () {
    Livewire::test(OrderShow::class, ['order' => 'ORD-10244'])
        ->assertSee('Cancel order')
        ->call('cancel')
        ->assertDispatched('toast', tone: 'info')
        ->assertDontSee('Cancel order');

    expect(app(DemoOrders::class)->find('ORD-10244')->status)->toBe(OrderStatus::Cancelled);
});

it('does not cancel an order that is on its way', function () {
    Livewire::test(OrderShow::class, ['order' => 'ORD-10245'])
        ->assertDontSee('Cancel order')
        ->call('cancel')
        ->assertDispatched('toast', tone: 'warning');

    expect(app(DemoOrders::class)->find('ORD-10245')->status)->toBe(OrderStatus::OutForDelivery);
});

it('puts the items back in the bag with buy again', function () {
    Livewire::test(OrderShow::class, ['order' => 'ORD-10231'])
        ->call('buyAgain')
        ->assertRedirect(route('cart.show'));

    expect(app(DemoCart::class))
        ->quantityOf('UG-DRY-1')->toBe(1)
        ->quantityOf('KC-MUG-1')->toBe(2);
});

it('warns when nothing can be bought again', function () {
    $cart = app(DemoCart::class);
    $cart->add('MG-ML-2', 30);

    Livewire::test(OrderShow::class, ['order' => 'ORD-10198'])
        ->call('buyAgain')
        ->assertNoRedirect()
        ->assertDispatched('toast', tone: 'warning');
});
