<?php

namespace App\Filament\Widgets;

use App\Models\ProductVariant;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;

/**
 * What to reorder, lowest first.
 */
class LowStock extends TableWidget
{
    protected static ?string $heading = 'Running low';

    protected int|string|array $columnSpan = 'full';

    protected static ?int $sort = 4;

    protected static ?string $pollingInterval = null;

    public function table(Table $table): Table
    {
        return $table
            ->query(
                ProductVariant::query()
                    ->with('product')
                    ->where('is_active', true)
                    ->whereColumn('stock_quantity', '<=', 'low_stock_threshold')
                    ->orderBy('stock_quantity'),
            )
            ->columns([
                TextColumn::make('product.name')
                    ->label('Product')
                    ->wrap()
                    ->description(fn (ProductVariant $record): string => $record->name.' · '.$record->sku),
                TextColumn::make('stock_quantity')
                    ->label('Left')
                    ->alignEnd()
                    ->badge()
                    ->color(fn (int $state): string => $state === 0 ? 'danger' : 'warning')
                    ->formatStateUsing(fn (int $state): string => $state === 0 ? 'Out of stock' : (string) $state),
            ])
            ->paginated([5, 10])
            ->defaultPaginationPageOption(5)
            ->emptyStateHeading('Everything is in stock');
    }
}
