<?php

use App\Models\Category;
use App\Support\Demo\DemoCatalog;

/**
 * What a search engine and a shared link see. The header navigation comes from
 * the categories the admin manages (ADR-024), never from a hardcoded list.
 */
it('describes a product with structured data a search result can use', function () {
    $product = DemoCatalog::products()[0];
    $variant = $product->defaultVariant();

    $response = $this->get(route('shop.product', $product->slug))->assertOk();

    $schema = json_decode(
        (string) str($response->getContent())->match('/<script type="application\/ld\+json">(.*?)<\/script>/s'),
        associative: true,
    );

    expect($schema)->toMatchArray([
        '@type' => 'Product',
        'name' => $product->name,
        'sku' => $variant->sku,
    ])
        ->and($schema['offers']['priceCurrency'])->toBe('INR')
        ->and($schema['offers']['price'])->toBe(number_format($variant->paise / 100, 2, '.', ''))
        ->and($schema['offers']['availability'])->toBe('https://schema.org/InStock');
});

it('builds the shop navigation from the categories the admin manages', function () {
    Category::factory()->create(['name' => 'Festive hampers', 'slug' => 'festive-hampers', 'sort_order' => 1]);
    Category::factory()->create(['name' => 'Switched off', 'slug' => 'switched-off', 'is_active' => false]);

    $this->get(route('shop.home'))
        ->assertOk()
        ->assertSee('Festive hampers')
        ->assertSee(route('shop.category', 'festive-hampers'), escape: false)
        ->assertDontSee('Switched off');
});

it('tells search engines to stay away from a customer private page', function () {
    signInCustomer();

    $this->get(route('cart.show'))->assertOk()->assertSee('noindex', escape: false);
    $this->get(route('account.profile'))->assertOk()->assertSee('noindex', escape: false);
});
