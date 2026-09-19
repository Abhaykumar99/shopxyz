<?php

namespace App\Livewire\Account;

use App\Actions\Account\UpdatePhone;
use App\Livewire\Forms\PhoneForm;
use App\Models\User;
use App\Support\Demo\DemoOrder;
use App\Support\Demo\DemoOrders;
use Illuminate\Contracts\View\View;
use Livewire\Component;

class Profile extends Component
{
    public PhoneForm $phoneForm;

    public bool $editingPhone = false;

    public function mount(): void
    {
        $customer = $this->customer();
        $this->phoneForm->phone = (string) $customer->phone;
        $this->editingPhone = ! $customer->hasPhone();
    }

    public function savePhone(UpdatePhone $updatePhone): void
    {
        $updatePhone->handle($this->customer(), $this->phoneForm->validated());
        $this->editingPhone = false;
        $this->dispatch('toast', message: 'Mobile number saved.', tone: 'success');
    }

    public function cancelPhoneEdit(): void
    {
        $customer = $this->customer();
        $this->phoneForm->phone = (string) $customer->phone;
        $this->phoneForm->resetValidation();
        $this->editingPhone = ! $customer->hasPhone();
    }

    /**
     * The order history is still the sample one until orders move onto the
     * database (ADR-016, Phase 8).
     */
    public function render(DemoOrders $orders): View
    {
        $customer = $this->customer();
        $all = $orders->all();

        return view('livewire.account.profile', [
            'customer' => $customer,
            'activeOrders' => count(array_filter($all, fn (DemoOrder $order): bool => $order->isActive())),
            'addressCount' => $customer->addresses()->count(),
        ])->layout('layouts::account', [
            'title' => 'Your account',
            'heading' => false,
            'tab' => 'profile',
        ]);
    }

    private function customer(): User
    {
        return auth()->user() ?? abort(403);
    }
}
