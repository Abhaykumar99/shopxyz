<?php

namespace App\Support\Demo;

use Carbon\CarbonImmutable;

/**
 * TEMPORARY (Phase 3): one packed box of an order (ADR-021).
 *
 * The shop packs an order into one or more boxes. Each box gets its own package
 * id and a **pickup code**, both printed on its label. The delivery boy types
 * that code at the counter to confirm they are carrying that box; the order only
 * goes out for delivery once every box is verified.
 *
 * The pickup code is never shown in the delivery panel: it is read off the label.
 * Replaced in Phase 8/9 by an `order_packages` table.
 */
final readonly class DemoPackage
{
    /**
     * @param  list<array{name: string, variant: string, quantity: int}>  $items
     */
    public function __construct(
        public string $id,
        public int $index,
        public int $total,
        public string $pickupCode,
        public array $items,
        public ?string $pickedUpAt = null,
    ) {}

    public function label(): string
    {
        return "Box {$this->index} of {$this->total}";
    }

    public function itemCount(): int
    {
        return array_sum(array_column($this->items, 'quantity'));
    }

    public function isPickedUp(): bool
    {
        return $this->pickedUpAt !== null;
    }

    public function pickedUpTime(): ?string
    {
        return $this->pickedUpAt === null ? null : CarbonImmutable::parse($this->pickedUpAt)->format('g:i a');
    }

    /**
     * A short "2 × Kaju katli, 1 × Brass diya set" summary for the packing list.
     */
    public function contents(): string
    {
        return collect($this->items)
            ->map(fn (array $item): string => $item['quantity'].' × '.$item['name'])
            ->join(', ');
    }
}
