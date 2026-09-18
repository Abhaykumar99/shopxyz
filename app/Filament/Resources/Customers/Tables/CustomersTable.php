<?php

namespace App\Filament\Resources\Customers\Tables;

use App\Models\User;
use App\Support\IndianPhone;
use App\Support\Money;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class CustomersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->modifyQueryUsing(fn (Builder $query): Builder => $query
                ->withCount('orders')
                ->withSum('orders as spent_paise', 'total_paise'))
            ->columns([
                TextColumn::make('name')
                    ->label('Customer')
                    ->searchable()
                    ->sortable()
                    ->weight('semibold')
                    ->description(fn (User $record): string => $record->email),

                TextColumn::make('phone')
                    ->label('Phone')
                    ->searchable()
                    ->formatStateUsing(fn (?string $state): string => $state ? IndianPhone::format($state) : 'Not given yet')
                    ->color(fn (?string $state): string => $state ? 'gray' : 'warning'),

                TextColumn::make('orders_count')
                    ->label('Orders')
                    ->alignCenter()
                    ->sortable(),

                TextColumn::make('spent_paise')
                    ->label('Spent')
                    ->alignEnd()
                    ->sortable()
                    ->formatStateUsing(fn (?int $state): string => Money::format((int) $state)),

                TextColumn::make('created_at')
                    ->label('Joined')
                    ->date('j M Y')
                    ->sortable()
                    ->toggleable(),
            ])
            ->filters([
                Filter::make('no_phone')
                    ->label('No phone number yet')
                    ->query(fn (Builder $query): Builder => $query->whereNull('phone'))
                    ->toggle(),
                Filter::make('repeat')
                    ->label('Ordered more than once')
                    ->query(fn (Builder $query): Builder => $query->has('orders', '>', 1))
                    ->toggle(),
            ])
            ->recordActions([ViewAction::make()])
            ->emptyStateHeading('No customers yet');
    }
}
