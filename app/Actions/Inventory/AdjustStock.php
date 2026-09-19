<?php

namespace App\Actions\Inventory;

use App\Enums\InventoryMovementType;
use App\Models\InventoryMovement;
use App\Models\ProductVariant;
use Illuminate\Support\Facades\DB;

/**
 * Changes a variant's stock and records why.
 *
 * The row is locked for the length of the transaction, so two people counting
 * the same shelf at the same moment cannot both read the old number and write
 * back a total that loses one of their changes. `inventory_movements` is
 * append-only: the running total is always reconstructable from it.
 */
final class AdjustStock
{
    public function handle(
        ProductVariant $variant,
        int $change,
        InventoryMovementType $type,
        ?string $note = null,
        ?int $orderId = null,
    ): int {
        return DB::transaction(function () use ($variant, $change, $type, $note, $orderId): int {
            // Re-read under a lock: whatever this model held a moment ago may be
            // out of date by now.
            $locked = ProductVariant::query()->lockForUpdate()->findOrFail($variant->getKey());

            $after = max(0, $locked->stock_quantity + $change);
            $locked->update(['stock_quantity' => $after]);

            InventoryMovement::create([
                'product_variant_id' => $locked->getKey(),
                'order_id' => $orderId,
                'user_id' => auth()->id(),
                'type' => $type,
                'quantity_change' => $change,
                'stock_after' => $after,
                'note' => $note ?: null,
            ]);

            $variant->setAttribute('stock_quantity', $after)->syncOriginalAttribute('stock_quantity');

            return $after;
        });
    }
}
