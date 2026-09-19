<?php

namespace App\Support\Demo;

use App\Enums\OrderStatus;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Models\Address;
use App\Support\ShopSettings;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\Session\Session;
use LogicException;

/**
 * TEMPORARY order history for the clickable prototype (Phase 2).
 *
 * Sample orders cover every status; orders placed in the preview are kept in
 * the session. Replaced by the Order model and Order actions in Phase 6.
 */
final class DemoOrders
{
    private const KEY = 'demo.orders';

    public function __construct(
        private readonly Session $session,
        private readonly ShopSettings $shop,
    ) {}

    /**
     * Newest first.
     *
     * @return list<DemoOrder>
     */
    public function all(): array
    {
        $orders = array_map(fn (array $data): DemoOrder => $this->hydrate($data), [...$this->samples(), ...$this->placed()]);
        usort($orders, fn (DemoOrder $a, DemoOrder $b): int => $b->placedAt <=> $a->placedAt);

        return $orders;
    }

    public function find(string $number): ?DemoOrder
    {
        foreach ($this->all() as $order) {
            if ($order->number === $number) {
                return $order;
            }
        }

        return null;
    }

    public function place(DemoCart $cart, Address $address, PaymentMethod $method, ?string $note = null): DemoOrder
    {
        $summary = $cart->summary($this->shop);
        $number = 'ORD-'.(10300 + (int) $this->session->get(self::KEY.'.sequence', 0) + 1);
        $now = CarbonImmutable::now();

        $items = array_map(fn (DemoCartLine $line): array => [
            'name' => $line->product->name,
            'variant' => $line->variant->name,
            'sku' => $line->variant->sku,
            'slug' => $line->product->slug,
            'category' => $line->product->category,
            'quantity' => $line->quantity,
            'mrp' => $line->variant->mrp ?? $line->variant->paise,
            'paise' => $line->unitPrice(),
            'wholesale' => $line->isWholesale(),
        ], $cart->lines());

        $data = [
            'number' => $number,
            'placed_at' => $now->toIso8601String(),
            'status' => OrderStatus::Placed->value,
            'payment_method' => $method->value,
            'payment_status' => $method->initialPaymentStatus()->value,
            'items' => $items,
            'address' => DemoAddress::fromModel($address)->toArray(),
            'delivery' => $summary['delivery'],
            'note' => $note ?: null,
            'timeline' => [OrderStatus::Placed->value => $now->toIso8601String()],
        ];

        $this->session->put(self::KEY.'.placed.'.$number, $data);
        $this->session->put(self::KEY.'.sequence', (int) $this->session->get(self::KEY.'.sequence', 0) + 1);
        $cart->clear();

        return $this->hydrate($data);
    }

    /**
     * Returns false when the order can no longer be cancelled.
     */
    public function cancel(string $number): bool
    {
        $order = $this->find($number);

        if ($order === null || ! $order->canBeCancelled()) {
            return false;
        }

        $this->override($number, [
            'status' => OrderStatus::Cancelled->value,
            'timeline' => [...$order->timeline, OrderStatus::Cancelled->value => CarbonImmutable::now()->toIso8601String()],
        ]);

        return true;
    }

    public function utrInUse(string $utr, string $exceptOrder): bool
    {
        foreach ($this->all() as $order) {
            if ($order->number !== $exceptOrder && $order->utr === $utr) {
                return true;
            }
        }

        return false;
    }

    public function submitPaymentProof(string $number, string $utr): bool
    {
        $order = $this->find($number);

        if ($order === null || ! $order->needsPaymentProof()) {
            return false;
        }

        $this->override($number, [
            'payment_status' => PaymentStatus::PendingVerification->value,
            'utr' => $utr,
            'rejection_reason' => null,
        ]);

        return true;
    }

