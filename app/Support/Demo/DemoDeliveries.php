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
    /** Wrong delivery OTPs allowed before the delivery boy has to call the shop. */
    public const MAX_CODE_ATTEMPTS = 3;

    /** Wrong pickup codes allowed per order before the shop has to check the boxes. */
    public const MAX_PICKUP_ATTEMPTS = 3;

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
     * Moves a delivery to its next step where a tap is enough: accepting it, and
     * saying you have reached the address. Picking up needs the pickup code on
     * every box (ADR-021) and delivering needs the customer's OTP.
     *
     * Returns the new step, or null when the delivery cannot move on.
     */
    public function advance(string $number): ?DeliveryStep
    {
        $job = $this->find($number);
        $next = $job?->step->next();

        if ($job === null || $next === null || ! $next->isReachedByTapping()) {
            return null;
        }

        $this->step($number, $job, $next);

        return $next;
    }

    /**
     * Verifies the pickup code printed on one box's label.
     *
     * @return array{verified: bool, attempts_left: int, all_picked_up: bool}
     */
    public function verifyPickup(string $number, string $packageId, string $code): array
    {
        $job = $this->find($number);
        $package = $job?->package($packageId);
        $failed = fn (int $left): array => ['verified' => false, 'attempts_left' => $left, 'all_picked_up' => false];

        if ($job === null || $package === null || $job->step !== DeliveryStep::Accepted || $package->isPickedUp()) {
            return $failed($job === null ? 0 : $this->pickupAttemptsLeft($number));
        }

        if ($this->pickupAttemptsLeft($number) === 0) {
            return $failed(0);
        }

        if ($code !== $package->pickupCode) {
            $used = (int) $this->session->get(self::KEY.'.pickup_attempts.'.$number, 0) + 1;
            $this->session->put(self::KEY.'.pickup_attempts.'.$number, $used);

            return $failed(max(0, self::MAX_PICKUP_ATTEMPTS - $used));
        }

        $this->session->put(self::KEY.'.picked_up.'.$number.'.'.$packageId, CarbonImmutable::now()->toIso8601String());
        $this->session->forget(self::KEY.'.pickup_attempts.'.$number);

        $job = $this->find($number) ?? $job;
        $allPickedUp = $job->allPickedUp();

        if ($allPickedUp) {
            $this->step($number, $job, DeliveryStep::PickedUp);
        }

        return ['verified' => true, 'attempts_left' => self::MAX_PICKUP_ATTEMPTS, 'all_picked_up' => $allPickedUp];
    }

    public function pickupAttemptsLeft(string $number): int
    {
        return max(0, self::MAX_PICKUP_ATTEMPTS - (int) $this->session->get(self::KEY.'.pickup_attempts.'.$number, 0));
    }

    /**
     * Confirms a delivery with the customer's code and the cash collected.
     * Returns how many attempts are left, or null once it is delivered.
     */
    public function deliver(string $number, string $code, int $cashPaise): ?int
    {
        $job = $this->find($number);

        if ($job === null || $job->step !== DeliveryStep::OutForDelivery) {
            return 0;
        }

        if ($code !== $job->deliveryCode) {
            $used = $this->codeAttempts($number) + 1;
            $this->session->put(self::KEY.'.attempts.'.$number, $used);

            return max(0, self::MAX_CODE_ATTEMPTS - $used);
        }

        $this->step($number, $job, DeliveryStep::Delivered, ['cash_collected_paise' => $job->isCod() ? $cashPaise : 0]);
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

        $this->step($number, $job, DeliveryStep::Failed, [
            'failure_reason' => $reason->value,
            'failure_note' => $note ?: null,
        ]);

        return true;
    }

    /**
     * Counts for the round screen and the profile.
     *
     * @return array{to_deliver: int, delivered: int, failed: int, cod_collected: int}
     */
    public function summary(): array
    {
        $today = $this->today();
        $delivered = array_values(array_filter($today, fn (DemoDeliveryJob $job): bool => $job->step === DeliveryStep::Delivered));

        return [
            'to_deliver' => count(array_filter($today, fn (DemoDeliveryJob $job): bool => ! $job->isFinished())),
            'delivered' => count($delivered),
            'failed' => count(array_filter($today, fn (DemoDeliveryJob $job): bool => $job->step === DeliveryStep::Failed)),
            'cod_collected' => array_sum(array_map(fn (DemoDeliveryJob $job): int => $job->cashCollectedPaise, $delivered)),
        ];
    }

    /**
     * Today's deliveries grouped by step, in the order the panel shows them.
     *
     * @return array<string, list<DemoDeliveryJob>>
     */
    public function grouped(): array
    {
        $groups = [];

        foreach (DeliveryStep::groups() as $step) {
            $groups[$step->group()] = [];
        }

        foreach ($this->today() as $job) {
            $groups[$job->step->group()][] = $job;
        }

        return array_filter($groups, fn (array $jobs): bool => $jobs !== []);
    }

    /**
     * @return list<DemoDeliveryJob>
     */
    private function all(): array
    {
        return array_map(fn (array $data): DemoDeliveryJob => $this->hydrate($data), $this->samples());
    }

    /**
     * Records a step and the time it happened.
     *
     * @param  array<string, mixed>  $changes
     */
    private function step(string $number, DemoDeliveryJob $job, DeliveryStep $step, array $changes = []): void
    {
        $this->override($number, [
            'step' => $step->value,
            'timeline' => [...$job->timeline, $step->value => CarbonImmutable::now()->toIso8601String()],
            ...$changes,
        ]);
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

        $packages = $data['packages'] ?? [];
        $pickedUp = (array) $this->session->get(self::KEY.'.picked_up.'.$data['number'], []);

        return new DemoDeliveryJob(
            number: $data['number'],
            step: DeliveryStep::from($data['step']),
            customerName: $data['customer'],
            phone: $data['phone'],
            addressLines: $data['address'],
            area: $data['area'],
            pincode: $data['pincode'],
            items: $data['items'],
            packages: array_map(fn (array $package, int $index): DemoPackage => new DemoPackage(
                id: $package['id'],
                index: $index + 1,
                total: count($packages),
                pickupCode: $package['code'],
                items: $package['items'],
                pickedUpAt: $pickedUp[$package['id']] ?? $package['picked_up_at'] ?? null,
            ), $packages, array_keys($packages)),
            paymentMethod: PaymentMethod::from($data['payment_method']),
            totalPaise: (int) $data['total_paise'],
            codPaise: (int) ($data['cod_paise'] ?? 0),
            note: $data['note'] ?? null,
            deliveryCode: $data['code'],
            timeline: $data['timeline'] ?? [],
            failureReason: is_string($reason) ? DeliveryFailureReason::from($reason) : null,
            failureNote: $data['failure_note'] ?? null,
            cashCollectedPaise: (int) ($data['cash_collected_paise'] ?? 0),
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
        $yesterday = fn (int $hour, int $minute): string => $now->subDay()->setTime($hour, $minute)->toIso8601String();
        $item = fn (string $name, string $variant, int $quantity): array => ['name' => $name, 'variant' => $variant, 'quantity' => $quantity];
        $box = fn (string $id, string $code, array $items, ?string $pickedUpAt = null): array => [
            'id' => $id, 'code' => $code, 'items' => $items, 'picked_up_at' => $pickedUpAt,
        ];

        $lipstick = $item('Velvet matte lipstick', 'Rosewood', 2);
        $katli = $item('Kaju katli with silver leaf', '500 g box', 1);
        $candles = $item('Scented candle trio', 'Set of 3', 1);
        $hamper = $item('Festive hamper with brass diya', 'Large', 1);
        $mithai = $item('Assorted mithai box', '500 g box', 4);
        $cookies = $item('Butter cookies tin', '400 g tin', 2);
        $dryFruit = $item('Premium dry fruit box', '400 g box', 1);
        $diya = $item('Brass diya set', 'Set of 4', 1);
        $kajal = $item('Smudge-proof kajal', 'Black', 3);
        $welcome = $item('Welcome sweet boxes', '250 g box', 12);
        $welcomeFour = $item('Welcome sweet boxes', '250 g box', 4);
        $mugs = $item('Personalised photo mug', 'White', 2);
        $ladoo = $item('Motichoor ladoo', '500 g box', 1);

        return [
            [
                'number' => 'ORD-10245', 'step' => 'picked_up', 'customer' => 'Priya Sharma', 'phone' => '9830012345',
                'address' => ['Flat 3B, Shanti Apartments', 'Near Pani Tanki'], 'area' => 'Boring Road', 'pincode' => '800001',
                'items' => [$lipstick, $katli, $candles],
                'packages' => [
                    $box('PKG-10245-1', '731408', [$lipstick, $katli], $at(38)),
                    $box('PKG-10245-2', '559214', [$candles], $at(38)),
                ],
                'payment_method' => 'cod', 'total_paise' => 174800, 'cod_paise' => 174800,
                'note' => 'Please call before arriving. Gate code 4411.', 'code' => '482915',
                'timeline' => ['assigned' => $at(70), 'accepted' => $at(64), 'picked_up' => $at(38)],
            ],
            [
                'number' => 'ORD-10252', 'step' => 'assigned', 'customer' => 'Anil Verma', 'phone' => '9876501234',
                'address' => ['House 22, Lane 4'], 'area' => 'Rajendra Nagar', 'pincode' => '800016',
                'items' => [$hamper],
                'packages' => [$box('PKG-10252-1', '640913', [$hamper])],
                'payment_method' => 'upi', 'total_paise' => 149900, 'cod_paise' => 0,
                'note' => null, 'code' => '318204',
                'timeline' => ['assigned' => $at(12)],
            ],
            [
                'number' => 'ORD-10250', 'step' => 'accepted', 'customer' => 'Sunita Devi', 'phone' => '9812345678',
                'address' => ['Shop 12, Bakerganj Market'], 'area' => 'Bakerganj', 'pincode' => '800004',
                'items' => [$mithai, $cookies],
                'packages' => [
                    $box('PKG-10250-1', '204877', [$mithai]),
                    $box('PKG-10250-2', '913526', [$cookies]),
                ],
                'payment_method' => 'cod', 'total_paise' => 249600, 'cod_paise' => 249600,
                'note' => 'Deliver to the shop counter, not the house.', 'code' => '770143',
                'timeline' => ['assigned' => $at(30), 'accepted' => $at(22)],
            ],
            [
                'number' => 'ORD-10246', 'step' => 'out_for_delivery', 'customer' => 'Mohit Raj', 'phone' => '9701122334',
                'address' => ['Flat 8C, Ganga Heights'], 'area' => 'Kankarbagh', 'pincode' => '800020',
                'items' => [$dryFruit, $diya],
                'packages' => [$box('PKG-10246-1', '884120', [$dryFruit, $diya], $at(60))],
                'payment_method' => 'cod', 'total_paise' => 139800, 'cod_paise' => 139800,
                'note' => null, 'code' => '905617',
                'timeline' => ['assigned' => $at(95), 'accepted' => $at(90), 'picked_up' => $at(60), 'out_for_delivery' => $at(52)],
            ],
            [
                'number' => 'ORD-10243', 'step' => 'delivered', 'customer' => 'Kavita Singh', 'phone' => '9988776655',
                'address' => ['Flat 2A, Lotus Residency'], 'area' => 'Patliputra Colony', 'pincode' => '800013',
                'items' => [$kajal],
                'packages' => [$box('PKG-10243-1', '448301', [$kajal], $at(190))],
                'payment_method' => 'cod', 'total_paise' => 44700, 'cod_paise' => 44700,
                'note' => null, 'code' => '221900', 'cash_collected_paise' => 44700,
                'timeline' => ['assigned' => $at(210), 'accepted' => $at(205), 'picked_up' => $at(190), 'out_for_delivery' => $at(188), 'delivered' => $at(168)],
            ],
            [
                'number' => 'ORD-10240', 'step' => 'delivered', 'customer' => 'Deepak Jha', 'phone' => '9911223344',
                'address' => ['Hotel Gulmohar, Fraser Road'], 'area' => 'Fraser Road', 'pincode' => '800001',
                'items' => [$welcome],
                'packages' => [
                    $box('PKG-10240-1', '117823', [$welcomeFour], $at(240)),
                    $box('PKG-10240-2', '556190', [$welcomeFour], $at(240)),
                    $box('PKG-10240-3', '882045', [$welcomeFour], $at(240)),
                ],
                'payment_method' => 'upi', 'total_paise' => 298800, 'cod_paise' => 0,
                'note' => 'Ask for the front desk manager.', 'code' => '640288',
                'timeline' => ['assigned' => $at(260), 'accepted' => $at(255), 'picked_up' => $at(240), 'out_for_delivery' => $at(238), 'delivered' => $at(218)],
            ],
            [
                'number' => 'ORD-10198', 'step' => 'failed', 'customer' => 'Ravi Ranjan', 'phone' => '9001122334',
                'address' => ['House 5, Sector 3'], 'area' => 'Digha', 'pincode' => '800011',
                'items' => [$ladoo],
                'packages' => [$box('PKG-10198-1', '330761', [$ladoo], $yesterday(11, 40))],
                'payment_method' => 'cod', 'total_paise' => 34000, 'cod_paise' => 34000,
                'note' => null, 'code' => '512773', 'failure_reason' => 'nobody_home',
                'timeline' => ['assigned' => $yesterday(11, 5), 'accepted' => $yesterday(11, 9), 'picked_up' => $yesterday(11, 40), 'out_for_delivery' => $yesterday(11, 45), 'failed' => $yesterday(12, 15)],
            ],
            [
                'number' => 'ORD-10231', 'step' => 'delivered', 'customer' => 'Priya Sharma', 'phone' => '9830012345',
                'address' => ['Flat 3B, Shanti Apartments'], 'area' => 'Boring Road', 'pincode' => '800001',
                'items' => [$dryFruit, $mugs],
                'packages' => [
                    $box('PKG-10231-1', '702394', [$dryFruit], $yesterday(15, 50)),
                    $box('PKG-10231-2', '148806', [$mugs], $yesterday(15, 50)),
                ],
                'payment_method' => 'upi', 'total_paise' => 145700, 'cod_paise' => 0,
                'note' => null, 'code' => '133906',
                'timeline' => ['assigned' => $yesterday(15, 20), 'accepted' => $yesterday(15, 24), 'picked_up' => $yesterday(15, 50), 'out_for_delivery' => $yesterday(15, 55), 'delivered' => $yesterday(16, 15)],
            ],
        ];
    }
}
