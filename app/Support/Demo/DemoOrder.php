<?php

namespace App\Support\Demo;

use App\Enums\OrderStatus;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use Carbon\CarbonImmutable;

/**
 * TEMPORARY (Phases 2–5): replaced by the Order model.
 */
final readonly class DemoOrder
{
    /**
     * @param  list<array{name: string, variant: string, sku: string, slug: string, category: string, quantity: int, mrp: int, paise: int, wholesale?: bool}>  $items
     * @param  list<array{id: string, picked_up: bool}>  $packages  the boxes the shop packed (ADR-021)
     * @param  array<string, string>  $timeline  OrderStatus value => ISO time the order reached it
     * @param  array{name: string, phone: string}|null  $deliveryPartner
     */
    public function __construct(
        public string $number,
        public CarbonImmutable $placedAt,
        public OrderStatus $status,
        public PaymentMethod $paymentMethod,
        public PaymentStatus $paymentStatus,
        public array $items,
        public DemoAddress $address,
        public int $delivery,
        public ?string $note = null,
        public array $timeline = [],
        public ?array $deliveryPartner = null,
        public ?string $deliveryCode = null,
        public ?string $utr = null,
        public ?string $rejectionReason = null,
        public ?string $failureReason = null,
        public ?string $invoiceNumber = null,
        public array $packages = [],
    ) {}

    public function packageCount(): int
    {
        return count($this->packages);
    }

    /**
     * Boxes the delivery partner has picked up from the shop.
     */
    public function pickedUpPackageCount(): int
    {
        return count(array_filter($this->packages, fn (array $package): bool => $package['picked_up']));
    }

    public function subtotal(): int
    {
        return array_sum(array_map(fn (array $item): int => $item['paise'] * $item['quantity'], $this->items));
    }

    public function mrpTotal(): int
    {
        return array_sum(array_map(fn (array $item): int => $item['mrp'] * $item['quantity'], $this->items));
    }

    public function discount(): int
    {
        return $this->mrpTotal() - $this->subtotal();
    }

    public function total(): int
    {
        return $this->subtotal() + $this->delivery;
    }

    public function itemCount(): int
    {
        return array_sum(array_column($this->items, 'quantity'));
    }

    /**
     * At least one line was priced at a wholesale slab.
     */
    public function hasWholesaleItems(): bool
    {
        foreach ($this->items as $item) {
            if ($item['wholesale'] ?? false) {
                return true;
            }
        }

        return false;
    }

    public function isActive(): bool
    {
        return ! $this->status->isFinal();
    }

    public function canBeCancelled(): bool
    {
        return $this->status->isCancellableByCustomer();
    }

    public function needsPaymentProof(): bool
    {
        return $this->paymentMethod === PaymentMethod::Upi
            && $this->paymentStatus->acceptsProof()
            && $this->status !== OrderStatus::Cancelled;
    }

    /**
     * The delivery code is shown only while the parcel is on its way.
     */
    public function visibleDeliveryCode(): ?string
    {
        return $this->status === OrderStatus::OutForDelivery ? $this->deliveryCode : null;
    }

    /**
     * Steps for <x-shop.order-tracker>.
     *
     * @return list<array{label: string, time?: string, state: string, icon?: string, note?: string}>
     */
    public function trackerSteps(): array
    {
        if ($this->status === OrderStatus::Cancelled) {
            return [
                $this->step(OrderStatus::Placed, 'done'),
                ['label' => 'Cancelled', 'time' => $this->timeFor(OrderStatus::Cancelled), 'state' => 'current', 'note' => 'Items have gone back on the shelf.'],
            ];
        }

        $current = $this->status->journeyStep();
        $journey = OrderStatus::customerJourney();
        $currentIndex = (int) array_search($current, $journey, true);
        $steps = [];

        foreach ($journey as $index => $status) {
            $state = match (true) {
                $index < $currentIndex => 'done',
                $index === $currentIndex => $this->status === OrderStatus::Delivered ? 'done' : 'current',
                default => 'upcoming',
            };
            $steps[] = $this->step($status, $state);
        }

        if ($this->status === OrderStatus::DeliveryFailed) {
            $steps[$currentIndex] = [
                'label' => 'Delivery failed',
                'time' => $this->timeFor(OrderStatus::DeliveryFailed),
                'state' => 'current',
                'note' => $this->failureReason ?? 'We could not reach you. We will call to arrange another time.',
            ];
        }

        return $steps;
    }

    /**
     * @return array{label: string, time?: string, state: string, icon?: string, note?: string}
     */
    private function step(OrderStatus $status, string $state): array
    {
        $step = ['label' => $status === OrderStatus::Placed ? 'Order placed' : $status->label(), 'state' => $state];

        if ($time = $this->timeFor($status)) {
            $step['time'] = $time;
        }

        if ($state === 'current') {
            $step['icon'] = match ($status) {
                OrderStatus::Placed, OrderStatus::Confirmed => 'receipt-indian-rupee',
                OrderStatus::Packed => 'package',
                default => 'truck',
            };
            $note = match ($this->status) {
                OrderStatus::Placed => $this->paymentMethod === PaymentMethod::Upi
                    ? 'We confirm your order as soon as your UPI payment is checked.'
                    : 'We will confirm your order shortly.',
                OrderStatus::Packing => 'Your items are being packed.',
                OrderStatus::Assigned => $this->deliveryPartner ? "{$this->deliveryPartner['name']} will pick up your parcel soon." : null,
                OrderStatus::OutForDelivery => $this->deliveryPartner ? "{$this->deliveryPartner['name']} is on the way." : null,
                default => null,
            };
            if ($note) {
                $step['note'] = $note;
            }
        }

        return $step;
    }

    private function timeFor(OrderStatus $status): ?string
    {
        $time = $this->timeline[$status->value] ?? null;

        if ($time === null) {
            return null;
        }

        $moment = CarbonImmutable::parse($time);
        $day = match (true) {
            $moment->isToday() => 'Today',
            $moment->isYesterday() => 'Yesterday',
            default => $moment->format('D, j M'),
        };

        return $day.', '.$moment->format('g:i a');
    }
}
