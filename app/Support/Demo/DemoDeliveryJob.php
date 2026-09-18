<?php

namespace App\Support\Demo;

use App\Enums\DeliveryFailureReason;
use App\Enums\DeliveryStep;
use App\Enums\PaymentMethod;
use Carbon\CarbonImmutable;

/**
 * TEMPORARY (Phase 3): one order assigned to the signed-in delivery boy.
 * Replaced in Phase 9 by a `delivery_assignments` row read through the Order model.
 */
final readonly class DemoDeliveryJob
{
    /**
     * @param  list<array{name: string, variant: string, quantity: int}>  $items
     * @param  list<string>  $addressLines
     * @param  list<DemoPackage>  $packages  the boxes this order was packed into (ADR-021)
     * @param  array<string, string>  $timeline  DeliveryStep value => ISO time
     */
    public function __construct(
        public string $number,
        public DeliveryStep $step,
        public string $customerName,
        public string $phone,
        public array $addressLines,
        public string $area,
        public string $pincode,
        public array $items,
        public array $packages,
        public PaymentMethod $paymentMethod,
        public int $totalPaise,
        public int $codPaise,
        public ?string $note,
        public string $deliveryCode,
        public array $timeline = [],
        public ?DeliveryFailureReason $failureReason = null,
        public ?string $failureNote = null,
        public int $cashCollectedPaise = 0,
    ) {}

    public function isCod(): bool
    {
        return $this->paymentMethod === PaymentMethod::Cod && $this->codPaise > 0;
    }

    public function itemCount(): int
    {
        return array_sum(array_column($this->items, 'quantity'));
    }

    public function packageCount(): int
    {
        return count($this->packages);
    }

    /**
     * @return list<DemoPackage>
     */
    public function pickedUpPackages(): array
    {
        return array_values(array_filter($this->packages, fn (DemoPackage $package): bool => $package->isPickedUp()));
    }

    /**
     * @return list<DemoPackage>
     */
    public function pendingPackages(): array
    {
        return array_values(array_filter($this->packages, fn (DemoPackage $package): bool => ! $package->isPickedUp()));
    }

    public function allPickedUp(): bool
    {
        return $this->packages !== [] && $this->pendingPackages() === [];
    }

    public function package(string $id): ?DemoPackage
    {
        foreach ($this->packages as $package) {
            if ($package->id === $id) {
                return $package;
            }
        }

        return null;
    }

    /**
     * Boxes the delivery boy is carrying and has to take back when a delivery fails.
     */
    public function packagesToReturn(): int
    {
        return $this->step === DeliveryStep::Failed ? count($this->pickedUpPackages()) : 0;
    }

    public function isFinished(): bool
    {
        return $this->step->isFinished();
    }

    public function address(): string
    {
        return implode(', ', [...$this->addressLines, $this->area, $this->pincode]);
    }

    /**
     * Maps link for the "Directions" button. Kept as a search so it works without coordinates.
     */
    public function mapsUrl(): string
    {
        return 'https://www.google.com/maps/search/?api=1&query='.rawurlencode($this->address());
    }

    public function callUrl(): string
    {
        return 'tel:+91'.$this->phone;
    }

    public function timeFor(DeliveryStep $step): ?string
    {
        $time = $this->timeline[$step->value] ?? null;

        return $time === null ? null : CarbonImmutable::parse($time)->format('g:i a');
    }

    public function assignedAt(): ?string
    {
        return $this->timeFor(DeliveryStep::Assigned);
    }

    public function finishedAt(): ?string
    {
        return $this->timeFor(DeliveryStep::Delivered) ?? $this->timeFor(DeliveryStep::Failed);
    }

    public function day(): string
    {
        $moment = CarbonImmutable::parse($this->timeline[DeliveryStep::Assigned->value] ?? 'now');

        return match (true) {
            $moment->isToday() => 'Today',
            $moment->isYesterday() => 'Yesterday',
            default => $moment->format('D, j M'),
        };
    }

    /**
     * Steps for the panel's own progress list.
     *
     * @return list<array{label: string, time: string|null, state: string}>
     */
    public function progress(): array
    {
        if ($this->step === DeliveryStep::Failed) {
            return [
                ['label' => DeliveryStep::Assigned->label(), 'time' => $this->timeFor(DeliveryStep::Assigned), 'state' => 'done'],
                ['label' => DeliveryStep::Failed->label(), 'time' => $this->timeFor(DeliveryStep::Failed), 'state' => 'current'],
            ];
        }

        $position = $this->step->position();

        return array_map(fn (DeliveryStep $step): array => [
            'label' => $step->label(),
            'time' => $this->timeFor($step),
            'state' => match (true) {
                $step->position() < $position => 'done',
                $step->position() === $position => $this->step === DeliveryStep::Delivered ? 'done' : 'current',
                default => 'upcoming',
            },
        ], DeliveryStep::journey());
    }
}
