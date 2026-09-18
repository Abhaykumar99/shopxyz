<?php

namespace App\Livewire\Delivery;

use App\Enums\DeliveryFailureReason;
use App\Enums\DeliveryStep;
use App\Support\Demo\DemoDeliveries;
use App\Support\Demo\DemoDeliveryJob;
use App\Support\Money;
use Illuminate\Contracts\View\View;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Locked;
use Livewire\Component;

/**
 * One delivery: address, items, payment, and the step the delivery boy is on.
 * Delivery is confirmed with the customer's code (limited attempts) and, for a
 * COD order, the cash collected (ADR-020).
 */
class DeliveryShow extends Component
{
    #[Locked]
    public string $number;

    public string $code = '';

    public string $cash = '';

    public string $failureReason = '';

    public string $failureNote = '';

    public function mount(string $order, DemoDeliveries $deliveries): void
    {
        $job = $deliveries->find($order) ?? abort(404);

        $this->number = $job->number;
        $this->cash = $job->isCod() ? (string) (int) round($job->codPaise / 100) : '';
    }

    public function advance(DemoDeliveries $deliveries): void
    {
        $step = $deliveries->advance($this->number);

        if ($step === null) {
            return;
        }

        $this->dispatch('toast', message: match ($step) {
            DeliveryStep::Accepted => 'Accepted. Collect the parcel from the shop counter.',
            DeliveryStep::PickedUp => 'Picked up. The customer can see that you are on the way.',
            default => 'Marked as reached. Ask the customer for their delivery code.',
        }, tone: 'success');
    }

    public function confirmDelivery(DemoDeliveries $deliveries): void
    {
        $job = $this->job($deliveries);

        if ($job->step !== DeliveryStep::Reached) {
            return;
        }

        if ($deliveries->codeAttemptsLeft($this->number) === 0) {
            throw ValidationException::withMessages([
                'code' => 'Too many wrong codes. Please call the shop before handing the parcel over.',
            ]);
        }

        $this->validate([
            'code' => ['required', 'digits:6'],
            'cash' => $job->isCod() ? ['required', 'numeric', 'integer', 'min:0'] : ['nullable'],
        ], [
            'code.required' => 'Ask the customer for the 6-digit code on their order page.',
            'code.digits' => 'The delivery code is 6 digits.',
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
                    ? "That code is not right. {$left} ".($left === 1 ? 'try' : 'tries').' left.'
                    : 'Too many wrong codes. Please call the shop before handing the parcel over.',
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
