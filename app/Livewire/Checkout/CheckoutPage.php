<?php

namespace App\Livewire\Checkout;

use App\Actions\Account\SaveAddress;
use App\Actions\Account\UpdatePhone;
use App\Enums\PaymentMethod;
use App\Livewire\Forms\AddressForm;
use App\Livewire\Forms\PhoneForm;
use App\Models\Address;
use App\Models\User;
use App\Support\Demo\DemoCart;
use App\Support\Demo\DemoOrders;
use App\Support\IndianStates;
use App\Support\Money;
use App\Support\ShopSettings;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Livewire\Component;

/**
 * One-page checkout: mobile number, delivery address, payment method, review.
 */
class CheckoutPage extends Component
{
    public PhoneForm $phoneForm;

    public AddressForm $addressForm;

    public bool $editingPhone = false;

    public ?int $addressId = null;

    public string $paymentMethod = 'cod';

    public string $note = '';

    public function mount(DemoCart $cart, ShopSettings $shop): mixed
    {
        if ($cart->isEmpty()) {
            return $this->redirectRoute('cart.show');
        }

        if (! $cart->summary($shop)['cod_available']) {
            $this->paymentMethod = PaymentMethod::Upi->value;
        }

        $customer = $this->customer();
        $this->editingPhone = ! $customer->hasPhone();
        $this->phoneForm->phone = (string) $customer->phone;

        $default = $this->addresses()->first(fn (Address $address): bool => $shop->servesPincode($address->pincode));
        $this->addressId = $default?->id;

        return null;
    }

    public function savePhone(UpdatePhone $updatePhone): void
    {
        $updatePhone->handle($this->customer(), $this->phoneForm->validated());
        $this->editingPhone = false;
        $this->dispatch('toast', message: 'Mobile number saved.', tone: 'success');
    }

    public function newAddress(ShopSettings $shop): void
    {
        $customer = $this->customer();
        $this->addressForm->start($customer->name, $customer->phone, $shop->deliveryArea);
        $this->dispatch('open-modal', 'checkout-address');
    }

    public function updatedAddressFormPincode(): void
    {
        $this->validateOnly('addressForm.pincode');
    }

    public function saveAddress(SaveAddress $saveAddress): void
    {
        $address = $saveAddress->handle($this->customer(), $this->addressForm->payload());
        $this->addressId = $address->id;
        $this->dispatch('close-modal', 'checkout-address');
        $this->dispatch('toast', message: 'Address saved.', tone: 'success');
    }

    public function placeOrder(DemoCart $cart, DemoOrders $orders, ShopSettings $shop): mixed
    {
        $this->note = trim($this->note);

        $this->validate([
            'paymentMethod' => ['required', Rule::enum(PaymentMethod::class)],
            'note' => ['nullable', 'string', 'max:200'],
            'addressId' => ['required', 'integer'],
        ], [
            'addressId.required' => 'Choose where we should deliver.',
            'paymentMethod.required' => 'Choose how you want to pay.',
            'note.max' => 'Keep the note under 200 characters.',
        ]);

        $customer = $this->customer();

        if (! $customer->hasPhone()) {
            $this->editingPhone = true;
            throw ValidationException::withMessages(['phoneForm.phone' => 'Add your mobile number so the delivery partner can reach you.']);
        }

        // Resolved through the customer's own addresses, so a tampered id can
        // never point at someone else's.
        $address = $customer->addresses()->find($this->addressId);
        if ($address === null || ! $shop->servesPincode($address->pincode)) {
            throw ValidationException::withMessages(['addressId' => 'We don\'t deliver to this address yet. Choose or add another one.']);
        }

        $summary = $cart->summary($shop);
        if ($cart->isEmpty() || $cart->hasUnavailableLines() || ! $summary['meets_minimum']) {
            session()->flash('toast', ['message' => 'Your bag changed. Please review it before placing the order.', 'tone' => 'warning']);

            return $this->redirectRoute('cart.show');
        }

        $method = PaymentMethod::from($this->paymentMethod);

        if ($method === PaymentMethod::Cod && ! $summary['cod_available']) {
            $this->paymentMethod = PaymentMethod::Upi->value;

            throw ValidationException::withMessages([
                'paymentMethod' => 'Cash on delivery is not available above '.Money::format($shop->codMaxPaise).'. We have selected UPI instead.',
            ]);
        }

        $order = $orders->place($cart, $address, $method, $this->note);

        return $method === PaymentMethod::Upi
            ? $this->redirectRoute('orders.pay', $order->number)
            : $this->redirectRoute('orders.placed', $order->number);
    }

    public function render(DemoCart $cart, ShopSettings $shop): View
    {
        return view('livewire.checkout.checkout-page', [
            'lines' => $cart->lines(),
            'summary' => $cart->summary($shop),
            'customer' => $this->customer(),
            'addresses' => $this->addresses(),
            'states' => IndianStates::options(),
            'labels' => AddressForm::LABELS,
        ])->layout('layouts::shop', [
            'title' => 'Checkout',
            'active' => 'cart',
            'noindex' => true,
        ]);
    }

    private function customer(): User
    {
        return auth()->user() ?? abort(403);
    }

    /**
     * @return Collection<int, Address>
     */
    private function addresses(): Collection
    {
        return $this->customer()->addresses()->orderByDesc('is_default')->orderBy('id')->get();
    }
}
