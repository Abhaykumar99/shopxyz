<?php

namespace App\Support\Demo;

use App\Support\ShopSettings;
use Illuminate\Contracts\Session\Session;

/**
 * TEMPORARY session-backed bag for the clickable prototype (Phase 2).
 * Replaced by the Cart model and Cart actions in Phase 5.
 */
final class DemoCart
{
    public const MAX_PER_LINE = 10;

    private const KEY = 'demo.cart';

    public function __construct(private readonly Session $session) {}

    /**
     * @return list<DemoCartLine>
     */
    public function lines(): array
    {
        $lines = [];

        foreach ($this->raw() as $sku => $quantity) {
            if ($found = DemoCatalog::findSku((string) $sku)) {
                $lines[] = new DemoCartLine($found[0], $found[1], $quantity);
            }
        }

        return $lines;
    }

    public function count(): int
    {
        return array_sum($this->raw());
    }

    public function quantityOf(string $sku): int
    {
        return $this->raw()[$sku] ?? 0;
    }

    /**
     * Adds units, capped by stock and the per-line limit. Returns how many were added.
     */
    public function add(string $sku, int $quantity = 1): int
    {
        $found = DemoCatalog::findSku($sku);

        if ($found === null || $quantity < 1 || ! $found[1]->inStock()) {
            return 0;
        }

        $current = $this->quantityOf($sku);
        $target = min($current + $quantity, self::MAX_PER_LINE, $found[1]->stock);
        $this->put($sku, max($current, $target));

        return max(0, $target - $current);
    }

    /**
     * Sets a line's quantity (capped). Zero or less removes the line. Returns the stored quantity.
     */
    public function setQuantity(string $sku, int $quantity): int
    {
        $found = DemoCatalog::findSku($sku);

        if ($found === null || $quantity < 1) {
            $this->remove($sku);

            return 0;
        }

        $quantity = min($quantity, self::MAX_PER_LINE, max(1, $found[1]->stock));
        $this->put($sku, $quantity);

        return $quantity;
    }

    public function remove(string $sku): void
    {
        $lines = $this->raw();
        unset($lines[$sku]);
        $this->session->put(self::KEY, $lines);
    }

    public function clear(): void
    {
        $this->session->forget(self::KEY);
    }

    public function isEmpty(): bool
    {
        return $this->raw() === [];
    }

    public function hasUnavailableLines(): bool
    {
        foreach ($this->lines() as $line) {
            if (! $line->isAvailable()) {
                return true;
            }
        }

        return false;
    }

    /**
     * @return array{mrp: int, subtotal: int, discount: int, delivery: int, total: int, free_delivery_shortfall: int, meets_minimum: bool, items: int}
     */
    public function summary(ShopSettings $shop): array
    {
        $lines = $this->lines();
        $subtotal = array_sum(array_map(fn (DemoCartLine $line): int => $line->total(), $lines));
        $mrp = array_sum(array_map(fn (DemoCartLine $line): int => $line->mrpTotal(), $lines));
        $delivery = $lines === [] ? 0 : $shop->deliveryChargeFor($subtotal);

        return [
            'mrp' => $mrp,
            'subtotal' => $subtotal,
            'discount' => $mrp - $subtotal,
            'delivery' => $delivery,
            'total' => $subtotal + $delivery,
            'free_delivery_shortfall' => $lines === [] ? 0 : $shop->freeDeliveryShortfall($subtotal),
            'meets_minimum' => $shop->meetsMinimumOrder($subtotal),
            'items' => $this->count(),
        ];
    }

    /**
     * @return array<string, int>
     */
    private function raw(): array
    {
        $lines = $this->session->get(self::KEY, []);

        return is_array($lines) ? array_map('intval', $lines) : [];
    }

    private function put(string $sku, int $quantity): void
    {
        $lines = $this->raw();
        $lines[$sku] = $quantity;
        $this->session->put(self::KEY, $lines);
    }
}
