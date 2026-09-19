<?php

use App\Livewire\Account\OrderList;
use Livewire\Livewire;

beforeEach(function () {
    signInCustomer();
});

it('shows orders in progress first, newest first', function () {
    Livewire::test(OrderList::class)
        ->assertSeeInOrder(['ORD-10248', 'ORD-10245', 'ORD-10247', 'ORD-10244', 'ORD-10198'])
        ->assertDontSee('ORD-10231')
        ->assertSee('Pay now');
});

it('switches to past orders', function () {
    Livewire::test(OrderList::class)
        ->set('show', 'past')
        ->assertSeeInOrder(['ORD-10231', 'ORD-10150'])
        ->assertDontSee('ORD-10245');
});

it('treats an unknown filter as orders in progress', function () {
    Livewire::withQueryParams(['show' => 'everything'])
        ->test(OrderList::class)
        ->assertSee('ORD-10245')
        ->assertDontSee('ORD-10231');
});
