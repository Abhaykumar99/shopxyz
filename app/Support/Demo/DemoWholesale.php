<?php

namespace App\Support\Demo;

use Carbon\CarbonImmutable;
use Illuminate\Contracts\Session\Session;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

/**
 * TEMPORARY wholesale catalogue and enquiry list (Phase 2, catalogue + enquiry model).
 * Replaced by wholesale slabs on products and a WholesaleEnquiry model later.
 */
final class DemoWholesale
{
    public const MAX_QUANTITY = 10000;

    public const BUSINESS_TYPES = [
        'retail' => 'Retail shop',
        'events' => 'Wedding or event planner',
        'corporate' => 'Corporate gifting',
        'hospitality' => 'Hotel, restaurant or café',
        'other' => 'Other',
    ];

    private const KEY = 'demo.wholesale';

    /** @var list<DemoWholesaleItem>|null */
    private static ?array $items = null;

    public function __construct(private readonly Session $session) {}

    /**
     * @return list<DemoWholesaleItem>
     */
    public static function items(): array
    {
        return self::$items ??= array_values(array_filter(array_map(
            function (array $row): ?DemoWholesaleItem {
                [$sku, $unit, $slabs] = $row;
                $found = DemoCatalog::findSku($sku);

                return $found ? new DemoWholesaleItem($found[0], $found[1], $unit, array_map(
                    fn (array $slab): array => ['min' => $slab[0], 'paise' => $slab[1]],
                    $slabs,
                )) : null;
            },
            [
                ['MG-KK-3', '1 kg box', [[5, 92000], [20, 88000], [50, 84000]]],
                ['MG-ASST-1', '500 g box', [[10, 45000], [50, 42000], [100, 39500]]],
                ['MG-ML-2', '500 g box', [[10, 31000], [50, 29000], [100, 27500]]],
                ['CL-TRF-1', 'box of 9', [[10, 40500], [50, 38000], [100, 36000]]],
                ['BH-TIN-1', '400 g tin', [[12, 30900], [48, 28900], [96, 26900]]],
                ['UG-DRY-1', '400 g box', [[10, 79900], [50, 74900], [100, 69900]]],
                ['UG-HMP-1', 'hamper', [[10, 134900], [25, 127900], [50, 119900]]],
                ['DH-DIYA-1', 'set of 4', [[12, 59900], [50, 55900], [100, 51900]]],
                ['DH-CND-1', 'set of 3', [[12, 79900], [50, 74900], [100, 69900]]],
                ['KC-MUG-1', 'mug', [[20, 32900], [50, 29900], [100, 26900]]],
                ['BB-LIP-1', 'lipstick', [[12, 29900], [60, 27900], [120, 25900]]],
                ['BB-KAJ-1', 'kajal', [[24, 12500], [100, 11500], [250, 10500]]],
            ],
        )));
    }

    public static function item(string $sku): ?DemoWholesaleItem
    {
        foreach (self::items() as $item) {
            if ($item->sku() === $sku) {
                return $item;
            }
        }

        return null;
    }

    /**
     * @return Collection<int, DemoWholesaleItem>
     */
    public static function query(?string $category = null, ?string $search = null): Collection
    {
        $terms = array_filter(explode(' ', Str::lower(trim((string) $search))));

        return collect(self::items())
            ->when($category, fn (Collection $items) => $items->filter(fn (DemoWholesaleItem $item): bool => $item->product->category === $category))
            ->when($terms !== [], fn (Collection $items) => $items->filter(function (DemoWholesaleItem $item) use ($terms): bool {
                $haystack = Str::lower("{$item->product->name} {$item->product->brand} {$item->variant->name} {$item->unit}");

                return collect($terms)->every(fn (string $term): bool => str_contains($haystack, $term));
            }))
            ->values();
    }

    /*
    |--------------------------------------------------------------------------
    | Enquiry list (session)
    |--------------------------------------------------------------------------
    */

    /**
     * Adds units to the enquiry list, raising the line to the minimum order. Returns the stored quantity.
     */
    public function add(string $sku, int $quantity): int
    {
        $item = self::item($sku);

        if ($item === null) {
            return 0;
        }

        return $this->setQuantity($sku, $this->quantityOf($sku) + max(1, $quantity));
    }

    /**
     * Sets a line's quantity between the minimum order and the maximum. Zero or less removes it.
     */
    public function setQuantity(string $sku, int $quantity): int
    {
        $item = self::item($sku);

        if ($item === null || $quantity < 1) {
            $this->remove($sku);

            return 0;
        }

        $quantity = min(self::MAX_QUANTITY, max($item->moq(), $quantity));
        $this->session->put(self::KEY.'.list.'.$sku, $quantity);

        return $quantity;
    }

    public function remove(string $sku): void
    {
        $list = $this->raw();
        unset($list[$sku]);
        $this->session->put(self::KEY.'.list', $list);
    }

    public function quantityOf(string $sku): int
    {
        return $this->raw()[$sku] ?? 0;
    }

    public function count(): int
    {
        return count($this->raw());
    }

    /**
     * @return list<array{item: DemoWholesaleItem, quantity: int, unit: int, total: int}>
     */
    public function lines(): array
    {
        $lines = [];

        foreach ($this->raw() as $sku => $quantity) {
            if ($item = self::item((string) $sku)) {
                $unit = $item->unitPriceFor($quantity);
                $lines[] = ['item' => $item, 'quantity' => $quantity, 'unit' => $unit, 'total' => $unit * $quantity];
            }
        }

        return $lines;
    }

    public function estimate(): int
    {
        return array_sum(array_column($this->lines(), 'total'));
    }

    /**
     * Records the enquiry, empties the list and returns its reference.
     *
     * @param  array<string, mixed>  $details
     */
    public function submit(array $details): string
    {
        $sequence = (int) $this->session->get(self::KEY.'.sequence', 0) + 1;
        $reference = 'WQ-'.(5100 + $sequence);

        $this->session->put(self::KEY.'.enquiries.'.$reference, [
            'reference' => $reference,
            'submitted_at' => CarbonImmutable::now()->toIso8601String(),
            'details' => $details,
            'items' => array_map(fn (array $line): array => [
                'sku' => $line['item']->sku(),
                'name' => $line['item']->product->name,
                'quantity' => $line['quantity'],
                'unit_paise' => $line['unit'],
            ], $this->lines()),
            'estimate' => $this->estimate(),
        ]);
        $this->session->put(self::KEY.'.sequence', $sequence);
        $this->session->forget(self::KEY.'.list');

        return $reference;
    }

    /**
     * @return array<string, mixed>|null
     */
    public function enquiry(string $reference): ?array
    {
        $enquiry = $this->session->get(self::KEY.'.enquiries.'.$reference);

        return is_array($enquiry) ? $enquiry : null;
    }

    /**
     * @return array<string, int>
     */
    private function raw(): array
    {
        $list = $this->session->get(self::KEY.'.list', []);

        return is_array($list) ? array_map('intval', $list) : [];
    }
}
