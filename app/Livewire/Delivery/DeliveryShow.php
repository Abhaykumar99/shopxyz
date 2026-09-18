<?php

namespace App\Livewire\Delivery;

use App\Enums\DeliveryFailureReason;
use App\Enums\DeliveryStep;
use App\Support\Demo\DemoDeliveries;
use App\Support\Demo\DemoDeliveryJob;
use App\Support\Money;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Locked;
use Livewire\Component;

/**
 * One delivery: boxes, address, items, payment, and the step the delivery boy is
 * on. Pickup is confirmed box by box with the code printed on each label, and
 * delivery with the customer's 6-digit OTP plus the full cash for a COD order.
 * Both codes have a limited number of tries (ADR-020, ADR-021).
 */
class DeliveryShow extends Component
{
    #[Locked]
    public string $number;

    public string $code = '';

    /** @var array<string, string> Pickup codes typed per package */
    public array $pickupCodes = [];

    public string $cash = '';

    public string $failureReason = '';

    public string $failureNote = '';

    public function mount(string $order, DemoDeliveries $deliveries): void
    {
        $job = $deliveries->find($order) ?? abort(404);

        $this->number = $job->number;
        $this->cash = $job->isCod() ? (string) (int) round($job->codPaise / 100) : '';
    }

    /**
     * Confirms one box with the pickup code printed on its label (ADR-021).
     */
    public function verifyPickup(string $packageId, DemoDeliveries $deliveries): void
    {
        $job = $this->job($deliveries);
        $package = $job->package($packageId);

        if ($package === null || $package->isPickedUp()) {
            return;
        }

        $field = "pickupCodes.{$packageId}";

        if ($deliveries->pickupAttemptsLeft($this->number) === 0) {
            throw ValidationException::withMessages([$field => 'Too many wrong codes. Ask the shop to check the boxes for this order.']);
        }

        $this->validate(
            ["pickupCodes.{$packageId}" => ['required', 'digits:6']],
            [
                "pickupCodes.{$packageId}.required" => 'Enter the 6-digit pickup code printed on the label.',
                "pickupCodes.{$packageId}.digits" => 'The pickup code is 6 digits.',
            ],
        );

        $result = $deliveries->verifyPickup($this->number, $packageId, (string) $this->pickupCodes[$packageId]);

        if (! $result['verified']) {
            $this->pickupCodes[$packageId] = '';

            throw ValidationException::withMessages([
                $field => $result['attempts_left'] > 0
                    ? "That code does not match {$package->label()}. Check the label. ".$result['attempts_left'].' '.Str::plural('try', $result['attempts_left']).' left.'
                    : 'Too many wrong codes. Ask the shop to check the boxes for this order.',
            ]);
        }

        unset($this->pickupCodes[$packageId]);
        $left = count($this->job($deliveries)->pendingPackages());

        $this->dispatch('toast', message: $result['all_picked_up']
            ? 'All boxes verified. You are out for delivery.'
            : $package->label()." verified. {$left} ".Str::plural('box', $left).' left to collect.', tone: 'success');
    }

    public function advance(DemoDeliveries $deliveries): void
    {
        $step = $deliveries->advance($this->number);

        if ($step === null) {
            return;
        }

        $this->dispatch('toast', message: $step === DeliveryStep::Accepted
            ? 'Accepted. Enter the pickup code on each box label at the counter.'
            : 'Out for delivery. The customer can see you are on the way.', tone: 'success');
    }

    public function confirmDelivery(DemoDeliveries $deliveries): void
    {
        $job = $this->job($deliveries);

        if ($job->step !== DeliveryStep::OutForDelivery) {
            return;
        }

        if ($deliveries->codeAttemptsLeft($this->number) === 0) {
            throw ValidationException::withMessages([
                'code' => 'Too many wrong OTPs. Please call the shop before handing the parcel over.',
            ]);
        }

        $this->validate([
            'code' => ['required', 'digits:6'],
            'cash' => $job->isCod() ? ['required', 'numeric', 'integer', 'min:0'] : ['nullable'],
        ], [
            'code.required' => 'Ask the customer for the 6-digit OTP on their order page.',
            'code.digits' => 'The OTP is 6 digits.',
            'cash.required' => 'Enter how much cash you collected.',
        ]);

        $cashPaise = $job->isCod() ? (int) round((float) $this->cash * 100) : 0;

        if ($job->isCod() && $cashPaise !== $job->codPaise) {
            throw ValidationException::withMessages([
                'cash' => 'Collect the full '.Money::format($job->codPaise).'. If the customer cannot pay, use "Could not deliver".',
            ]);
        }

        $left = $deliveries->deliver($this->number, $this->code, $cashPaise);

        if ($left !== null) {
            $this->reset('code');

            throw ValidationException::withMessages([
                'code' => $left > 0
                    ? "That OTP is not right. {$left} ".Str::plural('try', $left).' left.'
                    : 'Too many wrong OTPs. Please call the shop before handing the parcel over.',
            ]);
        }

        $this->dispatch('toast', message: "{$this->number} delivered. Well done.", tone: 'success');
    }

    public function reportFailure(DemoDeliveries $deliveries): void
    {
        $job = $this->job($deliveries);
        $reason = DeliveryFailureReason::tryFrom($this->failureReason);

        $this->validate([
            'failureReason' => ['required', Rule::enum(DeliveryFailureReason::class)],
            'failureNote' => [$reason?->needsNote() ? 'required' : 'nullable', 'string', 'max:200'],
        ], [
            'failureReason.required' => 'Choose what happened.',
            'failureNote.required' => 'Add a short note so the shop can sort it out.',
            'failureNote.max' => 'Keep the note under 200 characters.',
        ]);

        if ($job->step === DeliveryStep::Assigned) {
            throw ValidationException::withMessages(['failureReason' => 'Accept the delivery first, or call the shop to give it back.']);
        }

        $deliveries->fail($this->number, $reason ?? DeliveryFailureReason::NobodyHome, trim($this->failureNote));
        $this->dispatch('close-modal', 'delivery-failed');
        $this->dispatch('toast', message: 'Recorded. The shop will call the customer.', tone: 'info');
    }

    public function render(DemoDeliveries $deliveries): View
    {
        $job = $this->job($deliveries);

        return view('livewire.delivery.delivery-show', [
            'job' => $job,
            'attemptsLeft' => $deliveries->codeAttemptsLeft($this->number),
            'pickupAttemptsLeft' => $deliveries->pickupAttemptsLeft($this->number),
            'reasons' => DeliveryFailureReason::options(),
            'needsNote' => DeliveryFailureReason::tryFrom($this->failureReason)?->needsNote() ?? false,
        ])->layout('layouts::delivery', [
            'title' => $job->number,
            'back' => route('delivery.index'),
            'active' => 'deliveries',
        ]);
    }

    private function job(DemoDeliveries $deliveries): DemoDeliveryJob
    {
        return $deliveries->find($this->number) ?? abort(404);
    }
}
