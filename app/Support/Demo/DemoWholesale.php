<?php

namespace App\Support\Demo;

use Carbon\CarbonImmutable;
use Illuminate\Contracts\Session\Session;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

/**
 * TEMPORARY wholesale catalogue (Phase 2). Slab prices apply in the ordinary bag
 * through DemoCartLine (ADR-019); the optional quote requests are kept in the session.
 * Replaced by a `price_slabs` table on product variants and a WholesaleEnquiry model later.
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
    | Quote requests (optional, ADR-019)
    |--------------------------------------------------------------------------
    |
    | Ordering happens through the normal bag. A quote request is for buyers who
    | need something the catalogue cannot price: custom packing, branding,
    | quantities beyond the slabs or a special payment arrangement. Customers may
    | attach what is in their bag so the shop can see what they are looking at.
    */

    /**
     * Records the request and returns its reference.
     *
     * @param  array<string, mixed>  $details
     * @param  list<DemoCartLine>  $lines  bag contents the customer chose to attach
     */
    public function submit(array $details, array $lines = []): string
    {
        $sequence = (int) $this->session->get(self::KEY.'.sequence', 0) + 1;
        $reference = 'WQ-'.(5100 + $sequence);

        $this->session->put(self::KEY.'.enquiries.'.$reference, [
            'reference' => $reference,
            'submitted_at' => CarbonImmutable::now()->toIso8601String(),
            'details' => $details,
            'items' => array_map(fn (DemoCartLine $line): array => [
                'sku' => $line->variant->sku,
                'name' => $line->product->name,
                'quantity' => $line->quantity,
                'unit_paise' => $line->unitPrice(),
            ], $lines),
            'estimate' => array_sum(array_map(fn (DemoCartLine $line): int => $line->total(), $lines)),
        ]);
        $this->session->put(self::KEY.'.sequence', $sequence);

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
}
