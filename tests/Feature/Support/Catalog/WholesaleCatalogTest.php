<?php

use App\Support\Catalog\WholesaleCatalog;
use App\Support\Catalog\WholesaleItem;

beforeEach(function () {
    seedCatalog();
});

it('prices each quantity at its band', function (int $quantity, int $expected) {
    expect(WholesaleCatalog::find('MG-KK-3')->unitPriceFor($quantity))->toBe($expected);
})->with([
    'minimum' => [5, 92000],
    'just below the next band' => [19, 92000],
    'second band' => [20, 88000],
    'top band' => [50, 84000],
    'far above the top band' => [900, 84000],
]);

it('never offers a wholesale price above the retail price', function () {
    foreach (WholesaleCatalog::query() as $item) {
        expect($item->slabs[0]['paise'])->toBeLessThan($item->variant->price_paise);
    }
});

it('lists bands in ascending order with falling prices', function () {
    foreach (WholesaleCatalog::query() as $item) {
        $mins = array_column($item->slabs, 'min');
        $prices = array_column($item->slabs, 'paise');

        expect($mins)->toBe(collect($mins)->sort()->values()->all())
            ->and($prices)->toBe(collect($prices)->sortDesc()->values()->all());
    }
});

it('labels each band by its quantity range', function () {
    $item = WholesaleCatalog::find('MG-KK-3');

    expect([$item->slabRange(0), $item->slabRange(1), $item->slabRange(2)])->toBe(['5–19', '20–49', '50+']);
});

it('points at the next cheaper band until the best one is reached', function () {
    $item = WholesaleCatalog::find('MG-KK-3');

    expect($item->slabAfter(5))->toBe(['min' => 20, 'paise' => 88000])
        ->and($item->unitsToNextSlab(5))->toBe(15)
        ->and($item->unitsToNextSlab(20))->toBe(30)
        ->and($item->slabAfter(50))->toBeNull()
        ->and($item->unitsToNextSlab(50))->toBe(0);
});

it('offers only products that actually have price bands', function () {
    $items = WholesaleCatalog::query();

    expect($items)->not->toBeEmpty()
        ->and($items->every(fn (WholesaleItem $item): bool => $item->slabs !== []))->toBeTrue()
        ->and(WholesaleCatalog::find('BB-LIP-5'))->toBeNull();
});

it('leads with the biggest saving on offer', function () {
    $featured = WholesaleCatalog::featured();

    expect($featured->bestSavingPercent())
        ->toBe(WholesaleCatalog::query()->max(fn (WholesaleItem $item): int => $item->bestSavingPercent()));
});

it('drops a product the shop switches off', function () {
    $item = WholesaleCatalog::find('MG-KK-3');
    $item->product->update(['is_active' => false]);

    expect(WholesaleCatalog::query()->contains(fn (WholesaleItem $i): bool => $i->sku() === 'MG-KK-3'))->toBeFalse();
});

it('filters by category and by search, both counting subcategories', function () {
    expect(WholesaleCatalog::query('confectionery'))->not->toBeEmpty()
        ->and(WholesaleCatalog::query(null, 'kaju')->pluck('variant.sku'))->toContain('MG-KK-3')
        ->and(WholesaleCatalog::query(null, 'nothing-matches-this'))->toBeEmpty();
});
