<?php

namespace App\Filament\Widgets;

use App\Enums\OrderStatus;
use App\Models\Order;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Carbon;

/**
 * Two weeks of takings, so a quiet patch is obvious at a glance.
 */
class RevenueChart extends ChartWidget
{
    protected ?string $heading = 'Takings, last 14 days';

    protected int|string|array $columnSpan = 'full';

    protected static ?int $sort = 2;

    protected ?string $pollingInterval = null;

    protected ?string $maxHeight = '260px';

    protected function getData(): array
    {
        $days = collect(range(13, 0))->map(fn (int $daysAgo): Carbon => today()->subDays($daysAgo));

        $totals = Order::query()
            ->where('status', '!=', OrderStatus::Cancelled->value)
            ->whereDate('placed_at', '>=', today()->subDays(13))
            ->get(['placed_at', 'total_paise'])
            ->groupBy(fn (Order $order): string => $order->placed_at->toDateString())
            ->map(fn ($orders): float => round($orders->sum('total_paise') / 100, 2));

        return [
            'datasets' => [[
                'label' => 'Rupees',
                'data' => $days->map(fn (Carbon $day): float => (float) ($totals[$day->toDateString()] ?? 0))->all(),
                'borderColor' => '#9b2c55',
                'backgroundColor' => 'rgba(155, 44, 85, 0.12)',
                'fill' => true,
                'tension' => 0.35,
            ]],
            'labels' => $days->map(fn (Carbon $day): string => $day->format('j M'))->all(),
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }
}
