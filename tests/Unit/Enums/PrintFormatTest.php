<?php

use App\Enums\PrintDocument;
use App\Enums\PrintFormat;

test('labels can print on thermal, A5 and A4', function () {
    expect(PrintFormat::forDocument(PrintDocument::Label))
        ->toBe([PrintFormat::Thermal4x6, PrintFormat::A5, PrintFormat::A4]);
});

test('invoices can print on A4 and A5 but not on thermal labels', function () {
    expect(PrintFormat::forDocument(PrintDocument::Invoice))->toBe([PrintFormat::A4, PrintFormat::A5])
        ->and(PrintFormat::Thermal4x6->supports(PrintDocument::Invoice))->toBeFalse();
});

test('defaults are a thermal label and an A4 invoice', function () {
    expect(PrintFormat::defaultFor(PrintDocument::Label))->toBe(PrintFormat::Thermal4x6)
        ->and(PrintFormat::defaultFor(PrintDocument::Invoice))->toBe(PrintFormat::A4);
});

test('page sizes are portrait millimetres for the CSS page rule', function (PrintFormat $format, string $size) {
    expect($format->cssPageSize())->toBe($size);
})->with([
    [PrintFormat::Thermal4x6, '100mm 150mm'],
    [PrintFormat::A5, '148mm 210mm'],
    [PrintFormat::A4, '210mm 297mm'],
]);
