<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * A closed or switched-off account stops working on the next request, not at
 * the next sign-in (docs/security.md). Laravel's `auth` middleware only asks
 * whether there is a session, never whether the person is still welcome.
 */
final class EnsureUserIsActive
{
    /**
     * @param  Closure(Request): Response  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user !== null && ! $user->is_active) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            // Back to the sign-in page that belongs to the area they were in.
            $signIn = $request->is('delivery', 'delivery/*') ? 'delivery.login' : 'auth.login';

            return redirect()->route($signIn)->with('toast', [
                'message' => 'That account is no longer active. Please contact the shop.',
                'tone' => 'warning',
            ]);
        }

        return $next($request);
    }
}
