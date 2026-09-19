<?php

use App\Actions\Inventory\AdjustStock;
use App\Enums\InventoryMovementType;
use App\Models\InventoryMovement;
use App\Models\ProductVariant;
use App\Models\User;

it('moves stock and records why', function () {
    $this->actingAs($admin = User::factory()->admin()->create());
    $variant = ProductVariant::factory()->create(['stock_quantity' => 10]);

    $after = app(AdjustStock::class)->handle($variant, 5, InventoryMovementType::Restock, 'New delivery');

    expect($after)->toBe(15)
        ->and($variant->fresh()->stock_quantity)->toBe(15)
        ->and(InventoryMovement::sole())
        ->quantity_change->toBe(5)
        ->stock_after->toBe(15)
        ->note->toBe('New delivery')
        ->user_id->toBe($admin->id);
});

it('updates the model it was handed, so the screen shows the new number', function () {
    $variant = ProductVariant::factory()->create(['stock_quantity' => 4]);

    app(AdjustStock::class)->handle($variant, -1, InventoryMovementType::Adjustment);

    expect($variant->stock_quantity)->toBe(3);
});

it('never takes stock below zero', function () {
    $variant = ProductVariant::factory()->create(['stock_quantity' => 2]);

    $after = app(AdjustStock::class)->handle($variant, -10, InventoryMovementType::Adjustment, 'Damaged');

    expect($after)->toBe(0)
        ->and(InventoryMovement::sole()->stock_after)->toBe(0);
});

it('works from a stale model rather than writing back an old total', function () {
    $variant = ProductVariant::factory()->create(['stock_quantity' => 10]);

    // Someone else adjusts the same variant; this copy still says 10.
    ProductVariant::whereKey($variant->getKey())->update(['stock_quantity' => 3]);

    $after = app(AdjustStock::class)->handle($variant, 1, InventoryMovementType::Restock);

    expect($after)->toBe(4);
});

it('keeps every movement rather than overwriting the last', function () {
    $variant = ProductVariant::factory()->create(['stock_quantity' => 0]);
    $action = app(AdjustStock::class);

    $action->handle($variant, 10, InventoryMovementType::Restock);
    $action->handle($variant, -3, InventoryMovementType::Sale);
    $action->handle($variant, -2, InventoryMovementType::Adjustment);

    expect(InventoryMovement::count())->toBe(3)
        ->and($variant->fresh()->stock_quantity)->toBe(5)
        ->and(InventoryMovement::latest('id')->first()->stock_after)->toBe(5);
});
