<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Support\Demo\DemoCustomer;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Route;

/**
 * Customer sign-in page and sign-out.
 *
 * Phase 2: the Google button signs in the demo customer on developer machines.
 * Phase 4 replaces this with Laravel Socialite (ADR-004).
 */
final class SessionController extends Controller
{
    public function create(DemoCustomer $customer): View|RedirectResponse
    {
        if ($customer->isSignedIn()) {
            return redirect()->intended(route('account.profile'));
        }

        return view('auth.sign-in', [
            'googleUrl' => Route::has('dev.ui.as') ? route('dev.ui.as', 'customer') : null,
            'returningToCheckout' => str_contains((string) session('url.intended'), '/checkout'),
        ]);
    }

    public function destroy(DemoCustomer $customer): RedirectResponse
    {
        $customer->signOut();

        return redirect()->route('shop.home')->with('toast', ['message' => 'You have signed out.', 'tone' => 'info']);
    }
}
