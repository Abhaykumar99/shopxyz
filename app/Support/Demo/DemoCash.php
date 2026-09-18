<?php

namespace App\Support\Demo;

use App\Enums\CashSettlementStatus;
use App\Enums\DeliveryStep;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\Session\Session;

/**
 * TEMPORARY COD cash trail for the clickable prototype (Phase 3, ADR-022).
 *
 * Cash collected at the door stays "with you" until the delivery boy hands it
 * over at the shop as one batch. The batch then waits for the admin to count it
 * and mark it settled, and every batch stays in the history.
 *
 * Replaced in Phase 9 by `cod_settlements` and the settlement actions.
 */
final class DemoCash
{
    private const KEY = 'demo.cash';

    public function __construct(
        private readonly Session $session,
        private readonly DemoDeliveries $deliveries,
    ) {}

    /**
     * Cash the delivery boy is still carrying: collected today and not yet in a batch.
     *
     * @return list<array{number: string, customer: string, paise: int, time: string|null}>
     */
    public function withYou(): array
    {
        $settled = $this->settledOrderNumbers();

        return array_values(array_map(fn (DemoDeliveryJob $job): array => [
            'number' => $job->number,
            'customer' => $job->customerName,
            'paise' => $job->cashCollectedPaise,
            'time' => $job->timeFor(DeliveryStep::Delivered),
        ], array_filter(
            $this->deliveries->today(),
            fn (DemoDeliveryJob $job): bool => $job->cashCollectedPaise > 0
                && $job->step === DeliveryStep::Delivered
                && ! in_array($job->number, $settled, true),
        )));
    }

    public function withYouTotal(): int
    {
        return array_sum(array_column($this->withYou(), 'paise'));
    }

    /**
     * Hands everything collected so far to the shop as one batch.
     * Returns the new batch, or null when there is nothing to hand over.
     */
    public function handOver(): ?DemoCashSettlement
    {
        $collections = $this->withYou();

        if ($collections === []) {
            return null;
        }

        $sequence = (int) $this->session->get(self::KEY.'.sequence', 0) + 1;
        $reference = 'CS-'.(2040 + $sequence);

        $batch = [
            'reference' => $reference,
            'status' => CashSettlementStatus::AwaitingVerification->value,
            'amount_paise' => array_sum(array_column($collections, 'paise')),
            'collections' => array_map(fn (array $line): array => [
                'number' => $line['number'],
                'customer' => $line['customer'],
                'paise' => $line['paise'],
            ], $collections),
            'handed_over_at' => CarbonImmutable::now()->toIso8601String(),
        ];

        $this->session->put(self::KEY.'.batches.'.$reference, $batch);
        $this->session->put(self::KEY.'.sequence', $sequence);

        return $this->hydrate($batch);
    }

    /**
     * Stands in for the admin counting the cash (Phase 7). Preview only.
     */
    public function markVerified(string $reference): bool
    {
        $batch = (array) $this->session->get(self::KEY.'.batches.'.$reference, []);

        if ($batch === [] || ($batch['status'] ?? null) !== CashSettlementStatus::AwaitingVerification->value) {
            return false;
        }

        $this->session->put(self::KEY.'.batches.'.$reference, [
            ...$batch,
            'status' => CashSettlementStatus::Settled->value,
            'verified_at' => CarbonImmutable::now()->toIso8601String(),
            'verified_by' => 'Shop counter',
        ]);

        return true;
    }

    /**
     * Batches waiting for the shop to count them, newest first.
     *
     * @return list<DemoCashSettlement>
     */
    public function awaitingVerification(): array
    {
        return array_values(array_filter($this->all(), fn (DemoCashSettlement $batch): bool => $batch->isOpen()));
    }

    public function awaitingVerificationTotal(): int
    {
        return array_sum(array_map(fn (DemoCashSettlement $batch): int => $batch->amountPaise, $this->awaitingVerification()));
    }

    /**
     * Every batch, newest first.
     *
     * @return list<DemoCashSettlement>
     */
    public function all(): array
    {
        $batches = array_map(fn (array $batch): DemoCashSettlement => $this->hydrate($batch), [
            ...array_values((array) $this->session->get(self::KEY.'.batches', [])),
            ...$this->samples(),
        ]);
        usort($batches, fn (DemoCashSettlement $a, DemoCashSettlement $b): int => $b->handedOverAt <=> $a->handedOverAt);

        return $batches;
    }

    /**
     * @return list<DemoCashSettlement>
     */
    public function settled(): array
    {
        return array_values(array_filter($this->all(), fn (DemoCashSettlement $batch): bool => ! $batch->isOpen()));
    }

    public function settledTodayTotal(): int
    {
        return array_sum(array_map(
            fn (DemoCashSettlement $batch): int => $batch->handedOverOn() === 'Today' ? $batch->amountPaise : 0,
            $this->settled(),
        ));
    }

    public function find(string $reference): ?DemoCashSettlement
    {
        foreach ($this->all() as $batch) {
            if ($batch->reference === $reference) {
                return $batch;
            }
        }

        return null;
    }

    /**
     * Orders whose cash is already in a batch.
     *
     * @return list<string>
     */
    private function settledOrderNumbers(): array
    {
        $numbers = [];

        foreach ($this->all() as $batch) {
            foreach ($batch->collections as $collection) {
                $numbers[] = $collection['number'];
            }
        }

        return $numbers;
    }

    /**
     * @param  array<string, mixed>  $batch
     */
    private function hydrate(array $batch): DemoCashSettlement
    {
        return new DemoCashSettlement(
            reference: $batch['reference'],
            status: CashSettlementStatus::from($batch['status']),
            amountPaise: (int) $batch['amount_paise'],
            collections: array_values((array) $batch['collections']),
            handedOverAt: $batch['handed_over_at'],
            verifiedAt: $batch['verified_at'] ?? null,
            verifiedBy: $batch['verified_by'] ?? null,
            note: $batch['note'] ?? null,
        );
    }

    /**
     * Earlier handovers, so the history has something to show.
     *
     * @return list<array<string, mixed>>
     */
    private function samples(): array
    {
        $now = CarbonImmutable::now();

        return [
            [
                'reference' => 'CS-2039',
                'status' => CashSettlementStatus::Settled->value,
                'amount_paise' => 214500,
                'collections' => [
                    ['number' => 'ORD-10236', 'customer' => 'Imran Khan', 'paise' => 96000],
                    ['number' => 'ORD-10228', 'customer' => 'Farhan Ali', 'paise' => 74500],
                    ['number' => 'ORD-10225', 'customer' => 'Neha Gupta', 'paise' => 44000],
                ],
                'handed_over_at' => $now->subDay()->setTime(19, 10)->toIso8601String(),
                'verified_at' => $now->subDay()->setTime(19, 25)->toIso8601String(),
                'verified_by' => 'Shop counter',
            ],
            [
                'reference' => 'CS-2038',
                'status' => CashSettlementStatus::Settled->value,
                'amount_paise' => 128900,
                'collections' => [
                    ['number' => 'ORD-10214', 'customer' => 'Sanjay Prasad', 'paise' => 78900],
                    ['number' => 'ORD-10209', 'customer' => 'Meera Kumari', 'paise' => 50000],
                ],
                'handed_over_at' => $now->subDays(2)->setTime(18, 40)->toIso8601String(),
                'verified_at' => $now->subDays(2)->setTime(18, 55)->toIso8601String(),
                'verified_by' => 'Shop counter',
            ],
        ];
    }
}