    /**
     * @param  array<string, mixed>  $changes
     */
    private function override(string $number, array $changes): void
    {
        $key = self::KEY.'.overrides.'.$number;
        $this->session->put($key, [...(array) $this->session->get($key, []), ...$changes]);
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function placed(): array
    {
        return array_values((array) $this->session->get(self::KEY.'.placed', []));
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function hydrate(array $data): DemoOrder
    {
        $data = [...$data, ...(array) $this->session->get(self::KEY.'.overrides.'.$data['number'], [])];

        return new DemoOrder(
            number: $data['number'],
            placedAt: CarbonImmutable::parse($data['placed_at']),
            status: OrderStatus::from($data['status']),
            paymentMethod: PaymentMethod::from($data['payment_method']),
            paymentStatus: PaymentStatus::from($data['payment_status']),
            items: $data['items'],
            address: DemoAddress::fromArray($data['address']),
            delivery: (int) $data['delivery'],
            note: $data['note'] ?? null,
            timeline: $data['timeline'] ?? [],
            deliveryPartner: $data['delivery_partner'] ?? null,
            deliveryCode: $data['delivery_code'] ?? null,
            utr: $data['utr'] ?? null,
            rejectionReason: $data['rejection_reason'] ?? null,
            failureReason: $data['failure_reason'] ?? null,
            invoiceNumber: $data['invoice_number'] ?? null,
            packages: array_values((array) ($data['packages'] ?? [])),
        );
    }

    /**
     * One sample order per interesting state.
     *
     * @return list<array<string, mixed>>
     */
    private function samples(): array
    {
        $now = CarbonImmutable::now();
        $at = fn (int $minutesAgo): string => $now->subMinutes($minutesAgo)->toIso8601String();
        $home = [
            'id' => 'addr_home', 'label' => 'Home', 'name' => 'Priya Sharma', 'phone' => '9830012345',
            'line1' => 'Flat 3B, Shanti Apartments', 'line2' => 'Boring Road', 'landmark' => 'Pani Tanki',
            'city' => 'Patna', 'state' => 'Bihar', 'pincode' => '800001', 'is_default' => true,
        ];
        $shopfront = [
            'id' => 'addr_shop', 'label' => 'Work', 'name' => 'Priya Sharma', 'phone' => '9830012345',
            'line1' => 'Sharma Sweets and Gifts, Shop 12', 'line2' => 'Bakerganj Market', 'landmark' => 'Opposite the bus stand',
            'city' => 'Patna', 'state' => 'Bihar', 'pincode' => '800004', 'is_default' => false,
        ];
        $item = fn (string $sku, int $quantity): array => $this->item($sku, $quantity);
        $rahul = ['name' => 'Rahul Kumar', 'phone' => '+91 90000 11111'];

        return [
            [
                'number' => 'ORD-10245', 'placed_at' => $at(95), 'status' => 'out_for_delivery', 'payment_method' => 'cod', 'payment_status' => 'cod_pending',
                'items' => [$item('BB-LIP-1', 2), $item('MG-KK-2', 1), $item('DH-CND-1', 1)], 'address' => $home, 'delivery' => 0,
                'packages' => [['id' => 'PKG-10245-1', 'picked_up' => true], ['id' => 'PKG-10245-2', 'picked_up' => true]],
                'note' => 'Please call before arriving. Gate code 4411.', 'delivery_partner' => $rahul, 'delivery_code' => '482915',
                'invoice_number' => 'INV/2026-27/00018',
                'timeline' => ['placed' => $at(95), 'confirmed' => $at(85), 'packed' => $at(55), 'out_for_delivery' => $at(20)],
            ],
            [
                'number' => 'ORD-10248', 'placed_at' => $at(30), 'status' => 'placed', 'payment_method' => 'upi', 'payment_status' => 'pending_verification',
                'items' => [$item('UG-HMP-1', 1)], 'address' => $home, 'delivery' => 0, 'utr' => '412345678901',
                'timeline' => ['placed' => $at(30)],
            ],
            [
                'number' => 'ORD-10249', 'placed_at' => $at(240), 'status' => 'packing', 'payment_method' => 'upi', 'payment_status' => 'verified',
                'items' => [$item('MG-ASST-1', 60), $item('UG-HMP-1', 25)], 'address' => $shopfront, 'delivery' => 0, 'utr' => '414500011122',
                'packages' => [['id' => 'PKG-10249-1', 'picked_up' => false], ['id' => 'PKG-10249-2', 'picked_up' => false], ['id' => 'PKG-10249-3', 'picked_up' => false]],
                'note' => 'Bulk order for Diwali counter. GST invoice needed.', 'invoice_number' => 'INV/2026-27/00021',
                'timeline' => ['placed' => $at(240), 'confirmed' => $at(210)],
            ],
            [
                'number' => 'ORD-10247', 'placed_at' => $at(300), 'status' => 'placed', 'payment_method' => 'upi', 'payment_status' => 'rejected',
                'items' => [$item('CL-TRF-1', 1), $item('BH-TIN-1', 1)], 'address' => $home, 'delivery' => 0, 'utr' => '400000000001',
                'rejection_reason' => 'We could not find a payment with this UTR. Please check the number in your UPI app and upload the screenshot again.',
                'timeline' => ['placed' => $at(300)],
            ],
            [
                'number' => 'ORD-10244', 'placed_at' => $at(1500), 'status' => 'packing', 'payment_method' => 'cod', 'payment_status' => 'cod_pending',
                'items' => [$item('GL-GEL-1', 1), $item('NB-ROSE-1', 1)], 'address' => $home, 'delivery' => 4000,
                'timeline' => ['placed' => $at(1500), 'confirmed' => $at(1440)],
            ],
            [
                'number' => 'ORD-10231', 'placed_at' => $at(4400), 'status' => 'delivered', 'payment_method' => 'upi', 'payment_status' => 'verified',
                'items' => [$item('UG-DRY-1', 1), $item('KC-MUG-1', 2)], 'address' => $home, 'delivery' => 0, 'utr' => '398765432101',
                'packages' => [['id' => 'PKG-10231-1', 'picked_up' => true], ['id' => 'PKG-10231-2', 'picked_up' => true]],
                'delivery_partner' => $rahul, 'invoice_number' => 'INV/2026-27/00011',
                'timeline' => ['placed' => $at(4400), 'confirmed' => $at(4380), 'packed' => $at(4300), 'out_for_delivery' => $at(4260), 'delivered' => $at(4200)],
            ],
            [
                'number' => 'ORD-10198', 'placed_at' => $at(2900), 'status' => 'delivery_failed', 'payment_method' => 'cod', 'payment_status' => 'cod_pending',
                'items' => [$item('MG-ML-2', 1)], 'address' => $home, 'delivery' => 4000,
                'packages' => [['id' => 'PKG-10198-1', 'picked_up' => true]],
                'delivery_partner' => $rahul, 'failure_reason' => 'Nobody was home. We will call you to arrange another time.',
                'timeline' => ['placed' => $at(2900), 'confirmed' => $at(2880), 'packed' => $at(2800), 'out_for_delivery' => $at(2760), 'delivery_failed' => $at(2700)],
            ],
            [
                'number' => 'ORD-10150', 'placed_at' => $at(12000), 'status' => 'cancelled', 'payment_method' => 'cod', 'payment_status' => 'cod_pending',
                'items' => [$item('RS-MASC-1', 1)], 'address' => $home, 'delivery' => 4000,
                'timeline' => ['placed' => $at(12000), 'cancelled' => $at(11950)],
            ],
        ];
    }

    /**
     * A sample line, priced at the wholesale slab once it reaches the minimum quantity.
     *
     * @return array{name: string, variant: string, sku: string, slug: string, category: string, quantity: int, mrp: int, paise: int, wholesale: bool}
     */
    private function item(string $sku, int $quantity): array
    {
        [$product, $variant] = DemoCatalog::findSku($sku) ?? throw new LogicException("Unknown demo SKU {$sku}");
        $line = new DemoCartLine($product, $variant, $quantity);

        return [
            'name' => $product->name,
            'variant' => $variant->name,
            'sku' => $sku,
            'slug' => $product->slug,
            'category' => $product->category,
            'quantity' => $quantity,
            'mrp' => $variant->mrp ?? $variant->paise,
            'paise' => $line->unitPrice(),
            'wholesale' => $line->isWholesale(),
        ];
    }
}
