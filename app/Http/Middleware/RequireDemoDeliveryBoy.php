<?php

namespace App\Http\Middleware;

use App\Support\Demo\DemoDeliveryBoy;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * TEMPORARY (Phase 3): sends anyone who is not signed in to the delivery
 * sign-in page. Replaced in Phase 4 by `auth` plus the `delivery` role
 * middleware, with staff accounts created by the admin (ADR-004).
 */
final class RequireDemoDeliveryBoy
{
    public function __construct(private readonly DemoDeliveryBoy $deliveryBoy) {}

    /**
     * @param  Closure(Request): Response  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (! $this->deliveryBoy->isSignedIn()) {
            return redirect()->guest(route('delivery.login'));
        }

        return $next($request);
    }
}
