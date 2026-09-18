<?php

use App\Enums\DeliveryFailureReason;
use App\Enums\DeliveryStep;
use App\Enums\OrderStatus;

it('only changes the order status at the milestones the customer sees', function () {
    expect(DeliveryStep::Assigned->orderStatus())->toBeNull()
        ->and(DeliveryStep::Accepted->orderStatus())->toBeNull()
        ->and(DeliveryStep::PickedUp->orderStatus())->toBeNull()
        ->and(DeliveryStep::OutForDelivery->orderStatus())->toBe(OrderStatus::OutForDelivery)
        ->and(DeliveryStep::Delivered->orderStatus())->toBe(OrderStatus::Delivered)
        ->and(DeliveryStep::Failed->orderStatus())->toBe(OrderStatus::DeliveryFailed);
});

it('maps each step to an order status the order can actually reach', function () {
    $status = OrderStatus::Assigned;

    foreach ([DeliveryStep::OutForDelivery, DeliveryStep::Delivered] as $step) {
        $next = $step->orderStatus();
        expect($status->canTransitionTo($next))->toBeTrue();
        $status = $next;
    }
});

it('groups the round into the buckets the panel shows', function () {
    expect(collect(DeliveryStep::groups())->map(fn (DeliveryStep $step): string => $step->group())->all())
        ->toBe(['To pick up', 'Picked up', 'Out for delivery', 'Delivered', 'Failed delivery'])
        ->and(DeliveryStep::Assigned->group())->toBe('To pick up')
        ->and(DeliveryStep::Assigned->isBeforePickup())->toBeTrue()
        ->and(DeliveryStep::PickedUp->isBeforePickup())->toBeFalse();
});

it('walks from assigned to delivered and then stops', function () {
    $steps = [];
    $step = DeliveryStep::Assigned;

    while ($step !== null) {
        $steps[] = $step;
        $step = $step->next();
    }

    expect($steps)->toBe(DeliveryStep::journey())
        ->and(DeliveryStep::Delivered->isFinished())->toBeTrue()
        ->and(DeliveryStep::Failed->isFinished())->toBeTrue()
        ->and(DeliveryStep::Failed->next())->toBeNull();
});

it('offers a next action while there is work left', function (DeliveryStep $step, bool $hasAction) {
    expect($step->nextAction() !== null)->toBe($hasAction);
})->with([
    'new' => [DeliveryStep::Assigned, true],
    'accepted' => [DeliveryStep::Accepted, true],
    'picked up' => [DeliveryStep::PickedUp, true],
    'out for delivery' => [DeliveryStep::OutForDelivery, true],
    'delivered' => [DeliveryStep::Delivered, false],
    'failed' => [DeliveryStep::Failed, false],
]);

it('uses the shared status tones', function () {
    expect(collect(DeliveryStep::cases())->map(fn (DeliveryStep $step): string => $step->tone())->unique()->values()->all())
        ->each->toBeIn(['info', 'offer', 'brand', 'success', 'danger']);
});

it('explains every failure reason to the customer and asks for a note when needed', function (DeliveryFailureReason $reason) {
    expect($reason->label())->not->toBeEmpty()
        ->and($reason->customerMessage())->not->toBeEmpty()
        ->and($reason->needsNote())->toBe(in_array($reason, [DeliveryFailureReason::AddressWrong, DeliveryFailureReason::Rescheduled], true));
})->with(DeliveryFailureReason::cases());
