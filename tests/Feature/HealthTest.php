<?php

it('serves the home page', function () {
    $this->get('/')->assertOk();
});

it('reports healthy on the health check endpoint', function () {
    $this->get('/up')->assertOk();
});

it('runs against a mysql-compatible test database', function () {
    expect(config('database.default'))->toBe('mysql')
        ->and(config('database.connections.mysql.database'))->toEndWith('_test');
});
