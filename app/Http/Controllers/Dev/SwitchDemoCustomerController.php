<?php

namespace App\Http\Controllers\Dev;

use App\Http\Controllers\Controller;
use App\Support\Demo\DemoCustomer;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * Local-only switch between guest and signed-in customer previews (Phase 2),
 * standing in for Google sign-in until Phase 4.
 */
final class SwitchDemoCustomerController extends Controller
{
    public function __invoke(Request $request, DemoCustomer $customer, string $role): RedirectResponse
    {
        if ($role === 'guest') {
            $customer->signOut();

            return redirect()->to($this->returnPath($request) ?? route('shop.home'));
        }

        $customer->signIn();

        return $this->returnPath($request)
            ? redirect()->to($this->returnPath($request))
            : redirect()->intended(route('account.profile'));
    }

    /**
     * Only same-site paths, never another host.
     */
    private function returnPath(Request $request): ?string
    {
        $path = (string) $request->query('return');

        return preg_match('#^/(?![/\\\\])#', $path) ? $path : null;
    }
}
