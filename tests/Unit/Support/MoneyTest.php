<?php

use App\Support\Money;

it('formats paise as rupees with Indian digit grouping', function (int $paise, string $expected) {
    expect(Money::format($paise))->toBe($expected);
})->with([
    'whole rupees drop the decimals' => [19900, '₹199'],
    'paise keep two decimals' => [19950, '₹199.50'],
    'lakhs use Indian grouping' => [12500050, '₹1,25,000.50'],
    'single paisa' => [1, '₹0.01'],
    'zero' => [0, '₹0'],
]);

it('returns the whole-number discount between MRP and price', function (int $mrp, int $price, int $expected) {
    expect(Money::discountPercent($mrp, $price))->toBe($expected);
})->with([
    'rounds down' => [49900, 34900, 30],
    'no discount when price equals MRP' => [52000, 52000, 0],
    'no discount when price is above MRP' => [50000, 60000, 0],
    'no discount without an MRP' => [0, 10000, 0],
    'just under one percent rounds to zero' => [100000, 99100, 0],
]);
