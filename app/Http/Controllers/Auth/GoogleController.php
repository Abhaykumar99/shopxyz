<?php

namespace App\Http\Controllers\Auth;

use App\Actions\Auth\FindOrCreateGoogleCustomer;
use App\Actions\Auth\GoogleSignInDenied;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\AbstractProvider;
use Symfony\Component\HttpFoundation\RedirectResponse as SymfonyRedirect;
use Throwable;

/**
 * Customer sign-in through Google (ADR-004). Socialite carries the stateful
 * `state` parameter for us, so the callback only has to deal with the answer.
 */
final class GoogleController extends Controller
{
    public function redirect(): SymfonyRedirect
    {
        abort_unless(self::isConfigured(), 404);

        $google = Socialite::driver('google');

        // `with()` belongs to the OAuth 2 provider rather than the contract.
        // `select_account` lets someone pick a different account on a shared
        // machine instead of being signed straight back in as the last one.
        if ($google instanceof AbstractProvider) {
            $google->with(['prompt' => 'select_account']);
        }

        return $google->redirect();
    }

    public function callback(Request $request, FindOrCreateGoogleCustomer $findOrCreate): RedirectResponse
    {
        abort_unless(self::isConfigured(), 404);

        // The visitor pressed "cancel" on Google's own screen.
        if ($request->filled('error')) {
            return redirect()->route('auth.login')
                ->with('toast', ['message' => 'Sign-in was cancelled.', 'tone' => 'info']);
        }

        try {
            $customer = $findOrCreate->handle(Socialite::driver('google')->user());
        } catch (GoogleSignInDenied $denied) {
            return redirect()->route('auth.login')
                ->with('toast', ['message' => $denied->getMessage(), 'tone' => 'warning']);
        } catch (Throwable $failure) {
            // Includes an invalid `state`, which Socialite raises as its own
            // exception. The visitor never sees why.
            Log::warning('Google sign-in failed.', ['exception' => $failure]);

            return redirect()->route('auth.login')
                ->with('toast', ['message' => 'We could not complete the sign-in. Please try again.', 'tone' => 'warning']);
        }

        Auth::login($customer, remember: true);
        $request->session()->regenerate();

        return redirect()->intended(route('account.profile'));
    }

    /**
     * Without an OAuth client there is nothing to redirect to, so the routes
     * behave as if they do not exist and the sign-in page hides the button.
     */
    public static function isConfigured(): bool
    {
        return filled(config('services.google.client_id'))
            && filled(config('services.google.client_secret'))
            && filled(config('services.google.redirect'));
    }
}
