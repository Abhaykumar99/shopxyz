<?php

use App\Support\Demo\DemoCart;
use App\Support\Demo\DemoWholesale;
use App\Support\ShopSettings;

beforeEach(function () {
    config([
        'shop.delivery.charge_paise' => 4000,
        'shop.delivery.free_above_paise' => 49900,
        'shop.delivery.min_order_paise' => 9900,
        'shop.payment.cod_max_paise' => 2000000,
    ]);
    app()->forgetScopedInstances();
});

it('adds units up to the per-line limit', function () {
    $cart = app(DemoCart::class);

    $added = $cart->add('RS-MASC-1', 15);

    expect($added)->toBe(DemoCart::MAX_PER_LINE)
        ->and($cart->quantityOf('RS-MASC-1'))->toBe(DemoCart::MAX_PER_LINE);
});

it('never adds more than the stock on the shelf', function () {
    $cart = app(DemoCart::class);

    $first = $cart->add('BB-LIP-5', 1);
    $second = $cart->add('BB-LIP-5', 5);

    expect([$first, $second])->toBe([1, 1])
        ->and($cart->quantityOf('BB-LIP-5'))->toBe(2);
});

it('refuses out of stock and unknown items', function (string $sku) {
    $cart = app(DemoCart::class);

    expect($cart->add($sku))->toBe(0)
        ->and($cart->isEmpty())->toBeTrue();
})->with(['out of stock shade' => 'BB-LIP-3', 'unknown sku' => 'NOPE-1']);

it('removes a line when its quantity is set to zero', function () {
    $cart = fillDemoCart(['MG-KK-1' => 2]);

    $stored = $cart->setQuantity('MG-KK-1', 0);

    expect($stored)->toBe(0)
        ->and($cart->isEmpty())->toBeTrue();
});

it('charges delivery below the free-delivery threshold', function () {
    $cart = fillDemoCart(['MG-KK-1' => 1]);

    expect($cart->summary(app(ShopSettings::class)))->toMatchArray([
        'subtotal' => 28000,
        'delivery' => 4000,
        'total' => 32000,
        'free_delivery_shortfall' => 21900,
        'meets_minimum' => true,
        'items' => 1,
    ]);
});

it('delivers free at the threshold and totals discounts against MRP', function () {
    $cart = fillDemoCart(['BB-LIP-1' => 1, 'MG-KK-1' => 1]);

    expect($cart->summary(app(ShopSettings::class)))->toMatchArray([
        'mrp' => 77900,
        'subtotal' => 62900,
        'discount' => 15000,
        'delivery' => 0,
        'total' => 62900,
        'free_delivery_shortfall' => 0,
    ]);
});

it('flags a bag below the minimum order', function () {
    config(['shop.delivery.min_order_paise' => 50000]);
    app()->forgetScopedInstances();
    $cart = fillDemoCart(['BB-KAJ-1' => 1]);

    expect($cart->summary(app(ShopSettings::class))['meets_minimum'])->toBeFalse();
});

it('reports lines that exceed the current stock', function () {
    session()->put('demo.cart', ['GL-VITC-1' => 5]);

    expect(app(DemoCart::class)->hasUnavailableLines())->toBeTrue();
});

it('lets a wholesale product be ordered in bulk, past the retail line limit', function () {
    $cart = app(DemoCart::class);

    $added = $cart->add('MG-KK-3', 40);

    expect($added)->toBe(40)
        ->and($cart->lines()[0]->unitPrice())->toBe(88000)
        ->and($cart->lines()[0]->isWholesale())->toBeTrue();
});

it('caps a wholesale line at the largest order we handle', function () {
    $cart = app(DemoCart::class);

    expect($cart->setQuantity('MG-KK-3', DemoWholesale::MAX_QUANTITY + 1))->toBe(DemoWholesale::MAX_QUANTITY);
});

it('prices a small quantity of a wholesale product at retail', function () {
    $cart = fillDemoCart(['MG-KK-3' => 2]);

    expect($cart->lines()[0])
        ->isWholesale()->toBeFalse()
        ->unitPrice()->toBe(99000);
});

it('orders bulk quantities in rather than blocking them on shelf stock', function () {
    $cart = fillDemoCart(['MG-KK-3' => 500]);

    expect($cart->hasUnavailableLines())->toBeFalse()
        ->and($cart->lines()[0]->isMadeToOrder())->toBeTrue();
});

it('totals a bag of wholesale and retail lines and reports the wholesale saving', function () {
    $cart = fillDemoCart(['MG-KK-3' => 20, 'BB-LIP-1' => 1]);

    expect($cart->summary(app(ShopSettings::class)))->toMatchArray([
        'subtotal' => 20 * 88000 + 34900,
        'wholesale_lines' => 1,
        'wholesale_saving' => 20 * (99000 - 88000),
        'cod_available' => true,
        'items' => 21,
    ]);
});

it('withdraws cash on delivery above the ceiling', function () {
    $cart = fillDemoCart(['MG-KK-3' => 25]);

    expect($cart->summary(app(ShopSettings::class)))
        ->subtotal->toBe(25 * 88000)
        ->cod_available->toBeFalse();
});

it('offers cash on delivery up to the ceiling', function () {
    expect(fillDemoCart(['MG-KK-3' => 5])->summary(app(ShopSettings::class))['cod_available'])->toBeTrue();
});
