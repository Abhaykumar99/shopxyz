<?php

use App\Support\Demo\DemoEnquiries;

beforeEach(function () {
    seedCatalog();
});

it('keeps a record of a quote request with the attached bag', function () {
    $bag = fillCart(['MG-KK-3' => 20]);

    $reference = app(DemoEnquiries::class)->submit(['business_name' => 'Sharma Sweets'], $bag->lines());

    expect($reference)->toBe('WQ-5101')
        ->and(app(DemoEnquiries::class)->enquiry($reference))->toMatchArray([
            'reference' => 'WQ-5101',
            'estimate' => 20 * 88000,
        ]);
});
