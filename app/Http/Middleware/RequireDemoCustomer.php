<?php

namespace App\Http\Middleware;

use App\Support\Demo\DemoCustomer;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * TEMPORARY (Phase 2): sends guests to the sign-in page, remembering where they
 * were going. Replaced by Laravel's `auth` middleware with Google sign-in in Phase 4.
 */
final class RequireDemoCustomer
{
    public function __construct(private readonly DemoCustomer $customer) {}

    /**
     * @param  Closure(Request): Response  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (! $this->customer->isSignedIn()) {
            return redirect()->guest(route('auth.login'));
        }

        return $next($request);
    }
}
