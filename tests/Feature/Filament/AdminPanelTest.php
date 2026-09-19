<?php

use App\Models\Order;
use App\Models\User;

function admin(): User
{
    return User::factory()->admin()->create();
}

it('keeps the admin panel behind sign-in', function () {
    $this->get('/admin')->assertRedirect('/admin/login');
});

it('lets an active admin open the panel', function () {
    $this->actingAs(admin())->get('/admin')->assertOk();
});

it('keeps customers and delivery partners out', function (string $state) {
    $user = User::factory()->{$state}()->create();

    $this->actingAs($user)->get('/admin')->assertForbidden();
})->with(['googleCustomer', 'deliveryPartner']);

it('keeps a deactivated admin out', function () {
    $this->actingAs(User::factory()->admin()->inactive()->create())->get('/admin')->assertForbidden();
});

it('lists orders', function () {
    $order = Order::factory()->create();

    $this->actingAs(admin())
        ->get('/admin/orders')
        ->assertOk()
        ->assertSee($order->order_number);
});

it('shows one order', function () {
    $order = Order::factory()->create();
    $order->items()->create([
        'product_name' => 'Kaju katli with silver leaf',
        'variant_name' => '500 g box',
        'sku' => 'MG-KK-500',
        'mrp_paise' => 52000,
        'unit_price_paise' => 52000,
        'quantity' => 2,
        'line_total_paise' => 104000,
    ]);

    $this->actingAs(admin())
        ->get('/admin/orders/'.$order->getKey())
        ->assertOk()
        ->assertSee($order->order_number)
        ->assertSee('Kaju katli with silver leaf');
});

it('lists homepage banners in order', function () {
    seedHomepage();

    $this->actingAs(admin())
        ->get('/admin/banners')
        ->assertOk()
        ->assertSee('Sweets, beauty and gifts, delivered today.')
        ->assertSee('Desktop hero slide');
});
