<?php

namespace App\Livewire\Account;

use App\Livewire\Forms\PhoneForm;
use App\Support\Demo\DemoCustomer;
use App\Support\Demo\DemoOrder;
use App\Support\Demo\DemoOrders;
use Illuminate\Contracts\View\View;
use Livewire\Component;

class Profile extends Component
{
    public PhoneForm $phoneForm;

    public bool $editingPhone = false;

    public function mount(DemoCustomer $customer): void
    {
        $this->phoneForm->phone = (string) $customer->profile()['phone'];
        $this->editingPhone = ! $customer->hasPhone();
    }

    public function savePhone(DemoCustomer $customer): void
    {
        $customer->updatePhone($this->phoneForm->validated());
        $this->editingPhone = false;
        $this->dispatch('toast', message: 'Mobile number saved.', tone: 'success');
    }

    public function cancelPhoneEdit(DemoCustomer $customer): void
    {
        $this->phoneForm->phone = (string) $customer->profile()['phone'];
        $this->phoneForm->resetValidation();
        $this->editingPhone = ! $customer->hasPhone();
    }

    public function render(DemoCustomer $customer, DemoOrders $orders): View
    {
        $all = $orders->all();

        return view('livewire.account.profile', [
            'customer' => $customer,
            'profile' => $customer->profile(),
            'activeOrders' => count(array_filter($all, fn (DemoOrder $order): bool => $order->isActive())),
            'addressCount' => count($customer->addresses()),
        ])->layout('layouts::account', [
            'title' => 'Your account',
            'heading' => false,
            'tab' => 'profile',
        ]);
    }
}
