<?php

namespace App\Livewire\Delivery;

use App\Enums\UserRole;
use App\Models\User;
use App\Rules\IndianMobile;
use App\Support\IndianPhone;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;
use Livewire\Component;

/**
 * Delivery panel sign-in: the mobile number the admin put on the account, plus
 * the password they were given (ADR-004, client question 6). Staff never sign
 * in with Google.
 */
class SignIn extends Component
{
    public string $phone = '';

    public string $password = '';

    public bool $showPassword = false;

    public function mount(): mixed
    {
        return auth()->user()?->isDeliveryPartner()
            ? $this->redirectIntended(route('delivery.index'))
            : null;
    }

    public function submit(): mixed
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

        $partner = $this->partnerFor($this->phone);

        if ($partner === null || ! Hash::check($this->password, (string) $partner->password)) {
            RateLimiter::hit($key, 900);
            $this->reset('password');

            // One message for every failure, so the form never reveals which
            // numbers belong to an account.
            throw ValidationException::withMessages(['phone' => 'That number and password do not match. Please try again.']);
        }

        RateLimiter::clear($key);

        Auth::login($partner);
        session()->regenerate();
        $partner->forceFill(['last_login_at' => now()])->save();

        return $this->redirectIntended(route('delivery.index'), navigate: false);
    }

    /**
     * Only an active delivery partner can sign in here. A customer or an admin
     * with the same number is not a match.
     */
    private function partnerFor(string $phone): ?User
    {
        return User::query()
            ->where('phone', IndianPhone::normalize($phone))
            ->where('role', UserRole::Delivery)
            ->where('is_active', true)
            ->whereNotNull('password')
            ->first();
    }

    public function render(): View
    {
        return view('livewire.delivery.sign-in', [
            // Shown only on a developer machine, by the view.
            'seededPartner' => app()->environment('local')
                ? User::where('role', UserRole::Delivery)->where('is_active', true)->oldest('id')->value('phone')
                : null,
        ])->layout('layouts::app', [
            'title' => 'Delivery sign in',
            'noindex' => true,
        ]);
    }
}
