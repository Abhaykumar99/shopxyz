<?php

use App\Models\User;
use Illuminate\Support\Facades\DB;

it('shows a branded page when someone opens a panel that is not theirs', function () {
    // Phase 6 made 403 a real outcome: the delivery panel refuses customers.
    $this->actingAs(User::factory()->googleCustomer()->create());

    $this->get('/delivery')
        ->assertForbidden()
        ->assertSee('belongs to someone else')
        ->assertSee('Go to the shop');
});

it('keeps the shop chrome on the pages people actually land on', function (string $view, string $expected) {
    $this->view($view, ['exception' => new Exception])->assertSee($expected);
})->with([
    '404' => ['errors.404', 'find that page'],
    '403' => ['errors.403', 'belongs to someone else'],
    '401' => ['errors.401', 'Please sign in first'],
]);

it('indexes the columns the admin screens filter on', function (string $table, string $index) {
    $found = collect(DB::select("SHOW INDEX FROM {$table}"))->pluck('Key_name')->unique();

    expect($found)->toContain($index);
})->with([
    'wholesale orders' => ['orders', 'orders_wholesale_placed_index'],
    'active categories' => ['categories', 'categories_active_sort_index'],
]);
