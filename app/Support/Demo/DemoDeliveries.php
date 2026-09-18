<?php

namespace App\Support\Demo;

use App\Enums\DeliveryFailureReason;
use App\Enums\DeliveryStep;
use App\Enums\PaymentMethod;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\Session\Session;

/**
 * TEMPORARY delivery round for the clickable prototype (Phase 3).
 *
 * Sample assignments cover every step; what the delivery boy does in the preview
 * is kept in the session, exactly like the customer's orders (ADR-016). Order
 * numbers and delivery codes match the customer-side samples in `DemoOrders`, so
 * both panels can be reviewed side by side. Replaced in Phase 9 by
 * `delivery_assignments` and the delivery actions.
 */
final class DemoDeliveries
{
    /** Wrong codes allowed before the delivery boy has to call the shop. */
    public const MAX_CODE_ATTEMPTS = 3;

    private const KEY = 'demo.deliveries';

    public function __construct(private readonly Session $session) {}

    /**
     * Assignments for today, newest first, unfinished ones before finished ones.
     *
     * @return list<DemoDeliveryJob>
     */
    public function today(): array
    {
        $jobs = array_filter($this->all(), fn (DemoDeliveryJob $job): bool => $job->day() === 'Today');
        usort($jobs, fn (DemoDeliveryJob $a, DemoDeliveryJob $b): int => [$a->isFinished(), $a->step->position()] <=> [$b->isFinished(), $b->step->position()]);

        return $jobs;
    }

    /**
     * @return list<DemoDeliveryJob>
     */
    public function active(): array
    {
        return array_values(array_filter($this->today(), fn (DemoDeliveryJob $job): bool => ! $job->isFinished()));
    }

    /**
     * Finished deliveries, newest first, including earlier days.
     *
     * @return list<DemoDeliveryJob>
     */
    public function history(): array
    {
        $jobs = array_filter($this->all(), fn (DemoDeliveryJob $job): bool => $job->isFinished());
        usort($jobs, fn (DemoDeliveryJob $a, DemoDeliveryJob $b): int => ($b->timeline[DeliveryStep::Assigned->value] ?? '') <=> ($a->timeline[DeliveryStep::Assigned->value] ?? ''));

        return $jobs;
    }

    public function find(string $number): ?DemoDeliveryJob
    {
        foreach ($this->all() as $job) {
            if ($job->number === $number) {
                return $job;
            }
        }

        return null;
    }

    /**
     * Moves a delivery to its next step (accept, picked up, reached).
     * Returns the new step, or null when the delivery cannot move on.
     */
    public function advance(string $number): ?DeliveryStep
    {
        $job = $this->find($number);
        $next = $job?->step->next();

        if ($job === null || $next === null || $next === DeliveryStep::Delivered) {
            return null;
        }

        $this->override($number, [
            'step' => $next->value,
            'timeline' => [...$job->timeline, $next->value => CarbonImmutable::now()->toIso8601String()],
        ]);

        return $next;
    }

    /**
     * Confirms a delivery with the customer's code and the cash collected.
     * Returns how many attempts are left, or null once it is delivered.
     */
    public function deliver(string $number, string $code, int $cashPaise): ?int
    {
        $job = $this->find($number);

        if ($job === null || $job->step !== DeliveryStep::Reached) {
            return 0;
        }

        if ($code !== $job->deliveryCode) {
            $used = $this->codeAttempts($number) + 1;
            $this->session->put(self::KEY.'.attempts.'.$number, $used);

            return max(0, self::MAX_CODE_ATTEMPTS - $used);
        }

        $this->override($number, [
            'step' => DeliveryStep::Delivered->value,
            'timeline' => [...$job->timeline, DeliveryStep::Delivered->value => CarbonImmutable::now()->toIso8601String()],
            'cash_collected_paise' => $job->isCod() ? $cashPaise : 0,
        ]);
        $this->session->forget(self::KEY.'.attempts.'.$number);

        return null;
    }

    public function codeAttempts(string $number): int
    {
        return (int) $this->session->get(self::KEY.'.attempts.'.$number, 0);
    }

    public function codeAttemptsLeft(string $number): int
    {
        return max(0, self::MAX_CODE_ATTEMPTS - $this->codeAttempts($number));
    }

    /**
     * Records a delivery the boy could not complete.
     */
    public function fail(string $number, DeliveryFailureReason $reason, ?string $note = null): bool
    {
        $job = $this->find($number);

        if ($job === null || $job->isFinished() || $job->step === DeliveryStep::Assigned) {
            return false;
        }

        $this->override($number, [
            'step' => DeliveryStep::Failed->value,
            'timeline' => [...$job->timeline, DeliveryStep::Failed->value => CarbonImmutable::now()->toIso8601String()],
            'failure_reason' => $reason->value,
            'failure_note' => $note ?: null,
        ]);

        return true;
    }

