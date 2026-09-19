<?php

namespace App\Livewire\Delivery;

use App\Models\User;
use App\Support\Demo\DemoCash;
use App\Support\Demo\DemoDeliveries;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

/**
 * The delivery partner's own details, today's summary and sign out. The round
 * and the cash are still the sample ones until Phase 10.
 */
class Profile extends Component
{
    public function signOut(): mixed
    {
        Auth::logout();
        session()->invalidate();
        session()->regenerateToken();
        session()->flash('toast', ['message' => 'You have signed out.', 'tone' => 'info']);

        return $this->redirectRoute('delivery.login', navigate: false);
    }

    public function render(DemoDeliveries $deliveries, DemoCash $cash): View
    {
        return view('livewire.delivery.profile', [
            'partner' => $this->partner(),
            'summary' => $deliveries->summary(),
            'cashWithYou' => $cash->withYouTotal(),
        ])->layout('layouts::delivery', [
            'title' => 'Profile',
            'active' => 'profile',
        ]);
    }

    private function partner(): User
    {
        return auth()->user() ?? abort(403);
    }
}
