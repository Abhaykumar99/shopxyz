<?php

use App\Enums\DeliveryFailureReason;
use App\Enums\DeliveryStep;
use App\Support\Demo\DemoDeliveries;
use App\Support\Demo\DemoDeliveryJob;

it('gives the delivery boy a round covering every step', function () {
    $steps = collect(app(DemoDeliveries::class)->today())->map(fn (DemoDeliveryJob $job): DeliveryStep => $job->step);

    expect($steps)->toContain(DeliveryStep::Assigned, DeliveryStep::Accepted, DeliveryStep::PickedUp, DeliveryStep::Reached, DeliveryStep::Delivered);
});

it('moves a delivery along the steps a tap can reach', function () {
    $deliveries = app(DemoDeliveries::class);

    // Accepting is a tap; collecting the boxes needs their pickup codes (ADR-021).
    expect($deliveries->advance('ORD-10252'))->toBe(DeliveryStep::Accepted)
        ->and($deliveries->advance('ORD-10252'))->toBeNull();

    $deliveries->verifyPickup('ORD-10252', 'PKG-10252-1', '640913');

    expect($deliveries->find('ORD-10252')->step)->toBe(DeliveryStep::PickedUp)
        ->and($deliveries->advance('ORD-10252'))->toBe(DeliveryStep::Reached)
        ->and($deliveries->advance('ORD-10252'))->toBeNull();
});

it('only counts an order as picked up when every box is verified', function () {
    $deliveries = app(DemoDeliveries::class);

    $first = $deliveries->verifyPickup('ORD-10250', 'PKG-10250-1', '204877');

    expect($first)->toMatchArray(['verified' => true, 'all_picked_up' => false])
        ->and($deliveries->find('ORD-10250')->step)->toBe(DeliveryStep::Accepted);

    $second = $deliveries->verifyPickup('ORD-10250', 'PKG-10250-2', '913526');

    expect($second['all_picked_up'])->toBeTrue()
        ->and($deliveries->find('ORD-10250')->step)->toBe(DeliveryStep::PickedUp);
});

it('refuses a pickup code that belongs to another box', function () {
    $deliveries = app(DemoDeliveries::class);

    $result = $deliveries->verifyPickup('ORD-10250', 'PKG-10250-1', '913526');

    expect($result)->toMatchArray(['verified' => false, 'attempts_left' => 2])
        ->and($deliveries->find('ORD-10250')->packages[0]->isPickedUp())->toBeFalse();
});

it('stops pickup after three wrong codes for the order', function () {
    $deliveries = app(DemoDeliveries::class);

    foreach (['000000', '111111', '222222'] as $wrong) {
        $deliveries->verifyPickup('ORD-10250', 'PKG-10250-1', $wrong);
    }

    expect($deliveries->pickupAttemptsLeft('ORD-10250'))->toBe(0)
        ->and($deliveries->verifyPickup('ORD-10250', 'PKG-10250-1', '204877')['verified'])->toBeFalse();
});

it('forgets the wrong tries once a box is verified', function () {
    $deliveries = app(DemoDeliveries::class);
    $deliveries->verifyPickup('ORD-10250', 'PKG-10250-1', '000000');
    $deliveries->verifyPickup('ORD-10250', 'PKG-10250-1', '204877');

    expect($deliveries->pickupAttemptsLeft('ORD-10250'))->toBe(DemoDeliveries::MAX_PICKUP_ATTEMPTS);
});

it('will not verify a box twice or before the delivery is accepted', function () {
    $deliveries = app(DemoDeliveries::class);

    expect($deliveries->verifyPickup('ORD-10252', 'PKG-10252-1', '640913')['verified'])->toBeFalse()
        ->and($deliveries->verifyPickup('ORD-10245', 'PKG-10245-1', '731408')['verified'])->toBeFalse();
});

it('counts the boxes a failed delivery has to take back', function () {
    $deliveries = app(DemoDeliveries::class);

    expect($deliveries->find('ORD-10198')->packagesToReturn())->toBe(1)
        ->and($deliveries->find('ORD-10245')->packagesToReturn())->toBe(0);
});

