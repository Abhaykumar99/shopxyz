<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;

/**
 * Collects Content-Security-Policy violations while the policy is report-only,
 * so the enforcing policy can be written from what the site actually loads
 * rather than from guesswork. Browsers post here unauthenticated, so the body is
 * size-capped, the route is throttled, and nothing is echoed back.
 */
final class CspReportController extends Controller
{
    private const MAX_BYTES = 8192;

    public function __invoke(Request $request): Response
    {
        $body = $request->getContent();

        if ($body !== '' && strlen($body) <= self::MAX_BYTES) {
            $report = json_decode($body, true);

            Log::warning('CSP violation reported.', [
                'report' => is_array($report) ? ($report['csp-report'] ?? $report) : null,
            ]);
        }

        // 204: the browser is not waiting for anything from us.
        return response()->noContent();
    }
}
