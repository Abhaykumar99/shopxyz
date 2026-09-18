<?php

use App\Livewire\Shop\Search;
use Livewire\Livewire;

it('finds products by name, brand or category', function (string $query, string $expected) {
    Livewire::withQueryParams(['q' => $query])
        ->test(Search::class)
        ->assertSee($expected);
})->with([
    'name' => ['ladoo', 'Rose gulkand ladoo'],
    'brand' => ['cocoa lane', 'Artisan chocolate bar trio'],
    'subcategory' => ['fragrance', 'Jasmine body mist'],
]);

it('shows every product when nothing is searched, across pages', function () {
    Livewire::test(Search::class)
        ->assertSee('33 products')
        ->assertSee('Page 1 of 2')
        ->call('nextPage')
        ->assertSee('Page 2 of 2');
});

it('suggests categories when nothing matches', function () {
    Livewire::withQueryParams(['q' => 'bicycle'])
        ->test(Search::class)
        ->assertSee('Nothing found for “bicycle”')
        ->assertSee('Confectionery');
});

it('escapes the search term in the page', function () {
    $this->get('/search?q='.urlencode('<script>alert(1)</script>'))
        ->assertOk()
        ->assertDontSee('<script>alert(1)</script>', false);
});

it('resets to the first page when the search changes', function () {
    Livewire::test(Search::class)
        ->call('nextPage')
        ->set('q', 'kajal')
        ->assertSee('Smudge-proof kajal')
        ->assertDontSee('Page 2');
});
