<?php

use App\Models\Category;
use App\Models\Product;

/**
 * Search engines should find the catalogue the admin manages, and nothing that
 * belongs to staff or to one customer.
 */
it('lists the shop pages and the active catalogue', function () {
    $category = Category::factory()->create(['slug' => 'cosmetics', 'is_active' => true]);
    $product = Product::factory()->create([
        'slug' => 'velvet-matte-lipstick',
        'category_id' => $category->id,
        'is_active' => true,
    ]);

    $response = $this->get('/sitemap.xml')
        ->assertOk()
        ->assertHeader('Content-Type', 'application/xml');

    $response->assertSee(route('shop.home'), escape: false)
        ->assertSee(route('shop.category', $category->slug), escape: false)
        ->assertSee(route('shop.product', $product->slug), escape: false)
        ->assertSee(route('wholesale.index'), escape: false);
});

it('leaves out what is switched off, private or for staff', function () {
    $hidden = Category::factory()->create(['slug' => 'retired-range', 'is_active' => false]);
    $draft = Product::factory()->create(['slug' => 'draft-product', 'is_active' => false]);

    $this->get('/sitemap.xml')
        ->assertOk()
        ->assertDontSee($hidden->slug)
        ->assertDontSee($draft->slug)
        ->assertDontSee('/admin')
        ->assertDontSee('/delivery')
        ->assertDontSee('/checkout')
        ->assertDontSee('/account');
});

it('keeps crawlers out of the staff areas and the private pages', function () {
    $robots = file_get_contents(public_path('robots.txt'));

    expect($robots)->toContain('Disallow: /admin')
        ->toContain('Disallow: /delivery')
        ->toContain('Disallow: /checkout')
        ->toContain('Disallow: /account')
        ->toContain('Sitemap: /sitemap.xml');
});
