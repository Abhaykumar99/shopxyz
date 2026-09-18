<?php

namespace App\Livewire\Account;

use App\Livewire\Forms\AddressForm;
use App\Support\Demo\DemoCustomer;
use App\Support\IndianStates;
use App\Support\ShopSettings;
use Illuminate\Contracts\View\View;
use Livewire\Component;

class AddressBook extends Component
{
    public AddressForm $addressForm;

    public ?string $deletingId = null;

    public function create(DemoCustomer $customer, ShopSettings $shop): void
    {
        $profile = $customer->profile();
        $this->addressForm->start($profile['name'], $profile['phone'], $shop->deliveryArea);
        $this->dispatch('open-modal', 'address-form');
    }

    public function edit(string $id, DemoCustomer $customer): void
    {
        $address = $customer->address($id);

        if ($address === null) {
            return;
        }

        $this->addressForm->fillFrom($address);
        $this->dispatch('open-modal', 'address-form');
    }

    public function updatedAddressFormPincode(): void
    {
        $this->validateOnly('addressForm.pincode');
    }

    public function save(DemoCustomer $customer): void
    {
        $editing = $this->addressForm->id !== null && $customer->address($this->addressForm->id) !== null;
        $customer->saveAddress($this->addressForm->payload(), $editing ? $this->addressForm->id : null);

        $this->dispatch('close-modal', 'address-form');
        $this->dispatch('toast', message: $editing ? 'Address updated.' : 'Address added.', tone: 'success');
    }

    public function confirmDelete(string $id, DemoCustomer $customer): void
    {
        if ($customer->address($id) === null) {
            return;
        }

        $this->deletingId = $id;
        $this->dispatch('open-modal', 'delete-address');
    }

    public function delete(DemoCustomer $customer): void
    {
        if ($this->deletingId !== null && $customer->address($this->deletingId) !== null) {
            $customer->deleteAddress($this->deletingId);
            $this->dispatch('toast', message: 'Address deleted.', tone: 'info');
        }

        $this->deletingId = null;
        $this->dispatch('close-modal', 'delete-address');
    }

    public function makeDefault(string $id, DemoCustomer $customer): void
    {
        if ($customer->address($id) !== null) {
            $customer->setDefaultAddress($id);
            $this->dispatch('toast', message: 'Default address updated.', tone: 'success');
        }
    }

    public function render(DemoCustomer $customer): View
    {
        return view('livewire.account.address-book', [
            'addresses' => $customer->addresses(),
            'deleting' => $this->deletingId ? $customer->address($this->deletingId) : null,
            'states' => IndianStates::options(),
            'labels' => AddressForm::LABELS,
        ])->layout('layouts::account', [
            'title' => 'Saved addresses',
            'tab' => 'addresses',
        ]);
    }
}
