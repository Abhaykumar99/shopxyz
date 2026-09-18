<?php

namespace App\Filament\Resources\Orders\Tables;

use App\Enums\OrderStatus;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Models\Order;
use App\Support\Money;
use Filament\Actions\Action;
use Filament\Actions\BulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

/**
 * The orders board. Ordered oldest-first within a status so the shop works
 * through the queue, with the money, payment and delivery state on one row.
 */
class OrdersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('placed_at', 'desc')
            ->modifyQueryUsing(fn (Builder $query): Builder => $query->with(['customer', 'activeAssignment.deliveryPartner']))
            ->columns([
                TextColumn::make('order_number')
                    ->label('Order')
                    ->searchable()
                    ->sortable()
                    ->weight('semibold')
                    ->description(fn (Order $record): string => $record->placed_at->diffForHumans()),

                TextColumn::make('customer.name')
                    ->label('Customer')
                    ->searchable()
                    ->description(fn (Order $record): string => $record->ship_city.' '.$record->ship_pincode)
                    ->wrap(),

                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->sortable()
                    ->formatStateUsing(fn (OrderStatus $state): string => $state->label())
                    ->color(fn (OrderStatus $state): string => self::tone($state->tone())),

                TextColumn::make('payment_status')
                    ->label('Payment')
                    ->badge()
                    ->formatStateUsing(fn (PaymentStatus $state, Order $record): string => $record->payment_method->label().' · '.$state->label())
                    ->color(fn (PaymentStatus $state): string => self::tone($state->tone()))
                    ->wrap(),

                TextColumn::make('total_paise')
                    ->label('Total')
                    ->alignEnd()
                    ->sortable()
                    ->formatStateUsing(fn (int $state): string => Money::format($state))
                    ->description(fn (Order $record): ?string => $record->has_wholesale_items ? 'Wholesale' : null, position: 'above'),

                TextColumn::make('activeAssignment.deliveryPartner.name')
                    ->label('Delivery')
                    ->placeholder('Not assigned')
                    ->toggleable(),

                TextColumn::make('placed_at')
                    ->label('Placed')
                    ->dateTime('j M, g:i a')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
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

                SelectFilter::make('payment_status')
                    ->label('Payment status')
                    ->options(fn (): array => collect(PaymentStatus::cases())
                        ->mapWithKeys(fn (PaymentStatus $status): array => [$status->value => $status->label()])
                        ->all())
                    ->multiple(),

                Filter::make('wholesale')
                    ->label('Wholesale orders')
                    ->query(fn (Builder $query): Builder => $query->where('has_wholesale_items', true))
                    ->toggle(),

                Filter::make('today')
                    ->label('Placed today')
                    ->query(fn (Builder $query): Builder => $query->whereDate('placed_at', today()))
                    ->toggle(),
            ])
            ->recordActions([
                ViewAction::make(),
                Action::make('confirm')
                    ->label('Confirm')
                    ->icon('heroicon-m-check-circle')
                    ->color('primary')
                    ->visible(fn (Order $record): bool => $record->status === OrderStatus::Placed)
                    ->requiresConfirmation()
                    ->modalDescription(fn (Order $record): string => $record->payment_method === PaymentMethod::Upi && $record->payment_status !== PaymentStatus::Verified
                        ? 'This UPI payment has not been verified yet. Confirm only if you have checked it.'
                        : 'The customer will see that the order is confirmed.')
                    ->action(fn (Order $record) => self::moveTo($record, OrderStatus::Confirmed)),

                Action::make('pack')
                    ->label('Start packing')
                    ->icon('heroicon-m-archive-box')
                    ->visible(fn (Order $record): bool => $record->status === OrderStatus::Confirmed)
                    ->action(fn (Order $record) => self::moveTo($record, OrderStatus::Packing)),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    BulkAction::make('confirmSelected')
                        ->label('Confirm selected')
                        ->icon('heroicon-m-check-circle')
                        ->requiresConfirmation()
                        ->action(function (Collection $records): void {
                            DB::transaction(function () use ($records): void {
                                foreach ($records as $record) {
                                    if ($record instanceof Order && $record->status === OrderStatus::Placed) {
                                        self::moveTo($record, OrderStatus::Confirmed);
                                    }
                                }
                            });
                        })
                        ->deselectRecordsAfterCompletion(),
                ]),
            ])
            ->emptyStateHeading('No orders here')
            ->emptyStateDescription('Orders appear as customers place them.');
    }

    /**
     * Maps the shop's tone names onto Filament's colours, so a status looks the
     * same in the admin as it does on the customer's order page.
     */
    public static function tone(string $tone): string
    {
        return match ($tone) {
            'info' => 'info',
            'offer' => 'warning',
            'brand' => 'primary',
            'success' => 'success',
            'danger' => 'danger',
            default => 'gray',
        };
    }

    /**
     * Phase 6 replaces this with an Action that checks the transition and writes
     * the status history; the admin screens keep calling the same thing.
     */
    public static function moveTo(Order $order, OrderStatus $status): void
    {
        if (! $order->status->canTransitionTo($status)) {
            return;
        }

        DB::transaction(function () use ($order, $status): void {
            $order->statusHistories()->create([
                'from_status' => $order->status,
                'to_status' => $status,
                'changed_by' => auth()->id(),
            ]);

            $order->update([
                'status' => $status,
                'confirmed_at' => $status === OrderStatus::Confirmed ? now() : $order->confirmed_at,
                'packed_at' => $status === OrderStatus::Packed ? now() : $order->packed_at,
                'delivered_at' => $status === OrderStatus::Delivered ? now() : $order->delivered_at,
                'cancelled_at' => $status === OrderStatus::Cancelled ? now() : $order->cancelled_at,
            ]);
        });
    }
}
