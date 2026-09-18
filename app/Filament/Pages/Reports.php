<?php

namespace App\Filament\Pages;

use App\Enums\OrderStatus;
use App\Enums\PaymentMethod;
use App\Models\Order;
use App\Models\OrderItem;
use BackedEnum;
use Carbon\CarbonImmutable;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Collection;
use Livewire\Attributes\Url;
use UnitEnum;

/**
 * What sold, how it was paid for and what it was worth, over a period the admin
 * chooses. Read-only: every number here comes from orders.
 */
class Reports extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedChartBar;

    protected static string|UnitEnum|null $navigationGroup = 'Money';

    protected static ?int $navigationSort = 4;

    protected static ?string $title = 'Reports';

    protected string $view = 'filament.pages.reports';

    public const PERIODS = [
        'today' => 'Today',
        '7' => 'Last 7 days',
        '30' => 'Last 30 days',
        'month' => 'This month',
        '90' => 'Last 90 days',
    ];

    #[Url]
    public string $period = '30';

    public function updatedPeriod(): void
    {
        // Re-renders with the new range.
    }

    /**
     * @return array<string, mixed>
     */
    public function getViewData(): array
    {
        [$from, $to] = $this->range();

        $orders = Order::query()
            ->whereBetween('placed_at', [$from, $to])
            ->get(['id', 'status', 'payment_method', 'total_paise', 'has_wholesale_items', 'placed_at']);

        $paid = $orders->reject(fn (Order $order): bool => $order->status === OrderStatus::Cancelled);
        $revenue = (int) $paid->sum('total_paise');

        return [
            'periods' => self::PERIODS,
            'from' => $from,
            'to' => $to,
            'orderCount' => $orders->count(),
            'revenue' => $revenue,
            'averageOrder' => $paid->count() > 0 ? (int) round($revenue / $paid->count()) : 0,
            'cancelled' => $orders->where('status', OrderStatus::Cancelled)->count(),
            'delivered' => $orders->where('status', OrderStatus::Delivered)->count(),
            'wholesaleOrders' => $paid->where('has_wholesale_items', true)->count(),
            'byMethod' => [
                'Cash on delivery' => (int) $paid->where('payment_method', PaymentMethod::Cod)->sum('total_paise'),
                'UPI' => (int) $paid->where('payment_method', PaymentMethod::Upi)->sum('total_paise'),
            ],
            'byStatus' => $orders
                ->groupBy(fn (Order $order): string => $order->status->label())
                ->map(fn (Collection $group): int => $group->count())
                ->sortDesc(),
            'topProducts' => OrderItem::query()
                ->whereHas('order', fn ($query) => $query
                    ->whereBetween('placed_at', [$from, $to])
                    ->where('status', '!=', OrderStatus::Cancelled->value))
                ->selectRaw('product_name, sum(quantity) as units, sum(line_total_paise) as revenue')
                ->groupBy('product_name')
                ->orderByDesc('revenue')
                ->limit(10)
                ->get(),
        ];
    }

    /**
     * @return array{0: CarbonImmutable, 1: CarbonImmutable}
     */
    private function range(): array
    {
        $now = CarbonImmutable::now();

        return match ($this->period) {
            'today' => [$now->startOfDay(), $now->endOfDay()],
            'month' => [$now->startOfMonth(), $now->endOfMonth()],
            '7', '90' => [$now->subDays((int) $this->period)->startOfDay(), $now->endOfDay()],
            default => [$now->subDays(30)->startOfDay(), $now->endOfDay()],
        };
    }
}