it('splits an order into the boxes it was packed into', function () {
    $job = app(DemoDeliveries::class)->find('ORD-10240');

    expect($job->packageCount())->toBe(3)
        ->and($job->packages[0]->label())->toBe('Box 1 of 3')
        ->and($job->packages[0]->contents())->toBe('4 × Welcome sweet boxes')
        ->and(collect($job->packages)->sum(fn ($package) => $package->itemCount()))->toBe($job->itemCount());
});

it('records the time of each step', function () {
    $deliveries = app(DemoDeliveries::class);
    $deliveries->advance('ORD-10252');

    expect($deliveries->find('ORD-10252')->timeFor(DeliveryStep::Accepted))->not->toBeNull();
});

it('will not move an unknown or finished delivery', function (string $number) {
    expect(app(DemoDeliveries::class)->advance($number))->toBeNull();
})->with(['unknown' => 'ORD-00000', 'already delivered' => 'ORD-10243', 'failed' => 'ORD-10198']);

it('only accepts the customer’s own delivery code', function () {
    $deliveries = app(DemoDeliveries::class);

    expect($deliveries->deliver('ORD-10246', '000000', 139800))->toBe(2)
        ->and($deliveries->find('ORD-10246')->step)->toBe(DeliveryStep::Reached)
        ->and($deliveries->deliver('ORD-10246', '905617', 139800))->toBeNull()
        ->and($deliveries->find('ORD-10246')->step)->toBe(DeliveryStep::Delivered);
});

it('stops after three wrong codes', function () {
    $deliveries = app(DemoDeliveries::class);

    foreach (range(1, 3) as $attempt) {
        $deliveries->deliver('ORD-10246', '000000', 139800);
    }

    expect($deliveries->codeAttemptsLeft('ORD-10246'))->toBe(0);
});

it('does not record cash for an order that was paid by UPI', function () {
    $deliveries = app(DemoDeliveries::class);
    $deliveries->advance('ORD-10252');
    $deliveries->advance('ORD-10252');
    $deliveries->advance('ORD-10252');
    $deliveries->deliver('ORD-10252', '318204', 149900);

    expect($deliveries->find('ORD-10252')->cashCollectedPaise)->toBe(0)
        ->and($deliveries->cash()['collected'])->toBe(44700);
});

it('records a failure with its reason and note', function () {
    $deliveries = app(DemoDeliveries::class);

    expect($deliveries->fail('ORD-10246', DeliveryFailureReason::Refused, 'Customer changed their mind'))->toBeTrue();

    $job = $deliveries->find('ORD-10246');
    expect($job->step)->toBe(DeliveryStep::Failed)
        ->and($job->failureReason)->toBe(DeliveryFailureReason::Refused)
        ->and($job->failureNote)->toBe('Customer changed their mind')
        ->and($job->cashToHandOver())->toBe(0);
});

it('refuses to fail a delivery that has not been accepted or is finished', function (string $number) {
    expect(app(DemoDeliveries::class)->fail($number, DeliveryFailureReason::NobodyHome))->toBeFalse();
})->with(['not accepted' => 'ORD-10252', 'delivered' => 'ORD-10243', 'unknown' => 'ORD-00000']);

it('keeps cash handed over yesterday out of today’s total', function () {
    $cash = app(DemoDeliveries::class)->cash();

    expect($cash['collected'])->toBe(44700)
        ->and($cash['to_hand_over'])->toBe(44700)
        ->and($cash['upi_deliveries'])->toBe(1);
});

it('lists finished deliveries newest first, including earlier days', function () {
    $history = collect(app(DemoDeliveries::class)->history())->map(fn (DemoDeliveryJob $job): string => $job->number);

    expect($history->first())->toBe('ORD-10243')
        ->and($history)->toContain('ORD-10198', 'ORD-10231');
});

it('builds a maps link and a phone link for the address', function () {
    $job = app(DemoDeliveries::class)->find('ORD-10245');

    expect($job->mapsUrl())->toContain('Boring%20Road')
        ->and($job->callUrl())->toBe('tel:+919830012345');
});
