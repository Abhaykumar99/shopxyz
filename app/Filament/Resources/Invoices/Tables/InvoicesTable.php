<?php

namespace App\Filament\Resources\Invoices\Tables;

use App\Models\Invoice;
use App\Support\Money;
use Filament\Actions\Action;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

/**
 * Issued invoices. They are never edited: a correction is a new document.
 */
class InvoicesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('issued_at', 'desc')
            ->modifyQueryUsing(fn (Builder $query): Builder => $query->with('order.customer'))
            ->columns([
                TextColumn::make('invoice_number')
                    ->label('Invoice')
                    ->searchable()
                    ->weight('semibold'),

                TextColumn::make('order.order_number')
                    ->label('Order')
                    ->searchable()
                    ->url(fn (Invoice $record): string => route('filament.admin.resources.orders.view', $record->order_id)),

                TextColumn::make('order.customer.name')
                    ->label('Customer')
                    ->searchable()
                    ->wrap(),

                TextColumn::make('total_paise')
                    ->label('Total')
                    ->alignEnd()
                    ->sortable()
                    ->formatStateUsing(fn (int $state): string => Money::format($state)),

                TextColumn::make('issued_at')
                    ->label('Issued')
                    ->dateTime('j M Y, g:i a')
                    ->sortable(),
            ])
            ->filters([
                Filter::make('this_month')
                    ->label('This month')
                    ->query(fn (Builder $query): Builder => $query->whereBetween('issued_at', [now()->startOfMonth(), now()->endOfMonth()]))
                    ->toggle(),
            ])
            ->recordActions([
                Action::make('print')
                    ->label('Print')
                    ->icon('heroicon-m-printer')
                    ->url(fn (Invoice $record): string => route('admin.print.invoice', $record->order_id), shouldOpenInNewTab: true),
            ])
            ->emptyStateHeading('No invoices yet')
            ->emptyStateDescription('An invoice is issued when an order is delivered.');
    }
}
