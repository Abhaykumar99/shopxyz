<?php

use App\Livewire\Shop\ProductShow;
use App\Support\Demo\DemoCart;
use Livewire\Livewire;

it('shows the product with its price, MRP and highlights', function () {
    $this->get('/p/velvet-matte-lipstick')
        ->assertOk()
        ->assertSee('<title>Velvet matte lipstick by Blush &amp; Bloom | ', false)
        ->assertSee('<meta property="og:type" content="product">', false)
        ->assertSee('₹349')
        ->assertSee('30% off')
        ->assertSee('Up to 8 hours wear');
});

it('returns 404 for an unknown product', function () {
    $this->get('/p/unicorn-lipstick')->assertNotFound();
});

it('starts on the first shade in stock and switches shades', function () {
    Livewire::test(ProductShow::class, ['product' => 'velvet-matte-lipstick'])
        ->assertSet('sku', 'BB-LIP-1')
        ->call('selectVariant', 'BB-LIP-2')
        ->assertSet('sku', 'BB-LIP-2')
        ->assertSee('Shade: <span class="font-normal">Mulberry</span>', false);
});

it('ignores a shade from another product', function () {
    Livewire::test(ProductShow::class, ['product' => 'velvet-matte-lipstick'])
        ->call('selectVariant', 'MG-KK-1')
        ->assertSet('sku', 'BB-LIP-1');
});

it('opens the shade from the link', function () {
    Livewire::withQueryParams(['option' => 'BB-LIP-4'])
        ->test(ProductShow::class, ['product' => 'velvet-matte-lipstick'])
        ->assertSet('sku', 'BB-LIP-4');
});

it('adds the chosen quantity of the chosen shade to the bag', function () {
    Livewire::test(ProductShow::class, ['product' => 'velvet-matte-lipstick'])
        ->call('selectVariant', 'BB-LIP-2')
        ->set('quantity', 3)
        ->call('add')
        ->assertDispatched('cart-updated');

    expect(app(DemoCart::class)->quantityOf('BB-LIP-2'))->toBe(3);
});

it('caps the quantity at the stock left', function () {
    Livewire::test(ProductShow::class, ['product' => 'velvet-matte-lipstick'])
        ->call('selectVariant', 'BB-LIP-5')
        ->set('quantity', 9)
        ->call('add');

    expect(app(DemoCart::class)->quantityOf('BB-LIP-5'))->toBe(2);
});

it('offers no purchase for an out of stock shade', function () {
    Livewire::test(ProductShow::class, ['product' => 'velvet-matte-lipstick'])
        ->call('selectVariant', 'BB-LIP-3')
        ->assertSee('Out of stock in this shade')
        ->assertDontSee('Buy now')
        ->call('buyNow')
        ->assertNoRedirect();

    expect(app(DemoCart::class)->isEmpty())->toBeTrue();
});

it('goes straight to the bag with buy now', function () {
    Livewire::test(ProductShow::class, ['product' => 'kaju-katli'])
        ->call('buyNow')
        ->assertRedirect(route('cart.show'));

    expect(app(DemoCart::class)->quantityOf('MG-KK-1'))->toBe(1);
});

it('shows bulk prices on a product that has wholesale slabs', function () {
    $this->get('/p/kaju-katli?option=MG-KK-3')
        ->assertOk()
        ->assertSee('Buying in bulk?')
        ->assertSee('5 to 19')
        ->assertSee('₹920 each')
        ->assertSee('Add 5 to bag')
        ->assertSee('href="'.route('wholesale.index').'"', false);
});

it('leaves retail-only products without a bulk block', function () {
    $this->get('/p/volume-mascara')->assertOk()->assertDontSee('Buying in bulk?');
});

it('adds the wholesale minimum from the product page', function () {
    Livewire::test(ProductShow::class, ['product' => 'kaju-katli'])
        ->set('sku', 'MG-KK-3')
        ->call('addWholesaleMinimum')
        ->assertDispatched('cart-updated');

    expect(app(DemoCart::class)->quantityOf('MG-KK-3'))->toBe(5);
});
