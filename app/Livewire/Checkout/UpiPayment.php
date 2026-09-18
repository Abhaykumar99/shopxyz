<?php

namespace App\Livewire\Checkout;

use App\Livewire\Forms\PaymentProofForm;
use App\Support\Demo\DemoOrder;
use App\Support\Demo\DemoOrders;
use App\Support\ShopSettings;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Locked;
use Livewire\Component;
use Livewire\WithFileUploads;

/**
 * Pay by UPI, then submit the screenshot and UTR for manual verification.
 */
class UpiPayment extends Component
{
    use WithFileUploads;

    #[Locked]
    public string $number;

    public PaymentProofForm $proof;

    public function mount(string $order, DemoOrders $orders): mixed
    {
        $found = $orders->find($order);
        abort_if($found === null, 404);

        $this->number = $order;

        if (! $found->needsPaymentProof()) {
            return $this->redirectRoute('account.order', $order);
        }

        return null;
    }

    public function submit(DemoOrders $orders): mixed
    {
        $key = 'upi-proof:'.session()->getId();
        if (RateLimiter::tooManyAttempts($key, 5)) {
            throw ValidationException::withMessages([
                'proof.utr' => 'Too many attempts. Please wait '.RateLimiter::availableIn($key).' seconds and try again.',
            ]);
        }
        RateLimiter::hit($key, 600);

        $utr = $this->proof->validatedUtr();

        if ($orders->utrInUse($utr, $this->number)) {
            throw ValidationException::withMessages([
                'proof.utr' => 'This UTR number is already used on another order. Check the number in your UPI app.',
            ]);
        }

        // Phase 6: re-encode the image and store it on the private disk (ADR-009).
        $this->proof->screenshot?->delete();

        if (! $orders->submitPaymentProof($this->number, $utr)) {
            return $this->redirectRoute('account.order', $this->number);
        }

        session()->flash('toast', ['message' => 'Payment details sent. We will confirm your order shortly.', 'tone' => 'success']);

        return $this->redirectRoute('orders.placed', $this->number);
    }

    public function render(DemoOrders $orders, ShopSettings $shop): View
    {
        /** @var DemoOrder $order */
        $order = $orders->find($this->number) ?? abort(404);

        return view('livewire.checkout.upi-payment', [
            'order' => $order,
            'upiLink' => $shop->upiPaymentUri($order->total(), $order->number),
        ])->layout('layouts::shop', [
            'title' => "Pay for {$order->number}",
            'active' => 'orders',
            'noindex' => true,
        ]);
    }
}
