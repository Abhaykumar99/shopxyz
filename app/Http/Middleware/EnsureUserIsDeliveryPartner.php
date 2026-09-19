<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * The delivery panel is for delivery partners only. Customers and admins get a
 * 403 rather than a redirect, so nothing here hints at what the panel holds.
 */
final class EnsureUserIsDeliveryPartner
{
    /**
     * @param  Closure(Request): Response  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        abort_unless($user?->isDeliveryPartner() && $user->is_active, 403);

        return $next($request);
    }
}
