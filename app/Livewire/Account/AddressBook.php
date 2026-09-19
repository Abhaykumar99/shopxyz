<?php

namespace App\Livewire\Account;

use App\Actions\Account\DeleteAddress;
use App\Actions\Account\SaveAddress;
use App\Actions\Account\SetDefaultAddress;
use App\Livewire\Forms\AddressForm;
use App\Models\Address;
use App\Models\User;
use App\Support\IndianStates;
use App\Support\ShopSettings;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Locked;
use Livewire\Component;

class AddressBook extends Component
{
    public AddressForm $addressForm;

    /**
     * Only ever set by `confirmDelete()` after the address has been resolved
     * through the signed-in customer, so the browser cannot choose it freely.
     */
    #[Locked]
    public ?int $deletingId = null;

    public function create(ShopSettings $shop): void
    {
        $customer = $this->customer();
        $this->addressForm->start($customer->name, $customer->phone, $shop->deliveryArea);
        $this->dispatch('open-modal', 'address-form');
    }

    public function edit(int $id): void
    {
        $address = $this->find($id);

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

    public function save(SaveAddress $saveAddress): void
    {
        $editing = $this->addressForm->id !== null ? $this->find($this->addressForm->id) : null;

        $saveAddress->handle($this->customer(), $this->addressForm->payload(), $editing);

        $this->dispatch('close-modal', 'address-form');
        $this->dispatch('toast', message: $editing !== null ? 'Address updated.' : 'Address added.', tone: 'success');
    }

    public function confirmDelete(int $id): void
    {
        if ($this->find($id) === null) {
            return;
        }

        $this->deletingId = $id;
        $this->dispatch('open-modal', 'delete-address');
    }

    public function delete(DeleteAddress $deleteAddress): void
    {
        $address = $this->deletingId !== null ? $this->find($this->deletingId) : null;

        if ($address !== null) {
            $deleteAddress->handle($this->customer(), $address);
            $this->dispatch('toast', message: 'Address deleted.', tone: 'info');
        }

        $this->deletingId = null;
        $this->dispatch('close-modal', 'delete-address');
    }

    public function makeDefault(int $id, SetDefaultAddress $setDefault): void
    {
        $address = $this->find($id);

        if ($address !== null) {
            $setDefault->handle($this->customer(), $address);
            $this->dispatch('toast', message: 'Default address updated.', tone: 'success');
        }
    }

    public function render(): View
    {
        $customer = $this->customer();

        return view('livewire.account.address-book', [
            'addresses' => $customer->addresses()->orderByDesc('is_default')->orderBy('id')->get(),
            'deleting' => $this->deletingId !== null ? $this->find($this->deletingId) : null,
            'states' => IndianStates::options(),
            'labels' => AddressForm::LABELS,
        ])->layout('layouts::account', [
            'title' => 'Saved addresses',
            'tab' => 'addresses',
        ]);
    }

    private function customer(): User
    {
        return auth()->user() ?? abort(403);
    }

    /**
     * Every id arriving from the browser is looked up through the signed-in
     * customer's own addresses, so another customer's row can never be reached.
     */
    private function find(int $id): ?Address
    {
        return $this->customer()->addresses()->find($id);
    }
}
