<?php

use App\Enums\OrderStatus;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Support\Cart\Bag;
use App\Support\Demo\DemoOrders;

beforeEach(function () {
    seedCatalog();
});

it('places an order from the bag and empties the bag', function () {
    $customer = signInCustomerWithAddress();
    $cart = fillCart(['MG-KK-2' => 2]);

    $order = app(DemoOrders::class)->place($cart, $customer->addresses()->sole(), PaymentMethod::Upi, 'Ring the bell');

    expect($order->number)->toBe('ORD-10301')
        ->and($order->status)->toBe(OrderStatus::Placed)
        ->and($order->paymentStatus)->toBe(PaymentStatus::AwaitingProof)
        ->and($order->total())->toBe(104000)
        ->and($order->note)->toBe('Ring the bell')
        ->and(app(Bag::class)->isEmpty())->toBeTrue()
        ->and(app(DemoOrders::class)->find('ORD-10301'))->not->toBeNull();
});

it('cancels an order that has not been packed', function () {
    $orders = app(DemoOrders::class);

    $cancelled = $orders->cancel('ORD-10244');

    expect($cancelled)->toBeTrue()
        ->and($orders->find('ORD-10244')->status)->toBe(OrderStatus::Cancelled);
});

it('refuses to cancel an order that is on its way', function () {
    $orders = app(DemoOrders::class);

    expect($orders->cancel('ORD-10245'))->toBeFalse()
        ->and($orders->find('ORD-10245')->status)->toBe(OrderStatus::OutForDelivery);
});

it('moves a rejected UPI payment back to verification when new proof arrives', function () {
    $orders = app(DemoOrders::class);

    $accepted = $orders->submitPaymentProof('ORD-10247', '555555555555');

    expect($accepted)->toBeTrue()
        ->and($orders->find('ORD-10247'))
        ->paymentStatus->toBe(PaymentStatus::PendingVerification)
        ->utr->toBe('555555555555')
        ->rejectionReason->toBeNull();
});

it('ignores payment proof for orders that do not need it', function () {
    expect(app(DemoOrders::class)->submitPaymentProof('ORD-10245', '555555555555'))->toBeFalse();
});

it('detects a UTR already used on another order', function () {
    $orders = app(DemoOrders::class);

    expect($orders->utrInUse('412345678901', 'ORD-10247'))->toBeTrue()
        ->and($orders->utrInUse('412345678901', 'ORD-10248'))->toBeFalse();
});

it('shows the delivery code only while the parcel is on its way', function () {
    $orders = app(DemoOrders::class);

    expect($orders->find('ORD-10245')->visibleDeliveryCode())->toBe('482915')
        ->and($orders->find('ORD-10231')->visibleDeliveryCode())->toBeNull();
});