    /**
     * Cash position for the "hand over" screen, in paise.
     *
     * @return array{collected: int, to_hand_over: int, handed_over: int, deliveries: int, cod_deliveries: list<DemoDeliveryJob>, upi_deliveries: int}
     */
    public function cash(): array
    {
        $delivered = array_values(array_filter($this->today(), fn (DemoDeliveryJob $job): bool => $job->step === DeliveryStep::Delivered));
        $cod = array_values(array_filter($delivered, fn (DemoDeliveryJob $job): bool => $job->cashCollectedPaise > 0));

        return [
            'collected' => array_sum(array_map(fn (DemoDeliveryJob $job): int => $job->cashCollectedPaise, $cod)),
            'to_hand_over' => array_sum(array_map(fn (DemoDeliveryJob $job): int => $job->cashToHandOver(), $cod)),
            'handed_over' => array_sum(array_map(fn (DemoDeliveryJob $job): int => $job->handedOver ? $job->cashCollectedPaise : 0, $cod)),
            'deliveries' => count($delivered),
            'cod_deliveries' => $cod,
            'upi_deliveries' => count($delivered) - count($cod),
        ];
    }

    /**
     * @return list<DemoDeliveryJob>
     */
    private function all(): array
    {
        return array_map(fn (array $data): DemoDeliveryJob => $this->hydrate($data), $this->samples());
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
     * @param  array<string, mixed>  $data
     */
    private function hydrate(array $data): DemoDeliveryJob
    {
        $data = [...$data, ...(array) $this->session->get(self::KEY.'.overrides.'.$data['number'], [])];
        $reason = $data['failure_reason'] ?? null;

        return new DemoDeliveryJob(
            number: $data['number'],
            step: DeliveryStep::from($data['step']),
            customerName: $data['customer'],
            phone: $data['phone'],
            addressLines: $data['address'],
            area: $data['area'],
            pincode: $data['pincode'],
            items: $data['items'],
            paymentMethod: PaymentMethod::from($data['payment_method']),
            totalPaise: (int) $data['total_paise'],
            codPaise: (int) ($data['cod_paise'] ?? 0),
            note: $data['note'] ?? null,
            deliveryCode: $data['code'],
            timeline: $data['timeline'] ?? [],
            failureReason: is_string($reason) ? DeliveryFailureReason::from($reason) : null,
            failureNote: $data['failure_note'] ?? null,
            cashCollectedPaise: (int) ($data['cash_collected_paise'] ?? 0),
            handedOver: (bool) ($data['handed_over'] ?? false),
        );
    }

    /**
     * One sample assignment per interesting step, plus two earlier days for the history.
     *
     * @return list<array<string, mixed>>
     */
    private function samples(): array
    {
        $now = CarbonImmutable::now();
        $at = fn (int $minutesAgo): string => $now->subMinutes($minutesAgo)->toIso8601String();
        $item = fn (string $name, string $variant, int $quantity): array => ['name' => $name, 'variant' => $variant, 'quantity' => $quantity];

        return [
            [
                'number' => 'ORD-10245', 'step' => 'picked_up', 'customer' => 'Priya Sharma', 'phone' => '9830012345',
                'address' => ['Flat 3B, Shanti Apartments', 'Near Pani Tanki'], 'area' => 'Boring Road', 'pincode' => '800001',
                'items' => [$item('Velvet matte lipstick', 'Rosewood', 2), $item('Kaju katli with silver leaf', '500 g box', 1), $item('Scented candle trio', 'Set of 3', 1)],
                'payment_method' => 'cod', 'total_paise' => 174800, 'cod_paise' => 174800,
                'note' => 'Please call before arriving. Gate code 4411.', 'code' => '482915',
                'timeline' => ['assigned' => $at(70), 'accepted' => $at(64), 'picked_up' => $at(38)],
            ],
            [
                'number' => 'ORD-10252', 'step' => 'assigned', 'customer' => 'Anil Verma', 'phone' => '9876501234',
                'address' => ['House 22, Lane 4'], 'area' => 'Rajendra Nagar', 'pincode' => '800016',
                'items' => [$item('Festive hamper with brass diya', 'Large', 1)],
                'payment_method' => 'upi', 'total_paise' => 149900, 'cod_paise' => 0,
                'note' => null, 'code' => '318204',
                'timeline' => ['assigned' => $at(12)],
            ],
            [
                'number' => 'ORD-10250', 'step' => 'accepted', 'customer' => 'Sunita Devi', 'phone' => '9812345678',
                'address' => ['Shop 12, Bakerganj Market'], 'area' => 'Bakerganj', 'pincode' => '800004',
                'items' => [$item('Assorted mithai box', '500 g box', 4), $item('Butter cookies tin', '400 g tin', 2)],
                'payment_method' => 'cod', 'total_paise' => 249600, 'cod_paise' => 249600,
                'note' => 'Deliver to the shop counter, not the house.', 'code' => '770143',
                'timeline' => ['assigned' => $at(30), 'accepted' => $at(22)],
            ],
            [
                'number' => 'ORD-10246', 'step' => 'reached', 'customer' => 'Mohit Raj', 'phone' => '9701122334',
                'address' => ['Flat 8C, Ganga Heights'], 'area' => 'Kankarbagh', 'pincode' => '800020',
                'items' => [$item('Premium dry fruit box', '400 g box', 1), $item('Brass diya set', 'Set of 4', 1)],
                'payment_method' => 'cod', 'total_paise' => 139800, 'cod_paise' => 139800,
                'note' => null, 'code' => '905617',
                'timeline' => ['assigned' => $at(95), 'accepted' => $at(90), 'picked_up' => $at(60), 'reached' => $at(4)],
            ],
            [
                'number' => 'ORD-10243', 'step' => 'delivered', 'customer' => 'Kavita Singh', 'phone' => '9988776655',
                'address' => ['Flat 2A, Lotus Residency'], 'area' => 'Patliputra Colony', 'pincode' => '800013',
                'items' => [$item('Smudge-proof kajal', 'Black', 3)],
                'payment_method' => 'cod', 'total_paise' => 44700, 'cod_paise' => 44700,
                'note' => null, 'code' => '221900', 'cash_collected_paise' => 44700,
                'timeline' => ['assigned' => $at(210), 'accepted' => $at(205), 'picked_up' => $at(190), 'reached' => $at(170), 'delivered' => $at(168)],
            ],
            [
                'number' => 'ORD-10240', 'step' => 'delivered', 'customer' => 'Deepak Jha', 'phone' => '9911223344',
                'address' => ['Hotel Gulmohar, Fraser Road'], 'area' => 'Fraser Road', 'pincode' => '800001',
                'items' => [$item('Welcome sweet boxes', '250 g box', 12)],
                'payment_method' => 'upi', 'total_paise' => 298800, 'cod_paise' => 0,
                'note' => 'Ask for the front desk manager.', 'code' => '640288',
                'timeline' => ['assigned' => $at(260), 'accepted' => $at(255), 'picked_up' => $at(240), 'reached' => $at(220), 'delivered' => $at(218)],
            ],
            [
                'number' => 'ORD-10198', 'step' => 'failed', 'customer' => 'Ravi Ranjan', 'phone' => '9001122334',
                'address' => ['House 5, Sector 3'], 'area' => 'Digha', 'pincode' => '800011',
                'items' => [$item('Motichoor ladoo', '500 g box', 1)],
                'payment_method' => 'cod', 'total_paise' => 34000, 'cod_paise' => 34000,
                'note' => null, 'code' => '512773', 'failure_reason' => 'nobody_home',
                'timeline' => ['assigned' => $now->subDay()->setTime(11, 5)->toIso8601String(), 'accepted' => $now->subDay()->setTime(11, 9)->toIso8601String(), 'picked_up' => $now->subDay()->setTime(11, 40)->toIso8601String(), 'reached' => $now->subDay()->setTime(12, 2)->toIso8601String(), 'failed' => $now->subDay()->setTime(12, 15)->toIso8601String()],
            ],
            [
                'number' => 'ORD-10231', 'step' => 'delivered', 'customer' => 'Priya Sharma', 'phone' => '9830012345',
                'address' => ['Flat 3B, Shanti Apartments'], 'area' => 'Boring Road', 'pincode' => '800001',
                'items' => [$item('Premium dry fruit box', '400 g box', 1), $item('Personalised photo mug', 'White', 2)],
                'payment_method' => 'upi', 'total_paise' => 145700, 'cod_paise' => 0,
                'note' => null, 'code' => '133906', 'handed_over' => true,
                'timeline' => ['assigned' => $now->subDay()->setTime(15, 20)->toIso8601String(), 'accepted' => $now->subDay()->setTime(15, 24)->toIso8601String(), 'picked_up' => $now->subDay()->setTime(15, 50)->toIso8601String(), 'reached' => $now->subDay()->setTime(16, 12)->toIso8601String(), 'delivered' => $now->subDay()->setTime(16, 15)->toIso8601String()],
            ],
        ];
    }
}
