<?php

namespace App\Filament\Widgets;

use App\Enums\OrderStatus;
use App\Filament\Resources\Orders\Tables\OrdersTable;
use App\Models\Order;
use App\Support\Money;
use Filament\Actions\Action;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;

/**
 * The orders the shop still has to do something about, oldest first.
 */
class NeedsAttention extends TableWidget
{
    protected static ?string $heading = 'Orders waiting on you';

    protected int|string|array $columnSpan = 'full';

    protected static ?int $sort = 3;

    public function table(Table $table): Table
    {
        return $table
            ->query(
                Order::query()
                    ->with('customer')
                    ->whereIn('status', collect(OrderStatus::openStatuses())->map(fn (OrderStatus $status): string => $status->value))
                    ->orderBy('placed_at'),
            )
            ->columns([
                TextColumn::make('order_number')
                    ->label('Order')
                    ->weight('semibold')
                    ->description(fn (Order $record): string => $record->placed_at->diffForHumans()),
                TextColumn::make('customer.name')->label('Customer')->wrap(),
                TextColumn::make('status')
                    ->badge()
                    ->formatStateUsing(fn (OrderStatus $state): string => $state->label())
                    ->color(fn (OrderStatus $state): string => OrdersTable::tone($state->tone())),
                TextColumn::make('total_paise')
                    ->label('Total')
                    ->alignEnd()
                    ->formatStateUsing(fn (int $state): string => Money::format($state)),
            ])
            ->recordActions([
                Action::make('open')
                    ->label('Open')
                    ->icon('heroicon-m-arrow-right')
                    ->url(fn (Order $record): string => route('filament.admin.resources.orders.view', $record)),
            ])
            ->paginated([5, 10, 25])
            ->defaultPaginationPageOption(5)
            ->emptyStateHeading('Nothing waiting')
            ->emptyStateDescription('Every order has been dealt with.');
    }
}
