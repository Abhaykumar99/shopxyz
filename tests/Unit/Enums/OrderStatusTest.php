<?php

use App\Enums\OrderStatus;

it('allows only the documented next steps', function (OrderStatus $from, OrderStatus $to, bool $allowed) {
    expect($from->canTransitionTo($to))->toBe($allowed);
})->with([
    'placed to confirmed' => [OrderStatus::Placed, OrderStatus::Confirmed, true],
    'placed straight to delivered' => [OrderStatus::Placed, OrderStatus::Delivered, false],
    'packed to assigned' => [OrderStatus::Packed, OrderStatus::Assigned, true],
    'assigned back to packed when unassigned' => [OrderStatus::Assigned, OrderStatus::Packed, true],
    'out for delivery cannot be cancelled' => [OrderStatus::OutForDelivery, OrderStatus::Cancelled, false],
    'failed delivery can be reassigned' => [OrderStatus::DeliveryFailed, OrderStatus::Assigned, true],
    'delivered is final' => [OrderStatus::Delivered, OrderStatus::Cancelled, false],
]);

it('marks delivered and cancelled orders as final', function (OrderStatus $status, bool $final) {
    expect($status->isFinal())->toBe($final);
})->with([
    [OrderStatus::Delivered, true],
    [OrderStatus::Cancelled, true],
    [OrderStatus::DeliveryFailed, false],
    [OrderStatus::Placed, false],
]);

it('lets customers cancel only until the order is packed', function (OrderStatus $status, bool $cancellable) {
    expect($status->isCancellableByCustomer())->toBe($cancellable);
})->with([
    [OrderStatus::Placed, true],
    [OrderStatus::Confirmed, true],
    [OrderStatus::Packing, true],
    [OrderStatus::Packed, false],
    [OrderStatus::OutForDelivery, false],
    [OrderStatus::Cancelled, false],
]);

it('maps internal statuses onto the customer journey', function (OrderStatus $status, OrderStatus $step) {
    expect($status->journeyStep())->toBe($step);
})->with([
    [OrderStatus::Packing, OrderStatus::Confirmed],
    [OrderStatus::Assigned, OrderStatus::Packed],
    [OrderStatus::DeliveryFailed, OrderStatus::OutForDelivery],
    [OrderStatus::Delivered, OrderStatus::Delivered],
]);
