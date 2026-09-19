<?php

use App\Support\Demo\DemoWholesale;

beforeEach(function () {
    seedCatalog();
});

it('prices each quantity at its slab', function (int $quantity, int $expected) {
    expect(DemoWholesale::item('MG-KK-3')->unitPriceFor($quantity))->toBe($expected);
})->with([
    'minimum' => [5, 92000],
    'just below the next slab' => [19, 92000],
    'second slab' => [20, 88000],
    'top slab' => [50, 84000],
    'far above the top slab' => [900, 84000],
]);

it('never offers a wholesale price above the retail price', function () {
    foreach (DemoWholesale::items() as $item) {
        expect($item->slabs[0]['paise'])->toBeLessThan($item->variant->paise);
    }
});

it('lists slabs in ascending order with falling prices', function () {
    foreach (DemoWholesale::items() as $item) {
        $mins = array_column($item->slabs, 'min');
        $prices = array_column($item->slabs, 'paise');

        expect($mins)->toBe(collect($mins)->sort()->values()->all())
            ->and($prices)->toBe(collect($prices)->sortDesc()->values()->all());
    }
});

it('labels each slab by its quantity range', function () {
    $item = DemoWholesale::item('MG-KK-3');

    expect([$item->slabRange(0), $item->slabRange(1), $item->slabRange(2)])->toBe(['5–19', '20–49', '50+']);
});

it('points at the next cheaper slab until the best one is reached', function () {
    $item = DemoWholesale::item('MG-KK-3');

    expect($item->slabAfter(5))->toBe(['min' => 20, 'paise' => 88000])
        ->and($item->unitsToNextSlab(5))->toBe(15)
        ->and($item->unitsToNextSlab(20))->toBe(30)
        ->and($item->slabAfter(50))->toBeNull()
        ->and($item->unitsToNextSlab(50))->toBe(0);
});

it('keeps a record of a quote request with the attached bag', function () {
    $cart = fillDemoCart(['MG-KK-3' => 20]);

    $reference = app(DemoWholesale::class)->submit(['business_name' => 'Sharma Sweets'], $cart->lines());

    expect($reference)->toBe('WQ-5101')
        ->and(app(DemoWholesale::class)->enquiry($reference))->toMatchArray([
            'reference' => 'WQ-5101',
            'estimate' => 20 * 88000,
        ]);
});
