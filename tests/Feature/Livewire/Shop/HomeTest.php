<?php

use App\Livewire\Shop\Home;
use App\Models\Banner;
use App\Models\HomeSection;
use Livewire\Livewire;

beforeEach(function () {
    seedHomepage();
});

it('renders the home page from the blocks the admin arranged', function () {
    $this->get('/')
        ->assertOk()
        ->assertSeeLivewire(Home::class)
        ->assertSeeInOrder(['Sweets, beauty and gifts', 'Festive gifting', 'Shop by category', 'offers', 'Bestsellers', 'How ordering works'])
        ->assertSee('Festive hamper with brass diya');
});

it('leaves out a block that is switched off', function () {
    HomeSection::where('key', 'bestsellers')->update(['is_active' => false]);

    $this->get('/')->assertOk()->assertDontSee('Bestsellers');
});

it('leaves out a block whose dates have not started', function () {
    HomeSection::where('key', 'festive')->update(['starts_at' => now()->addWeek()]);

    $this->get('/')->assertOk()->assertDontSee('Festive gifting');
});

it('shows a banner only while it is scheduled', function () {
    $banner = Banner::where('placement', 'promo')->first();
    $banner->update(['ends_at' => now()->subDay()]);

    // The wording of the promotion card, which no hero slide repeats.
    $promoOnly = 'ordered through the same bag and checkout';

    $this->get('/')->assertOk()->assertDontSee($promoOnly);

    $banner->update(['ends_at' => now()->addWeek()]);

    $this->get('/')->assertOk()->assertSee($promoOnly);
});

it('puts the blocks in the order the admin set', function () {
    HomeSection::where('key', 'bestsellers')->update(['sort_order' => 15]);

    $this->get('/')->assertSeeInOrder(['Bestsellers', 'Festive gifting']);
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
