<?php

namespace App\Filament\Widgets;

use App\Enums\CashSettlementStatus;
use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Models\CodSettlement;
use App\Models\Order;
use App\Models\ProductVariant;
use App\Support\Money;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

/**
 * The numbers the shop owner checks first thing: what came in today, what is
 * waiting on them, and what money is in the air.
 */
class TodayOverview extends StatsOverviewWidget
{
    protected ?string $heading = 'Today';

    protected int|string|array $columnSpan = 'full';

    protected static ?int $sort = 1;

    protected function getColumns(): int
    {
        return 4;
    }

    protected function getStats(): array
    {
        $today = Order::whereDate('placed_at', today());
        $todayCount = (clone $today)->count();
        $todayRevenue = (clone $today)->where('status', '!=', OrderStatus::Cancelled->value)->sum('total_paise');

        $toVerify = Order::where('payment_status', PaymentStatus::PendingVerification)->count();
        $toPack = Order::whereIn('status', [OrderStatus::Confirmed->value, OrderStatus::Packing->value])->count();
        $toAssign = Order::where('status', OrderStatus::Packed)->count();
        $cashOut = CodSettlement::where('status', CashSettlementStatus::AwaitingVerification)->sum('amount_paise');
        $lowStock = ProductVariant::whereColumn('stock_quantity', '<=', 'low_stock_threshold')
            ->where('is_active', true)
            ->count();

        return [
            Stat::make('Orders today', (string) $todayCount)
                ->description(Money::format((int) $todayRevenue).' in orders')
                ->descriptionIcon('heroicon-m-currency-rupee')
                ->color('primary'),

            Stat::make('UPI to check', (string) $toVerify)
                ->description($toVerify > 0 ? 'Customers are waiting' : 'Nothing waiting')
                ->descriptionIcon('heroicon-m-qr-code')
                ->color($toVerify > 0 ? 'warning' : 'success'),

            Stat::make('To pack', (string) $toPack)
                ->description($toAssign.' packed, waiting for a delivery partner')
                ->descriptionIcon('heroicon-m-archive-box')
                ->color($toPack > 0 ? 'info' : 'success'),

            Stat::make('Cash with the shop', Money::format((int) $cashOut))
                ->description($lowStock.' products low on stock')
                ->descriptionIcon('heroicon-m-banknotes')
                ->color($cashOut > 0 ? 'warning' : 'success'),
        ];
    }
}
