<?php

namespace App\Livewire\Checkout;

use App\Enums\PaymentMethod;
use App\Livewire\Forms\AddressForm;
use App\Livewire\Forms\PhoneForm;
use App\Support\Demo\DemoAddress;
use App\Support\Demo\DemoCart;
use App\Support\Demo\DemoCustomer;
use App\Support\Demo\DemoOrders;
use App\Support\IndianStates;
use App\Support\ShopSettings;
use Illuminate\Contracts\View\View;
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

    public ?string $addressId = null;

    public string $paymentMethod = 'cod';

    public string $note = '';

    public function mount(DemoCart $cart, DemoCustomer $customer, ShopSettings $shop): mixed
    {
        if ($cart->isEmpty()) {
            return $this->redirectRoute('cart.show');
        }

        $this->editingPhone = ! $customer->hasPhone();
        $this->phoneForm->phone = (string) $customer->profile()['phone'];

        $default = collect($customer->addresses())->first(fn (DemoAddress $address): bool => $shop->servesPincode($address->pincode));
        $this->addressId = $default?->id;

        return null;
    }

    public function savePhone(DemoCustomer $customer): void
    {
        $customer->updatePhone($this->phoneForm->validated());
        $this->editingPhone = false;
        $this->dispatch('toast', message: 'Mobile number saved.', tone: 'success');
    }

    public function newAddress(DemoCustomer $customer, ShopSettings $shop): void
    {
        $profile = $customer->profile();
        $this->addressForm->start($profile['name'], $profile['phone'], $shop->deliveryArea);
        $this->dispatch('open-modal', 'checkout-address');
    }

    public function updatedAddressFormPincode(): void
    {
        $this->validateOnly('addressForm.pincode');
    }

    public function saveAddress(DemoCustomer $customer): void
    {
        $address = $customer->saveAddress($this->addressForm->payload());
        $this->addressId = $address->id;
        $this->dispatch('close-modal', 'checkout-address');
        $this->dispatch('toast', message: 'Address saved.', tone: 'success');
    }

    public function placeOrder(DemoCart $cart, DemoCustomer $customer, DemoOrders $orders, ShopSettings $shop): mixed
    {
        $this->note = trim($this->note);

        $this->validate([
            'paymentMethod' => ['required', Rule::enum(PaymentMethod::class)],
            'note' => ['nullable', 'string', 'max:200'],
            'addressId' => ['required', 'string'],
        ], [
            'addressId.required' => 'Choose where we should deliver.',
            'paymentMethod.required' => 'Choose how you want to pay.',
            'note.max' => 'Keep the note under 200 characters.',
        ]);

        if (! $customer->hasPhone()) {
            $this->editingPhone = true;
            throw ValidationException::withMessages(['phoneForm.phone' => 'Add your mobile number so the delivery partner can reach you.']);
        }

        $address = $customer->address($this->addressId);
        if ($address === null || ! $shop->servesPincode($address->pincode)) {
            throw ValidationException::withMessages(['addressId' => 'We don\'t deliver to this address yet. Choose or add another one.']);
        }

        $summary = $cart->summary($shop);
        if ($cart->isEmpty() || $cart->hasUnavailableLines() || ! $summary['meets_minimum']) {
            session()->flash('toast', ['message' => 'Your bag changed. Please review it before placing the order.', 'tone' => 'warning']);

            return $this->redirectRoute('cart.show');
        }

        $method = PaymentMethod::from($this->paymentMethod);
        $order = $orders->place($cart, $address, $method, $this->note);

        return $method === PaymentMethod::Upi
            ? $this->redirectRoute('orders.pay', $order->number)
            : $this->redirectRoute('orders.placed', $order->number);
    }

    public function render(DemoCart $cart, DemoCustomer $customer, ShopSettings $shop): View
    {
        return view('livewire.checkout.checkout-page', [
            'lines' => $cart->lines(),
            'summary' => $cart->summary($shop),
            'profile' => $customer->profile(),
            'addresses' => $customer->addresses(),
            'states' => IndianStates::options(),
            'labels' => AddressForm::LABELS,
        ])->layout('layouts::shop', [
            'title' => 'Checkout',
            'active' => 'cart',
            'noindex' => true,
        ]);
    }
}
