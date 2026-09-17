<?php

it('shows four labelled desktop slides and keeps the phone banner', function () {
    $html = $this->get('/')->getContent();

    expect($html)->toMatch('#aria-roledescription="carousel".*?class="[^"]*hidden[^"]*lg:block#s');
    $this->get('/')
        ->assertSee('aria-roledescription="carousel"', false)
        ->assertSee('aria-label="1 of 4: Sweets, beauty and gifts"', false)
        ->assertSee('aria-label="4 of 4: Wholesale"', false)
        ->assertSee('sm:rounded-sheet sm:px-8 lg:hidden', false);
});

it('offers pause, previous, next and slide controls', function () {
    $this->get('/')
        ->assertSee("x-bind:aria-label=\"paused ? 'Play slides' : 'Pause slides'\"", false)
        ->assertSee('aria-label="Previous slide"', false)
        ->assertSee('aria-label="Next slide"', false)
        ->assertSee('aria-label="Show slide 3: Beauty offers"', false);
});

it('does not auto-play for people who prefer reduced motion', function () {
    $this->get('/')->assertSee("paused: window.matchMedia('(prefers-reduced-motion: reduce)').matches", false);
});

it('hides inactive slides from keyboard and screen readers until shown', function () {
    $this->get('/')->assertSee('inert aria-hidden="true" style="opacity: 0"', false);
});

it('keeps the carousel state when the page updates', function () {
    $this->get('/')->assertSee('wire:ignore', false);
});
