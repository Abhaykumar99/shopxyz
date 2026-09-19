<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Symfony\Component\HttpFoundation\Response;

/**
 * The headers every response should carry (docs/security.md).
 *
 * The Content-Security-Policy is sent **report-only** on purpose. Livewire
 * injects inline scripts and Alpine's `x-data` expressions need `unsafe-eval`,
 * so a policy this codebase could enforce today would have to allow exactly the
 * things a CSP exists to forbid. Report-only collects real violations first; the
 * enforcing policy comes with the Alpine CSP build in a later phase.
 */
final class SecurityHeaders
{
    /**
     * Self plus the CDNs the app actually loads from. Kept here rather than in
     * config because it is the policy, not a setting.
     */
    private const CSP = [
        "default-src 'self'",
        "base-uri 'self'",
        "form-action 'self'",
        "frame-ancestors 'self'",
        "object-src 'none'",
        "img-src 'self' data: https:",
        "font-src 'self' data:",
        "style-src 'self' 'unsafe-inline'",
        "script-src 'self' 'unsafe-inline' 'unsafe-eval'",
        "connect-src 'self'",
    ];

    /**
     * @param  Closure(Request): Response  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        // SAMEORIGIN rather than DENY: the local phone preview at /dev/ui/phone
        // frames the site itself, and same-origin framing is not the attack.
        $response->headers->set('X-Frame-Options', 'SAMEORIGIN');
        $response->headers->set('X-Content-Type-Options', 'nosniff');
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');
        $response->headers->set('Permissions-Policy', 'camera=(), microphone=(), geolocation=(), payment=(), usb=()');

        $policy = implode('; ', self::CSP);

        if (Route::has('csp.report')) {
            $policy .= '; report-uri '.route('csp.report');
        }

        $response->headers->set('Content-Security-Policy-Report-Only', $policy);

        // Only over HTTPS: pinning HTTPS from a plain-HTTP dev server would lock
        // the developer's browser out of http://localhost for a year.
        if ($request->secure()) {
            $response->headers->set('Strict-Transport-Security', 'max-age=31536000; includeSubDomains');
        }

        return $response;
    }
}
