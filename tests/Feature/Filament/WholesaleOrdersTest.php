<?php

use App\Filament\Resources\WholesaleOrders\Pages\ListWholesaleOrders;
use App\Filament\Resources\WholesaleOrders\Tables\WholesaleOrdersTable;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\User;
use Livewire\Livewire;

/**
 * Wholesale orders are ordinary orders, read the way a wholesale desk reads
 * them (ADR-019): only bulk orders, with what the buyer saved.
 */
function wholesaleOrder(): Order
{
    $order = Order::factory()->create(['has_wholesale_items' => true]);

    OrderItem::factory()->create([
        'order_id' => $order->id,
        'quantity' => 24,
        'mrp_paise' => 20000,
        'unit_price_paise' => 15000,
        'line_total_paise' => 24 * 15000,
        'is_wholesale' => true,
    ]);

    return $order->load('items');
}

it('shows bulk orders and leaves retail orders out', function () {
    $this->actingAs(User::factory()->admin()->create());

    $wholesale = wholesaleOrder();
    $retail = Order::factory()->create(['has_wholesale_items' => false]);

    Livewire::test(ListWholesaleOrders::class)
        ->assertCanSeeTableRecords([$wholesale])
        ->assertCanNotSeeTableRecords([$retail]);
});

it('works out what the buyer saved against retail prices', function () {
    $order = wholesaleOrder();

    expect(WholesaleOrdersTable::mrpPaise($order))->toBe(24 * 20000)
        ->and(WholesaleOrdersTable::savingPaise($order))->toBe(24 * 5000);
});

it('never reports a negative saving when the price is above MRP', function () {
    $order = Order::factory()->create(['has_wholesale_items' => true]);

    OrderItem::factory()->create([
        'order_id' => $order->id,
        'quantity' => 2,
        'mrp_paise' => 10000,
        'unit_price_paise' => 12000,
        'line_total_paise' => 24000,
        'is_wholesale' => true,
    ]);

    expect(WholesaleOrdersTable::savingPaise($order->load('items')))->toBe(0);
});

it('counts the boxes an order was packed into', function () {
    $this->actingAs(User::factory()->admin()->create());
    $order = wholesaleOrder();
    $order->packages()->createMany([
        ['package_id' => 'PKG-W-1', 'sequence' => 1, 'pickup_code' => '111111'],
        ['package_id' => 'PKG-W-2', 'sequence' => 2, 'pickup_code' => '222222'],
    ]);

    $record = Livewire::test(ListWholesaleOrders::class)
        ->instance()
        ->getTable()
        ->getRecords()
        ->firstWhere('id', $order->id);

    expect((int) $record->packages_count)->toBe(2);
});

it('is closed to anyone who is not an admin', function () {
    $this->actingAs(User::factory()->deliveryPartner()->create());

    $this->get('/admin/wholesale-orders')->assertForbidden();
});

it('only flags an order as wholesale when a line actually reached a price band', function () {
    $this->seed(Database\Seeders\DatabaseSeeder::class);

    $flagged = Order::query()->where('has_wholesale_items', true)->with('items')->get();

    expect($flagged)->not->toBeEmpty();

    foreach ($flagged as $order) {
        expect($order->items->where('is_wholesale', true))
            ->not->toBeEmpty("{$order->order_number} is flagged wholesale but has no wholesale line");
    }
});
