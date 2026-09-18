<?php

namespace App\Livewire\Delivery;

use App\Rules\IndianMobile;
use App\Support\Demo\DemoDeliveryBoy;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;
use Livewire\Component;

/**
 * Delivery panel sign-in (Phase 3, demo credentials).
 * Phase 4 replaces this with the staff guard; the screen and its rate limit stay.
 */
class SignIn extends Component
{
    public string $phone = '';

    public string $password = '';

    public bool $showPassword = false;

    public function mount(DemoDeliveryBoy $deliveryBoy): mixed
    {
        return $deliveryBoy->isSignedIn() ? $this->redirectIntended(route('delivery.index')) : null;
    }

    public function submit(DemoDeliveryBoy $deliveryBoy): mixed
    {
        $this->validate([
            'phone' => ['required', 'string', new IndianMobile],
            'password' => ['required', 'string', 'min:6', 'max:72'],
        ], [
            'phone.required' => 'Enter the mobile number the shop gave you.',
            'password.required' => 'Enter your password.',
            'password.min' => 'Passwords are at least 6 characters.',
        ]);

        $key = 'delivery-login:'.request()->ip();

        if (RateLimiter::tooManyAttempts($key, 5)) {
            throw ValidationException::withMessages([
                'phone' => 'Too many attempts. Wait '.ceil(RateLimiter::availableIn($key) / 60).' minutes, or call the shop.',
            ]);
        }

        if (! $deliveryBoy->credentialsMatch($this->phone, $this->password)) {
            RateLimiter::hit($key, 900);
            $this->reset('password');

            throw ValidationException::withMessages(['phone' => 'That number and password do not match. Please try again.']);
        }

        RateLimiter::clear($key);
        $deliveryBoy->signIn();

        return $this->redirectIntended(route('delivery.index'), navigate: false);
    }

    public function render(): View
    {
        return view('livewire.delivery.sign-in')->layout('layouts::app', [
            'title' => 'Delivery sign in',
            'noindex' => true,
        ]);
    }
}
