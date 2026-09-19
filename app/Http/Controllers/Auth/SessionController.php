<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Support\Demo\DemoCart;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

/**
 * The customer sign-in page and sign-out. Customers only ever sign in through
 * Google (ADR-004); there is no password form here on purpose.
 */
final class SessionController extends Controller
{
    public function create(): View|RedirectResponse
    {
        if (Auth::check()) {
            return redirect()->intended(route('account.profile'));
        }

        return view('auth.sign-in', [
            'googleUrl' => GoogleController::isConfigured() ? route('auth.google.redirect') : null,
            // Local and testing only: lets a developer review the signed-in
            // screens before the shop has a Google OAuth client (ADR-025).
            'developerUrl' => Route::has('dev.ui.as') ? route('dev.ui.as', 'customer') : null,
            'returningToCheckout' => str_contains((string) session('url.intended'), '/checkout'),
        ]);
    }

    public function destroy(Request $request, DemoCart $cart): RedirectResponse
    {
        Auth::logout();

        // The bag still lives in the session until Phase 7, so signing out has
        // to empty it: on a shared phone the next person must not inherit it.
        $cart->clear();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('shop.home')
            ->with('toast', ['message' => 'You have signed out.', 'tone' => 'info']);
    }
}
