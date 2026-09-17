<?php

use App\Enums\PrintDocument;
use App\Enums\PrintFormat;
use App\Support\ShopSettings;

function freshShopSettings(): ShopSettings
{
    app()->forgetScopedInstances();

    return app(ShopSettings::class);
}

it('reads shop details from the shop config', function () {
    config(['shop.name' => 'Sweet Corner', 'shop.whatsapp' => '+91 90000 12345']);

    $shop = freshShopSettings();

    expect($shop->name)->toBe('Sweet Corner')
        ->and($shop->initials())->toBe('SC')
        ->and($shop->whatsappLink())->toBe('https://wa.me/919000012345');
});

it('uses the configured print formats', function () {
    config(['shop.print.label_format' => 'a5', 'shop.print.invoice_format' => 'a5']);

    $shop = freshShopSettings();

    expect($shop->printFormat(PrintDocument::Label))->toBe(PrintFormat::A5)
        ->and($shop->printFormat(PrintDocument::Invoice))->toBe(PrintFormat::A5);
});

it('falls back to the default format when the configured one is unknown or not allowed', function () {
    config(['shop.print.label_format' => 'letter', 'shop.print.invoice_format' => 'thermal_4x6']);

    $shop = freshShopSettings();

    expect($shop->labelFormat)->toBe(PrintFormat::Thermal4x6)
        ->and($shop->invoiceFormat)->toBe(PrintFormat::A4);
});

it('returns no WhatsApp link when no number is set', function () {
    config(['shop.whatsapp' => null]);

    expect(freshShopSettings()->whatsappLink())->toBeNull();
});

it('shows the configured shop name in page titles and the footer', function () {
    config(['shop.name' => 'Sweet Corner']);
    app()->forgetScopedInstances();

    $this->get('/dev/ui')
        ->assertSee('<title>Design system | Sweet Corner</title>', false)
        ->assertSee('© '.now()->year.' Sweet Corner');
});
