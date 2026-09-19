<?php

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\OrderPackage;
use App\Models\OrderPackageItem;
use App\Models\User;

/**
 * One label per box, each with its own package id, contents and pickup code
 * (ADR-021). Reprinting a single box must not leak the other boxes' codes.
 */
function packedOrder(int $boxes = 2): Order
{
    $order = Order::factory()->create();

    for ($sequence = 1; $sequence <= $boxes; $sequence++) {
        $item = OrderItem::factory()->create([
            'order_id' => $order->id,
            'product_name' => "Item for box {$sequence}",
        ]);

        $package = OrderPackage::factory()->create([
            'order_id' => $order->id,
            'package_id' => "PKG-TEST-{$sequence}",
            'sequence' => $sequence,
            'pickup_code' => str_pad((string) (100000 + $sequence), 6, '0'),
        ]);

        OrderPackageItem::factory()->create([
            'order_package_id' => $package->id,
            'order_item_id' => $item->id,
            'quantity' => 1,
        ]);
    }

    return $order;
}

it('prints one label per box, each with its own package, contents and pickup code', function () {
    $this->actingAs(User::factory()->admin()->create());
    $order = packedOrder(boxes: 2);

    $response = $this->get(route('admin.print.label', $order))->assertOk();

    $response->assertSee('PKG-TEST-1')->assertSee('PKG-TEST-2');
    $response->assertSee('100001')->assertSee('100002');
    $response->assertSee('Item for box 1')->assertSee('Item for box 2');
    $response->assertSeeInOrder(['Box 1 of 2', 'Box 2 of 2']);
});

it('reprints a single box without the other boxes', function () {
    $this->actingAs(User::factory()->admin()->create());
    $order = packedOrder(boxes: 3);

    $response = $this->get(route('admin.print.label', [$order, 'box' => 2]))->assertOk();

    $response->assertSee('Box 2 of 3')
        ->assertSee('PKG-TEST-2')
        ->assertSee('100002')
        ->assertDontSee('PKG-TEST-1')
        ->assertDontSee('PKG-TEST-3')
        ->assertDontSee('100003');
});

it('clamps a box number that is out of range', function () {
    $this->actingAs(User::factory()->admin()->create());
    $order = packedOrder(boxes: 2);

    $this->get(route('admin.print.label', [$order, 'box' => 99]))
        ->assertOk()
        ->assertSee('Box 2 of 2');

    $this->get(route('admin.print.label', [$order, 'box' => 0]))
        ->assertOk()
        ->assertSee('Box 1 of 2');
});

it('prints a single label for an order that was never packed into boxes', function () {
    $this->actingAs(User::factory()->admin()->create());
    $order = Order::factory()->create();
    OrderItem::factory()->create(['order_id' => $order->id, 'product_name' => 'Loose item']);

    $this->get(route('admin.print.label', $order))
        ->assertOk()
        ->assertSee('Loose item');
});

it('keeps labels and invoices away from anyone who is not an admin', function () {
    $order = packedOrder(boxes: 1);

    $this->get(route('admin.print.label', $order))->assertRedirect();

    $this->actingAs(User::factory()->deliveryPartner()->create());
    $this->get(route('admin.print.label', $order))->assertForbidden();
    $this->get(route('admin.print.invoice', $order))->assertForbidden();
});
