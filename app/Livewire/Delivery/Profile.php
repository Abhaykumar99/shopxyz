<?php

namespace App\Livewire\Delivery;

use App\Support\Demo\DemoDeliveries;
use App\Support\Demo\DemoDeliveryBoy;
use Illuminate\Contracts\View\View;
use Livewire\Component;

/**
 * The delivery boy's own details, today's summary and sign out.
 */
class Profile extends Component
{
    public function signOut(DemoDeliveryBoy $deliveryBoy): mixed
    {
        $deliveryBoy->signOut();

        session()->flash('toast', ['message' => 'You have signed out.', 'tone' => 'info']);

        return $this->redirectRoute('delivery.login', navigate: false);
    }

    public function render(DemoDeliveries $deliveries, DemoDeliveryBoy $deliveryBoy): View
    {
        return view('livewire.delivery.profile', [
            'profile' => $deliveryBoy->profile(),
            'cash' => $deliveries->cash(),
            'active' => count($deliveries->active()),
        ])->layout('layouts::delivery', [
            'title' => 'Profile',
            'active' => 'profile',
        ]);
    }
}
