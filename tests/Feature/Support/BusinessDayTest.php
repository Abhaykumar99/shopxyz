<?php

use App\Enums\OrderStatus;
use App\Filament\Widgets\TodayOverview;
use App\Models\Order;
use App\Models\User;
use Illuminate\Support\Carbon;
use Livewire\Livewire;

/**
 * The shop trades in Patna, so its day runs midnight to midnight IST. Under the
 * UTC default the day began at 05:30 IST, which put a late evening order and an
 * early morning order on different business days and made "today's revenue" the
 * wrong 24 hours.
 */
it('runs on the shop clock, not UTC', function () {
    expect(config('app.timezone'))->toBe('Asia/Kolkata')
        ->and(now()->getTimezone()->getName())->toBe('Asia/Kolkata');
});

it('counts an order placed late in the evening as today', function () {
    Carbon::setTestNow(Carbon::parse('2026-09-19 23:30:00', 'Asia/Kolkata'));

    $order = Order::factory()->create(['placed_at' => now(), 'total_paise' => 50000]);

    expect(Order::whereDate('placed_at', today())->pluck('id'))->toContain($order->id);
});

it('counts an order placed just after midnight as the new day, not the old one', function () {
    Carbon::setTestNow(Carbon::parse('2026-09-19 23:30:00', 'Asia/Kolkata'));
    $yesterday = Order::factory()->create(['placed_at' => now()]);

    Carbon::setTestNow(Carbon::parse('2026-09-20 00:30:00', 'Asia/Kolkata'));
    $today = Order::factory()->create(['placed_at' => now()]);

    $found = Order::whereDate('placed_at', today())->pluck('id');

    expect($found)->toContain($today->id)
        ->and($found)->not->toContain($yesterday->id);
});

it('shows the owner the revenue for the day they are actually having', function () {
    $this->actingAs(User::factory()->admin()->create());

    // 2am: under UTC this order counted as the previous day's takings.
    Carbon::setTestNow(Carbon::parse('2026-09-20 02:00:00', 'Asia/Kolkata'));

    Order::factory()->create([
        'placed_at' => now(),
        'total_paise' => 123400,
        'status' => OrderStatus::Placed,
    ]);

    Livewire::test(TodayOverview::class)
        ->assertSee('Orders today')
        ->assertSee('1')
        ->assertSee('₹1,234');
});

it('leaves a cancelled order out of the day\'s revenue', function () {
    $this->actingAs(User::factory()->admin()->create());

    Carbon::setTestNow(Carbon::parse('2026-09-20 11:00:00', 'Asia/Kolkata'));

    Order::factory()->create(['placed_at' => now(), 'total_paise' => 50000, 'status' => OrderStatus::Placed]);
    Order::factory()->create(['placed_at' => now(), 'total_paise' => 90000, 'status' => OrderStatus::Cancelled]);

    Livewire::test(TodayOverview::class)->assertSee('₹500');
});
