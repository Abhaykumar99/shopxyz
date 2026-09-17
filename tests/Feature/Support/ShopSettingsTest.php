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

describe('delivery rules', function () {
    beforeEach(function () {
        config([
            'shop.delivery.charge_paise' => 4000,
            'shop.delivery.free_above_paise' => 49900,
            'shop.delivery.min_order_paise' => 9900,
            'shop.delivery.pincodes' => ['800001', '800014'],
        ]);
    });

    it('charges delivery below the threshold and nothing from the threshold up', function (int $subtotal, int $charge, int $shortfall) {
        $shop = freshShopSettings();

        expect($shop->deliveryChargeFor($subtotal))->toBe($charge)
            ->and($shop->freeDeliveryShortfall($subtotal))->toBe($shortfall);
    })->with([
        'just below' => [49899, 4000, 1],
        'exactly at the threshold' => [49900, 0, 0],
        'above' => [80000, 0, 0],
    ]);

    it('checks the minimum order value', function () {
        $shop = freshShopSettings();

        expect($shop->meetsMinimumOrder(9899))->toBeFalse()
            ->and($shop->meetsMinimumOrder(9900))->toBeTrue();
    });

    it('serves only the listed pincodes', function () {
        $shop = freshShopSettings();

        expect($shop->servesPincode('800014'))->toBeTrue()
            ->and($shop->servesPincode('110001'))->toBeFalse();
    });

    it('serves every pincode when no list is configured', function () {
        config(['shop.delivery.pincodes' => []]);

        expect(freshShopSettings()->servesPincode('110001'))->toBeTrue();
    });
});

it('builds a UPI payment link for the exact amount and order number', function () {
    config(['shop.payment.upi_vpa' => 'sweet.corner@okaxis', 'shop.payment.upi_payee_name' => 'Sweet Corner']);

    expect(freshShopSettings()->upiPaymentUri(215750, 'ORD-10245'))
        ->toBe('upi://pay?pa=sweet.corner%40okaxis&pn=Sweet%20Corner&am=2157.50&cu=INR&tn=ORD-10245');
});

it('builds no UPI link when no UPI ID is set', function () {
    config(['shop.payment.upi_vpa' => null]);

    expect(freshShopSettings()->upiPaymentUri(1000, 'ORD-1'))->toBeNull();
});
