<?php

use App\Livewire\Wholesale\WholesalePage;
use App\Support\Demo\DemoCart;
use App\Support\Demo\DemoWholesale;
use Livewire\Livewire;

it('shows the wholesale page with price slabs and the navigation tab marked current', function () {
    $response = $this->get('/wholesale');

    expect($response->getContent())->toMatch('#<a href="'.preg_quote(route('wholesale.index'), '#').'"\s+aria-current="page"#');
    $response
        ->assertOk()
        ->assertSee('Bulk sweets, gifts and beauty for your business.')
        ->assertSee('Kaju katli with silver leaf')
        ->assertSee('5 to 19')
        ->assertSee('50 or more')
        ->assertSee('₹840 each')
        ->assertSee('Minimum 5');
});

it('sells wholesale through the ordinary bag and checkout', function () {
    $this->get('/wholesale')
        ->assertSee('Add to bag')
        ->assertSee('How wholesale ordering works')
        ->assertSee('Check out as usual')
        ->assertDontSee('Add to enquiry');
});

it('offers the quote page as a separate, optional route', function () {
    $this->get('/wholesale')
        ->assertSee('href="'.route('wholesale.quote').'"', false)
        ->assertSee('Request a quote');
});

it('links to wholesale from every shop page', function () {
    $this->get('/')->assertSee('href="'.route('wholesale.index').'"', false);
    $this->get('/cart')->assertSee('Wholesale and bulk orders');
});

it('filters wholesale products by category and search', function () {
    Livewire::test(WholesalePage::class)
        ->set('category', 'cosmetics')
        ->assertSee('Smudge-proof kajal')
        ->assertDontSee('Assorted mithai box')
        ->set('category', '')
        ->set('search', 'diya')
        ->assertSee('Brass diya set')
        ->assertDontSee('Smudge-proof kajal');
});

it('ignores an unknown category', function () {
    Livewire::withQueryParams(['category' => 'cars'])
        ->test(WholesalePage::class)
        ->assertSee('Assorted mithai box')
        ->assertSee('Smudge-proof kajal');
});

it('starts every product at its minimum quantity', function () {
    Livewire::test(WholesalePage::class)
        ->assertSet('quantities.MG-KK-3', 5)
        ->assertSet('quantities.BB-KAJ-1', 24);
});

it('adds a bulk quantity to the bag at the slab price', function () {
    Livewire::test(WholesalePage::class)
        ->set('quantities.MG-KK-3', 25)
        ->call('addBulkToCart', 'MG-KK-3')
        ->assertDispatched('cart-updated')
        ->assertDispatched('toast', tone: 'success')
        ->assertSee('25 in your bag');

    expect(app(DemoCart::class)->quantityOf('MG-KK-3'))->toBe(25);
    expect(app(DemoCart::class)->lines()[0]->unitPrice())->toBe(88000);
});

it('raises a quantity below the minimum to the minimum', function () {
    Livewire::test(WholesalePage::class)
        ->set('quantities.MG-KK-3', 2)
        ->call('addBulkToCart', 'MG-KK-3')
        ->assertSet('quantities.MG-KK-3', 5)
        ->assertDispatched('toast', tone: 'info');

    expect(app(DemoCart::class)->quantityOf('MG-KK-3'))->toBe(5);
});

it('never takes more than the largest order we handle', function () {
    Livewire::test(WholesalePage::class)
        ->set('quantities.MG-KK-3', DemoWholesale::MAX_QUANTITY + 500)
        ->call('addBulkToCart', 'MG-KK-3');

    expect(app(DemoCart::class)->quantityOf('MG-KK-3'))->toBe(DemoWholesale::MAX_QUANTITY);
});

it('ignores unknown products', function () {
    Livewire::test(WholesalePage::class)
        ->call('addBulkToCart', 'NOPE-1');

    expect(app(DemoCart::class)->isEmpty())->toBeTrue();
});

it('shows what the typed quantity costs and the next slab', function () {
    Livewire::test(WholesalePage::class)
        ->set('quantities.MG-KK-3', 20)
        ->assertSee('₹880 each')
        ->assertSee('₹17,600')
        ->assertSee('add 30 more for ₹840 each');
});

it('offers a link to the bag once something is in it', function () {
    fillDemoCart(['MG-KK-3' => 20]);

    Livewire::test(WholesalePage::class)
        ->assertSee('Go to bag (20)')
        ->assertSee('20 in your bag');
});
