<?php

use App\Models\User;
use Illuminate\Support\Facades\Log;

it('sends the baseline headers on every audience of the site', function (string $path) {
    $this->get($path)
        ->assertHeader('X-Frame-Options', 'SAMEORIGIN')
        ->assertHeader('X-Content-Type-Options', 'nosniff')
        ->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin')
        ->assertHeaderMissing('Strict-Transport-Security');
})->with(['/', '/login', '/delivery/login', '/categories']);

it('covers the admin panel, which builds its own middleware stack', function () {
    $this->actingAs(User::factory()->admin()->create())
        ->get('/admin')
        ->assertOk()
        ->assertHeader('X-Frame-Options', 'SAMEORIGIN')
        ->assertHeader('X-Content-Type-Options', 'nosniff');
});

it('reports the content security policy rather than enforcing it', function () {
    $response = $this->get('/');

    $response->assertHeaderMissing('Content-Security-Policy');

    $policy = $response->headers->get('Content-Security-Policy-Report-Only');

    expect($policy)->toContain("default-src 'self'")
        ->toContain("frame-ancestors 'self'")
        ->toContain("object-src 'none'")
        ->toContain('report-uri');
});

it('pins HTTPS only when the request already arrived over HTTPS', function () {
    $this->get('https://localhost/')
        ->assertHeader('Strict-Transport-Security', 'max-age=31536000; includeSubDomains');
});

it('logs a violation report and answers with nothing', function () {
    Log::spy();

    $this->call('POST', '/csp-report', [], [], [], [], json_encode([
        'csp-report' => ['violated-directive' => 'script-src', 'blocked-uri' => 'https://evil.example/x.js'],
    ]))->assertNoContent();

    Log::shouldHaveReceived('warning')->once();
});

it('ignores an oversized report body', function () {
    Log::spy();

    $this->call('POST', '/csp-report', [], [], [], [], str_repeat('a', 9000))
        ->assertNoContent();

    Log::shouldNotHaveReceived('warning');
});

it('throttles the report endpoint', function () {
    foreach (range(1, 30) as $attempt) {
        $this->call('POST', '/csp-report', [], [], [], [], '{}');
    }

    $this->call('POST', '/csp-report', [], [], [], [], '{}')->assertStatus(429);
});
