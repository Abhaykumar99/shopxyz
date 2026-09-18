<?php

namespace App\Filament\Resources\Products\Tables;

use App\Models\Product;
use App\Support\Money;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

/**
 * The catalogue: what is on sale, at what price, with how much left.
 */
class ProductsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('name')
            ->modifyQueryUsing(fn (Builder $query): Builder => $query->with(['category', 'variants']))
            ->columns([
                TextColumn::make('name')
                    ->label('Product')
                    ->searchable()
                    ->sortable()
                    ->weight('semibold')
                    ->wrap()
                    ->description(fn (Product $record): string => $record->brand ?? ''),

                TextColumn::make('category.name')
                    ->label('Category')
                    ->badge()
                    ->color('gray')
                    ->sortable(),

                TextColumn::make('variants_count')
                    ->label('Options')
                    ->state(fn (Product $record): int => $record->variants->count())
                    ->alignCenter(),

                TextColumn::make('price')
                    ->label('Price')
                    ->alignEnd()
                    ->state(fn (Product $record): string => $record->variants->isEmpty()
                        ? '—'
                        : Money::format((int) $record->variants->min('price_paise')))
                    ->description(fn (Product $record): ?string => $record->variants->count() > 1 ? 'from' : null, position: 'above'),

                TextColumn::make('stock')
                    ->label('In stock')
                    ->alignEnd()
                    ->badge()
                    ->state(fn (Product $record): int => (int) $record->variants->sum('stock_quantity'))
                    ->color(fn (int $state): string => match (true) {
                        $state === 0 => 'danger',
                        $state <= 10 => 'warning',
                        default => 'success',
                    }),

                ToggleColumn::make('is_featured')->label('Featured')->alignCenter()->toggleable(),
                ToggleColumn::make('is_active')->label('On sale')->alignCenter(),
            ])
            ->filters([
                SelectFilter::make('category_id')
                    ->label('Category')
                    ->relationship('category', 'name')
                    ->searchable(),
                Filter::make('out_of_stock')
                    ->label('Out of stock')
                    ->query(fn (Builder $query): Builder => $query->whereDoesntHave('variants', fn (Builder $variants): Builder => $variants->where('stock_quantity', '>', 0)))
                    ->toggle(),
                Filter::make('featured')
                    ->label('On the homepage')
                    ->query(fn (Builder $query): Builder => $query->where('is_featured', true))
                    ->toggle(),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ])
            ->emptyStateHeading('No products yet');
    }
}
