<?php

use App\Enums\CashSettlementStatus;
use App\Enums\DeliveryStep;
use App\Enums\InventoryMovementType;
use App\Enums\OrderStatus;
use App\Enums\PaymentAttemptStatus;
use App\Enums\PaymentStatus;
use App\Enums\UserRole;
use App\Filament\Resources\CodSettlements\Tables\CodSettlementsTable;
use App\Filament\Resources\DeliveryAssignments\Tables\DeliveryAssignmentsTable;
use App\Filament\Resources\Orders\Tables\OrdersTable;
use App\Filament\Resources\Payments\Tables\PaymentsTable;
use App\Filament\Resources\ProductVariants\Tables\ProductVariantsTable;
use App\Models\CodSettlement;
use App\Models\DeliveryAssignment;
use App\Models\Order;
use App\Models\Payment;
use App\Models\ProductVariant;
use App\Models\User;

function adminUser(): User
{
    $admin = User::factory()->admin()->create();
    test()->actingAs($admin);

    return $admin;
}

it('opens every admin screen', function (string $path) {
    adminUser();

    $this->get($path)->assertOk();
})->with([
    '/admin',
    '/admin/orders',
    '/admin/payments',
    '/admin/cod-settlements',
    '/admin/invoices',
    '/admin/delivery-assignments',
    '/admin/wholesale-enquiries',
    '/admin/products',
    '/admin/categories',
    '/admin/product-variants',
    '/admin/customers',
    '/admin/delivery-partners',
    '/admin/banners',
    '/admin/home-sections',
    '/admin/reports',
    '/admin/settings',
]);

it('moves an order along its status journey and records who did it', function () {
    $admin = adminUser();
    $order = Order::factory()->create(['status' => OrderStatus::Placed]);

    OrdersTable::moveTo($order, OrderStatus::Confirmed);

    expect($order->fresh()->status)->toBe(OrderStatus::Confirmed)
        ->and($order->fresh()->confirmed_at)->not->toBeNull()
        ->and($order->statusHistories()->latest('id')->first())
        ->to_status->toBe(OrderStatus::Confirmed)
        ->changed_by->toBe($admin->id);
});

it('refuses a status jump the order status machine does not allow', function () {
    adminUser();
    $order = Order::factory()->create(['status' => OrderStatus::Placed]);

    OrdersTable::moveTo($order, OrderStatus::Delivered);

    expect($order->fresh()->status)->toBe(OrderStatus::Placed)
        ->and($order->statusHistories()->count())->toBe(0);
});

it('verifies a UPI payment and lets the order be confirmed', function () {
    $admin = adminUser();
    $order = Order::factory()->upi()->create(['payment_status' => PaymentStatus::PendingVerification]);
    $payment = Payment::factory()->create(['order_id' => $order->id, 'amount_paise' => $order->total_paise]);

    PaymentsTable::verify($payment);

    expect($payment->fresh())
        ->status->toBe(PaymentAttemptStatus::Verified)
        ->verified_by->toBe($admin->id)
        ->and($order->fresh()->payment_status)->toBe(PaymentStatus::Verified);
});

it('rejects a UPI payment with a reason the customer can act on', function () {
    adminUser();
    $order = Order::factory()->upi()->create(['payment_status' => PaymentStatus::PendingVerification]);
    $payment = Payment::factory()->create(['order_id' => $order->id]);

    PaymentsTable::reject($payment, 'We could not find this UTR.');

    expect($payment->fresh())
        ->status->toBe(PaymentAttemptStatus::Rejected)
        ->rejection_reason->toBe('We could not find this UTR.')
        ->and($order->fresh()->payment_status)->toBe(PaymentStatus::Rejected);
});

it('counts a cash handover and settles it when the money matches', function () {
    $admin = adminUser();
    $settlement = CodSettlement::factory()->create(['amount_paise' => 44700]);

    CodSettlementsTable::settle($settlement, 44700, null);

    expect($settlement->fresh())
        ->status->toBe(CashSettlementStatus::Settled)
        ->counted_paise->toBe(44700)
        ->verified_by->toBe($admin->id);
});

it('records a short count rather than hiding it', function () {
    adminUser();
    $settlement = CodSettlement::factory()->create(['amount_paise' => 44700]);

    CodSettlementsTable::settle($settlement, 40000, 'Two hundred short, partner will bring it tomorrow.');

    expect($settlement->fresh())
        ->status->toBe(CashSettlementStatus::Short)
        ->and($settlement->fresh()->differencePaise())->toBe(-4700);
});

it('reassigns a delivery to another partner with a fresh OTP', function () {
    adminUser();
    $first = User::factory()->deliveryPartner()->create();
    $second = User::factory()->deliveryPartner()->create();
    $assignment = DeliveryAssignment::factory()->create([
        'user_id' => $first->id,
        'step' => DeliveryStep::Accepted,
        'otp' => '111111',
    ]);

    DeliveryAssignmentsTable::reassign($assignment, $second->id);

    $fresh = DeliveryAssignment::where('order_id', $assignment->order_id)->where('is_active', true)->first();

    expect($assignment->fresh()->is_active)->toBeFalse()
        ->and($fresh->user_id)->toBe($second->id)
        ->and($fresh->step)->toBe(DeliveryStep::Assigned)
        ->and($fresh->otpMatches('111111'))->toBeFalse();
});

it('adjusts stock and writes a movement', function () {
    $admin = adminUser();
    $variant = ProductVariant::factory()->create(['stock_quantity' => 4]);

    $after = ProductVariantsTable::adjust($variant, 20, InventoryMovementType::Restock, 'New batch');

    expect($after)->toBe(24)
        ->and($variant->fresh()->stock_quantity)->toBe(24)
        ->and($variant->inventoryMovements()->latest('id')->first())
        ->quantity_change->toBe(20)
        ->stock_after->toBe(24)
        ->type->toBe(InventoryMovementType::Restock)
        ->user_id->toBe($admin->id);
});

it('never takes stock below zero', function () {
    adminUser();
    $variant = ProductVariant::factory()->create(['stock_quantity' => 3]);

    expect(ProductVariantsTable::adjust($variant, -10, InventoryMovementType::Adjustment, 'Damaged'))->toBe(0);
});

it('keeps customers and delivery partners in their own lists', function () {
    adminUser();
    $customer = User::factory()->googleCustomer()->create(['name' => 'Priya Sharma']);
    $partner = User::factory()->deliveryPartner()->create(['name' => 'Rahul Kumar']);

    $this->get('/admin/customers')->assertSee('Priya Sharma')->assertDontSee('Rahul Kumar');
    $this->get('/admin/delivery-partners')->assertSee('Rahul Kumar')->assertDontSee('Priya Sharma');

    expect($customer->role)->toBe(UserRole::Customer)
        ->and($partner->role)->toBe(UserRole::Delivery);
});
