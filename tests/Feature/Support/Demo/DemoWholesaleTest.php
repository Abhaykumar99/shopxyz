<?php

use App\Support\Demo\DemoWholesale;

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

it('caps quantities at the maximum', function () {
    $wholesale = app(DemoWholesale::class);

    expect($wholesale->setQuantity('MG-KK-3', 50000))->toBe(DemoWholesale::MAX_QUANTITY);
});

it('estimates the enquiry total at slab prices', function () {
    $wholesale = app(DemoWholesale::class);
    $wholesale->setQuantity('MG-KK-3', 20);
    $wholesale->setQuantity('BB-KAJ-1', 24);

    expect($wholesale->estimate())->toBe(20 * 88000 + 24 * 12500);
});
