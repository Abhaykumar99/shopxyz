<?php

namespace App\Filament\Resources\WholesaleOrders\Tables;

use App\Enums\OrderStatus;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Filament\Resources\Orders\Tables\OrdersTable;
use App\Models\Order;
use App\Support\Money;
use Filament\Actions\Action;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

/**
 * Bulk orders the way a wholesale desk reads them (ADR-019).
 */
class WholesaleOrdersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('placed_at', 'desc')
            ->modifyQueryUsing(fn (Builder $query): Builder => $query
                ->with(['customer', 'items', 'activeAssignment.deliveryPartner'])
                ->withCount('packages'))
            ->columns([
                TextColumn::make('order_number')
                    ->label('Order')
                    ->searchable()
                    ->sortable()
                    ->weight('semibold')
                    ->description(fn (Order $record): string => $record->placed_at->diffForHumans()),

                TextColumn::make('customer.name')
                    ->label('Buyer')
                    ->searchable()
                    ->visibleFrom('sm')
                    ->wrap()
                    ->description(fn (Order $record): string => $record->ship_city.' '.$record->ship_pincode),

                TextColumn::make('units')
                    ->label('Units')
                    ->alignEnd()
                    ->state(fn (Order $record): int => (int) $record->items->sum('quantity'))
                    ->description(fn (Order $record): string => $record->items->count().' '.str('line')->plural($record->items->count())),

                TextColumn::make('slab_prices')
                    ->label('Slab prices')
                    ->visibleFrom('lg')
                    ->listWithLineBreaks()
                    ->limitList(3)
                    ->expandableLimitedList()
                    ->placeholder('Retail prices')
                    /** @return list<string> */
                    ->state(fn (Order $record): array => $record->items
                        ->where('is_wholesale', true)
                        ->map(fn ($item): string => $item->quantity.' × '.Money::format($item->unit_price_paise).' each')
                        ->values()
                        ->all()),

                TextColumn::make('saving')
                    ->label('Saved')
                    ->visibleFrom('md')
                    ->alignEnd()
                    ->color('success')
                    ->state(fn (Order $record): string => Money::format(self::savingPaise($record)))
                    ->description(fn (Order $record): ?string => ($mrp = self::mrpPaise($record)) > 0
                        ? round(self::savingPaise($record) / $mrp * 100).'% off retail'
                        : null),

                TextColumn::make('total_paise')
                    ->label('Total')
                    ->alignEnd()
                    ->sortable()
                    ->weight('semibold')
                    ->formatStateUsing(fn (int $state): string => Money::format($state)),

                TextColumn::make('payment_status')
                    ->label('Payment')
                    ->visibleFrom('sm')
                    ->badge()
                    ->formatStateUsing(fn (PaymentStatus $state): string => $state->label())
                    ->color(fn (PaymentStatus $state): string => OrdersTable::tone($state->tone()))
                    ->description(fn (Order $record): string => $record->payment_method->label()),

                TextColumn::make('packages_count')
                    ->label('Boxes')
                    ->visibleFrom('lg')
                    ->alignCenter()
                    ->badge()
                    ->color('gray')
                    ->formatStateUsing(fn (int $state): string => $state === 0 ? 'Not packed' : (string) $state),

                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->sortable()
                    ->formatStateUsing(fn (OrderStatus $state): string => $state->label())
                    ->color(fn (OrderStatus $state): string => OrdersTable::tone($state->tone())),

                TextColumn::make('activeAssignment.deliveryPartner.name')
                    ->label('Delivery')
                    ->visibleFrom('lg')
                    ->placeholder('Not assigned'),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->options(fn (): array => collect(OrderStatus::cases())
                        ->mapWithKeys(fn (OrderStatus $status): array => [$status->value => $status->label()])
                        ->all())
                    ->multiple(),

                SelectFilter::make('payment_method')
                    ->label('Payment method')
                    ->options(fn (): array => collect(PaymentMethod::cases())
                        ->mapWithKeys(fn (PaymentMethod $method): array => [$method->value => $method->label()])
                        ->all()),

                Filter::make('open')
                    ->label('Still being worked on')
                    ->query(fn (Builder $query): Builder => $query->whereIn('status', collect(OrderStatus::openStatuses())
                        ->map(fn (OrderStatus $status): string => $status->value)
                        ->all()))
                    ->toggle(),
            ])
            ->recordActions([
                Action::make('open')
                    ->label('Open')
                    ->icon('heroicon-m-arrow-right')
                    ->url(fn (Order $record): string => route('filament.admin.resources.orders.view', $record)),
            ])
            ->emptyStateHeading('No wholesale orders yet')
            ->emptyStateDescription('Orders that reach a product\'s minimum wholesale quantity appear here.');
    }

    /**
     * What the buyer saved against the shop's retail prices.
     */
    public static function savingPaise(Order $order): int
    {
        return max(0, self::mrpPaise($order) - (int) $order->items->sum('line_total_paise'));
    }

    public static function mrpPaise(Order $order): int
    {
        return (int) $order->items->sum(fn ($item): int => $item->mrp_paise * $item->quantity);
    }
}
