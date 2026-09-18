<?php

use App\Livewire\Shop\Home;
use Livewire\Livewire;

it('renders the home page with categories, festive picks, offers and bestsellers', function () {
    $this->get('/')
        ->assertOk()
        ->assertSeeLivewire(Home::class)
        ->assertSeeInOrder(['Sweets, beauty and gifts', 'Festive gifting', 'Shop by category', "Today's offers", 'Bestsellers', 'How ordering works'])
        ->assertSee('Festive hamper with brass diya');
});

it('adds a single-option product to the bag and updates the counters', function () {
    Livewire::test(Home::class)
        ->call('addToCart', 'DH-CND-1')
        ->assertDispatched('cart-updated')
        ->assertDispatched('toast', tone: 'success');

    $this->get('/cart')->assertSee('Scented candle trio');
});

it('warns instead of adding an out of stock product', function () {
    Livewire::test(Home::class)
        ->call('addToCart', 'DH-JAR-1')
        ->assertNotDispatched('cart-updated')
        ->assertDispatched('toast', tone: 'warning');
});
