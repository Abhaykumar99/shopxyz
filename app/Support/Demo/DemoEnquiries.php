<?php

namespace App\Support\Demo;

use App\Models\CartItem;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\Session\Session;
use Illuminate\Support\Collection;

/**
 * TEMPORARY store for wholesale quote requests, kept in the session.
 *
 * The wholesale *catalogue* is real from Phase 7 — price bands live on product
 * variants and are read through `App\Support\Catalog\WholesaleCatalog`. This
 * holds only the optional quote requests (ADR-019), which move onto the
 * `WholesaleEnquiry` model with the rest of the order flow in Phase 8.
 */
final class DemoEnquiries
{
    /**
     * The kinds of buyer the quote form offers.
     */
    public const BUSINESS_TYPES = [
        'retail' => 'Retail shop',
        'events' => 'Wedding or event planner',
        'corporate' => 'Corporate gifting',
        'hospitality' => 'Hotel, restaurant or café',
        'other' => 'Other',
    ];

    private const KEY = 'demo.enquiries';

    public function __construct(private readonly Session $session) {}

    /**
     * Records the request and returns its reference.
     *
     * @param  array<string, mixed>  $details
     * @param  Collection<int, CartItem>  $lines  bag contents the customer chose to attach
     */
    public function submit(array $details, Collection $lines = new Collection): string
    {
        $sequence = (int) $this->session->get(self::KEY.'.sequence', 0) + 1;
        $reference = 'WQ-'.(5100 + $sequence);

        $this->session->put(self::KEY.'.enquiries.'.$reference, [
            'reference' => $reference,
            'submitted_at' => CarbonImmutable::now()->toIso8601String(),
            'details' => $details,
            'items' => $lines->map(fn (CartItem $line): array => [
                'sku' => $line->sku(),
                'name' => (string) $line->variant?->product?->name,
                'quantity' => $line->quantity,
                'unit_paise' => $line->unitPrice(),
            ])->all(),
            'estimate' => (int) $lines->sum(fn (CartItem $line): int => $line->total()),
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
