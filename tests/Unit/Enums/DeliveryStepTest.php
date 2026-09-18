<?php

use App\Enums\DeliveryFailureReason;
use App\Enums\DeliveryStep;
use App\Enums\OrderStatus;

it('only changes the order status at the milestones the customer sees', function () {
    expect(DeliveryStep::Assigned->orderStatus())->toBeNull()
        ->and(DeliveryStep::Accepted->orderStatus())->toBeNull()
        ->and(DeliveryStep::Reached->orderStatus())->toBeNull()
        ->and(DeliveryStep::PickedUp->orderStatus())->toBe(OrderStatus::OutForDelivery)
        ->and(DeliveryStep::Delivered->orderStatus())->toBe(OrderStatus::Delivered)
        ->and(DeliveryStep::Failed->orderStatus())->toBe(OrderStatus::DeliveryFailed);
});

it('maps each step to an order status the order can actually reach', function () {
    $status = OrderStatus::Assigned;

    foreach ([DeliveryStep::PickedUp, DeliveryStep::Delivered] as $step) {
        $next = $step->orderStatus();
        expect($status->canTransitionTo($next))->toBeTrue();
        $status = $next;
    }
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
    'on the way' => [DeliveryStep::PickedUp, true],
    'at the address' => [DeliveryStep::Reached, true],
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
