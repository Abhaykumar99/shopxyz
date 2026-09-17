<?php

use App\Http\Controllers\InfoPageController;

it('renders every information page', function (string $slug) {
    $this->get(route('pages.show', $slug))
        ->assertOk()
        ->assertSee('<h1 class="text-3xl font-bold">'.InfoPageController::PAGES[$slug].'</h1>', false);
})->with(array_keys(InfoPageController::PAGES));

it('marks placeholder wording until the shop replaces it', function () {
    $this->get(route('pages.show', 'terms'))->assertSee('Sample wording.');
});

it('lists the delivery pincodes on the contact page', function () {
    $this->get(route('pages.show', 'contact'))
        ->assertSee('Where we deliver')
        ->assertSee('800014')
        ->assertDontSee('Sample wording.');
});

it('returns 404 for unknown pages', function () {
    $this->get('/pages/careers')->assertNotFound();
});

it('shows the branded 404 page', function () {
    $this->get('/no-such-page')
        ->assertNotFound()
        ->assertSee("We couldn't find that page")
        ->assertSee('Search products');
});
