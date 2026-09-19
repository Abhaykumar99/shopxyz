<?php

use App\Livewire\Shop\CategoryShow;
use Livewire\Livewire;

beforeEach(function () {
    seedCatalog();
});

it('lists the products of a category and its subcategories', function () {
    $this->get('/c/cosmetics')
        ->assertOk()
        ->assertSee('Velvet matte lipstick')
        ->assertSee('Oud and amber attar')
        ->assertDontSee('Kaju katli with silver leaf');
});

it('lists only the products of a subcategory', function () {
    $this->get('/c/lips')
        ->assertOk()
        ->assertSee('Tinted lip balm with SPF 15')
        ->assertDontSee('Smudge-proof kajal');
});

it('returns 404 for an unknown category', function () {
    $this->get('/c/perfume-bottles')->assertNotFound();
});

it('filters by brand, price and stock', function () {
    Livewire::test(CategoryShow::class, ['category' => 'cosmetics'])
        ->set('brands', ['Green Leaf'])
        ->assertSee('Aloe and cucumber face gel')
        ->assertDontSee('Velvet matte lipstick')
        ->set('price', 'over-1000')
        ->assertSee('No products match these filters')
        ->call('clearFilters')
        ->assertSee('Velvet matte lipstick');
});

it('hides out of stock products when asked', function () {
    Livewire::test(CategoryShow::class, ['category' => 'face'])
        ->assertSee('Cream blush stick')
        ->set('inStock', true)
        ->assertDontSee('Cream blush stick');
});

it('sorts by price', function () {
    Livewire::test(CategoryShow::class, ['category' => 'mithai'])
        ->set('sort', 'price_asc')
        ->assertSeeInOrder(['Motichoor ladoo', 'Kaju katli with silver leaf', 'Assorted mithai box'])
        ->set('sort', 'price_desc')
        ->assertSeeInOrder(['Assorted mithai box', 'Kaju katli with silver leaf', 'Motichoor ladoo']);
});

it('falls back to popular order for an unknown sort value', function () {
    Livewire::withQueryParams(['sort' => 'drop table'])
        ->test(CategoryShow::class, ['category' => 'mithai'])
        ->assertOk()
        ->assertSeeInOrder(['Kaju katli with silver leaf', 'Motichoor ladoo']);
});

it('keeps filters in the address bar', function () {
    Livewire::withQueryParams(['brands' => ['Mithai Ghar'], 'in_stock' => '1'])
        ->test(CategoryShow::class, ['category' => 'confectionery'])
        ->assertSet('brands', ['Mithai Ghar'])
        ->assertSet('inStock', true)
        ->assertDontSee('Dark chocolate truffles');
});

it('removes one brand filter from its chip', function () {
    Livewire::test(CategoryShow::class, ['category' => 'confectionery'])
        ->set('brands', ['Mithai Ghar', 'Cocoa Lane'])
        ->call('removeBrand', 'Cocoa Lane')
        ->assertSet('brands', ['Mithai Ghar']);
});
