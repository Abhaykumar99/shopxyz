<?php

use App\Models\Address;
use App\Models\Order;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;

it('lets a customer reach only their own address', function () {
    $customer = User::factory()->googleCustomer()->create();
    $mine = Address::factory()->for($customer)->create();
    $theirs = Address::factory()->for(User::factory()->googleCustomer())->create();

    expect(Gate::forUser($customer)->allows('view', $mine))->toBeTrue()
        ->and(Gate::forUser($customer)->allows('update', $mine))->toBeTrue()
        ->and(Gate::forUser($customer)->allows('delete', $mine))->toBeTrue()
        ->and(Gate::forUser($customer)->allows('view', $theirs))->toBeFalse()
        ->and(Gate::forUser($customer)->allows('update', $theirs))->toBeFalse()
        ->and(Gate::forUser($customer)->allows('delete', $theirs))->toBeFalse();
});

it('keeps staff and closed accounts out of the address book', function () {
    $address = Address::factory()->for(User::factory()->googleCustomer())->create();

    expect(Gate::forUser(User::factory()->admin()->create())->allows('view', $address))->toBeFalse()
        ->and(Gate::forUser(User::factory()->deliveryPartner()->create())->allows('view', $address))->toBeFalse();

    $owner = $address->user;
    $owner->forceFill(['is_active' => false])->save();

    expect(Gate::forUser($owner->fresh())->allows('view', $address))->toBeFalse();
});

it('shows an order to the customer who placed it and to the shop', function () {
    $customer = User::factory()->googleCustomer()->create();
    $order = Order::factory()->for($customer, 'customer')->create();
    $stranger = User::factory()->googleCustomer()->create();
    $admin = User::factory()->admin()->create();
    $partner = User::factory()->deliveryPartner()->create();

    expect(Gate::forUser($customer)->allows('view', $order))->toBeTrue()
        ->and(Gate::forUser($admin)->allows('view', $order))->toBeTrue()
        ->and(Gate::forUser($stranger)->allows('view', $order))->toBeFalse()
        ->and(Gate::forUser($partner)->allows('view', $order))->toBeFalse();
});

it('lets only the shop print a label or an invoice', function () {
    $customer = User::factory()->googleCustomer()->create();
    $order = Order::factory()->for($customer, 'customer')->create();

    expect(Gate::forUser(User::factory()->admin()->create())->allows('print', $order))->toBeTrue()
        ->and(Gate::forUser($customer)->allows('print', $order))->toBeFalse();
});

it('streams a payment proof to the shop only', function () {
    Storage::fake('local');
    Storage::disk('local')->put('proofs/one.jpg', 'not-really-an-image');

    $payment = Payment::factory()->create(['proof_path' => 'proofs/one.jpg']);

    $this->actingAs(User::factory()->admin()->create())
        ->get(route('admin.payment-proof', $payment))
        ->assertOk()
        ->assertHeader('X-Content-Type-Options', 'nosniff');

    foreach (['googleCustomer', 'deliveryPartner'] as $state) {
        $this->actingAs(User::factory()->{$state}()->create())
            ->get(route('admin.payment-proof', $payment))
            ->assertForbidden();
    }
});
