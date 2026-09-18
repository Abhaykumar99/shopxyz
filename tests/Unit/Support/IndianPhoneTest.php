<?php

use App\Support\IndianPhone;

it('normalises typed mobile numbers to ten digits', function (string $input, string $expected) {
    expect(IndianPhone::normalize($input))->toBe($expected);
})->with([
    'spaces' => ['98300 12345', '9830012345'],
    'country code' => ['+91 98300-12345', '9830012345'],
    'country code without plus' => ['919830012345', '9830012345'],
    'trunk prefix' => ['09830012345', '9830012345'],
]);

it('accepts only Indian mobile numbers', function (string $input, bool $valid) {
    expect(IndianPhone::isValid($input))->toBe($valid);
})->with([
    'starts with 9' => ['9830012345', true],
    'starts with 6' => ['6000000000', true],
    'starts with 5' => ['5830012345', false],
    'nine digits' => ['983001234', false],
    'letters' => ['98300abcde', false],
    'too short' => ['12345', false],
    'eleven digits without a leading zero' => ['98300123456', false],
]);

it('formats a number for display', function () {
    expect(IndianPhone::format('9830012345'))->toBe('+91 98300 12345');
});
