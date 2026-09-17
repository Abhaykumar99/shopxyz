<?php

use App\Livewire\Cart\CartCount;
use App\Livewire\Cart\CartPage;
use App\Support\Demo\DemoCart;
use Livewire\Livewire;

it('shows an empty bag with a way back to the shop', function () {
    $this->get('/cart')
        ->assertOk()
        ->assertSee('Your bag is empty')
        ->assertSee('Start shopping');
});

it('lists bag lines with the order summary', function () {
    fillDemoCart(['BB-LIP-1' => 2, 'MG-KK-1' => 1]);

    Livewire::test(CartPage::class)
        ->assertSee('Velvet matte lipstick')
        ->assertSee('Kaju katli with silver leaf')
        ->assertSee('₹978')
        ->assertSee('Free')
        ->assertSet('quantities', ['BB-LIP-1' => 2, 'MG-KK-1' => 1]);
});

it('updates a quantity and tells the counters', function () {
    fillDemoCart(['MG-KK-1' => 1]);

    Livewire::test(CartPage::class)
        ->set('quantities.MG-KK-1', 3)
        ->assertDispatched('cart-updated')
        ->assertSet('quantities.MG-KK-1', 3);

    expect(app(DemoCart::class)->quantityOf('MG-KK-1'))->toBe(3);
});

it('limits a quantity to the stock and says so', function () {
    fillDemoCart(['GL-VITC-1' => 1]);

    Livewire::test(CartPage::class)
        ->set('quantities.GL-VITC-1', 8)
        ->assertSet('quantities.GL-VITC-1', 3)
        ->assertDispatched('toast', tone: 'warning');
});

it('removes a line', function () {
    fillDemoCart(['MG-KK-1' => 1, 'BB-KAJ-1' => 1]);

    Livewire::test(CartPage::class)
        ->call('remove', 'MG-KK-1')
        ->assertDispatched('cart-updated')
        ->assertDontSee('Kaju katli with silver leaf')
        ->assertSee('Smudge-proof kajal');
});

it('ignores quantity changes for items that are not in the bag', function () {
    fillDemoCart(['MG-KK-1' => 1]);

    Livewire::test(CartPage::class)->set('quantities.BB-LIP-1', 4);

    expect(app(DemoCart::class)->quantityOf('BB-LIP-1'))->toBe(0);
});

it('shows how much more is needed for free delivery', function () {
    fillDemoCart(['BB-KAJ-1' => 1]);

    Livewire::test(CartPage::class)
        ->assertSee('Add <strong class="figures">₹350</strong> more for <strong>free delivery</strong>', false);
});

it('blocks checkout when a line is no longer available', function () {
    session()->put('demo.cart', ['GL-VITC-1' => 5]);

    Livewire::test(CartPage::class)
        ->assertSee('Some items are no longer available')
        ->assertSee('Only 3 left. Lower the quantity to continue.')
        ->assertDontSee('href="'.route('checkout.show').'"', false);
});

it('blocks checkout below the minimum order', function () {
    config(['shop.delivery.min_order_paise' => 50000]);
    app()->forgetScopedInstances();
    fillDemoCart(['BB-KAJ-1' => 1]);

    Livewire::test(CartPage::class)
        ->assertSee('The minimum order is ₹500')
        ->assertDontSee('href="'.route('checkout.show').'"', false);
});

it('counts bag items in the header', function () {
    fillDemoCart(['BB-KAJ-1' => 2, 'MG-KK-1' => 1]);

    Livewire::test(CartCount::class)->assertSee('aria-label="Your bag (3)"', false);
});
