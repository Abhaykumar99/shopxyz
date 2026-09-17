<?php

describe('access', function () {
    it('renders the developer pages outside production', function (string $uri) {
        $this->get($uri)->assertOk();
    })->with(['/dev/ui', '/dev/ui/delivery', '/dev/ui/sign-in', '/dev/ui/phone', '/dev/ui/print/label', '/dev/ui/print/invoice']);

    it('returns 404 for developer pages in production', function (string $uri) {
        app()->detectEnvironment(fn (): string => 'production');

        $this->get($uri)->assertNotFound();
    })->with(['/dev/ui', '/dev/ui/print/label']);

    it('asks search engines not to index developer pages', function () {
        $this->get('/dev/ui')->assertSee('<meta name="robots" content="noindex, nofollow">', false);
    });
});

describe('print preview', function () {
    it('prints a label on the requested paper size', function (string $format, string $pageSize) {
        $this->get("/dev/ui/print/label?format={$format}")
            ->assertSee("data-print-format=\"{$format}\"", false)
            ->assertSee("size: {$pageSize};", false);
    })->with([
        ['thermal_4x6', '100mm 150mm'],
        ['a5', '148mm 210mm'],
        ['a4', '210mm 297mm'],
    ]);

    it('uses the shop default when no format is requested', function () {
        config(['shop.print.label_format' => 'a5']);
        app()->forgetScopedInstances();

        $this->get('/dev/ui/print/label')->assertSee('data-print-format="a5"', false);
    });

    it('falls back to the shop default for a format the document does not support', function () {
        $this->get('/dev/ui/print/invoice?format=thermal_4x6')
            ->assertSee('data-print-format="a4"', false)
            ->assertDontSee('value="thermal_4x6"', false);
    });

    it('returns 404 for an unknown document type', function () {
        $this->get('/dev/ui/print/receipt')->assertNotFound();
    });

    it('shows cash to collect on COD labels and prepaid on UPI labels', function () {
        $this->get('/dev/ui/print/label')->assertSee('Collect cash')->assertSee('₹2,157');
        $this->get('/dev/ui/print/label?payment=upi')->assertSee('Prepaid')->assertDontSee('Collect cash');
    });

    it('prints the shop name from settings on labels and invoices', function (string $document) {
        config(['shop.name' => 'Sweet Corner']);
        app()->forgetScopedInstances();

        $this->get("/dev/ui/print/{$document}")->assertSee('Sweet Corner');
    })->with(['label', 'invoice']);

    it('escapes query values it carries into the format form', function () {
        $this->get('/dev/ui/print/label?payment='.urlencode('"><script>alert(1)</script>'))
            ->assertDontSee('<script>alert(1)</script>', false)
            ->assertSee('&quot;&gt;&lt;script&gt;alert(1)&lt;/script&gt;', false);
    });
});

it('only previews pages on this site in the phone frame', function (string $source) {
    $this->get('/dev/ui/phone?src='.urlencode($source))
        ->assertSee('src="'.url('/dev/ui').'"', false)
        ->assertDontSee('evil.example');
})->with(['https://evil.example', '//evil.example']);
