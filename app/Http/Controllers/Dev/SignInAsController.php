<?php

namespace App\Http\Controllers\Dev;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Models\User;
use App\Support\Demo\DemoCart;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * Local-only shortcut that signs in one of the seeded accounts, so the
 * signed-in screens can be reviewed on a machine with no Google OAuth client
 * (ADR-025). The route is registered only in local and testing and is guarded
 * again by the LocalOnly middleware.
 */
final class SignInAsController extends Controller
{
    public function __invoke(Request $request, DemoCart $cart, string $role): RedirectResponse
    {
        if ($role === 'guest') {
            Auth::logout();
            $cart->clear();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->to($this->returnPath($request) ?? route('shop.home'));
        }

        $user = $this->seeded($role);

        if ($user === null) {
            return redirect()->route('shop.home')->with('toast', [
                'message' => 'No seeded '.$role.' account. Run `php artisan migrate:fresh --seed` first.',
                'tone' => 'warning',
            ]);
        }

        Auth::login($user);
        $request->session()->regenerate();

        $home = $role === 'delivery' ? route('delivery.index') : route('account.profile');

        return $this->returnPath($request)
            ? redirect()->to($this->returnPath($request))
            : redirect()->intended($home);
    }

    private function seeded(string $role): ?User
    {
        $wanted = $role === 'delivery' ? UserRole::Delivery : UserRole::Customer;

        return User::query()
            ->where('role', $wanted)
            ->where('is_active', true)
            ->oldest('id')
            ->first();
    }

    /**
     * Same-site absolute paths only. A leading "//" or "/\" is how a
     * redirect gets pointed at another host, so both are refused.
     */
    private function returnPath(Request $request): ?string
    {
        $path = (string) $request->query('return');

        if (! str_starts_with($path, '/')) {
            return null;
        }

        return str_starts_with($path, '//') || str_starts_with($path, '/'.chr(92)) ? null : $path;
    }
}
