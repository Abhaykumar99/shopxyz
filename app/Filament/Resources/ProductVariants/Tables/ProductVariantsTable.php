<?php

namespace App\Filament\Resources\ProductVariants\Tables;

use App\Enums\InventoryMovementType;
use App\Models\InventoryMovement;
use App\Models\ProductVariant;
use App\Support\Money;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

/**
 * Stock, one line per option, with every change written to the movements log.
 */
class ProductVariantsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('stock_quantity')
            ->modifyQueryUsing(fn (Builder $query): Builder => $query->with('product.category'))
            ->columns([
                TextColumn::make('product.name')
                    ->label('Product')
                    ->searchable()
                    ->sortable()
                    ->weight('semibold')
                    ->wrap()
                    ->description(fn (ProductVariant $record): string => $record->name.' · '.$record->sku),

                TextColumn::make('product.category.name')
                    ->label('Category')
                    ->badge()
                    ->color('gray')
                    ->toggleable(),

                TextColumn::make('price_paise')
                    ->label('Price')
                    ->alignEnd()
                    ->sortable()
                    ->formatStateUsing(fn (int $state): string => Money::format($state)),

                TextColumn::make('stock_quantity')
                    ->label('In stock')
                    ->alignEnd()
                    ->sortable()
                    ->badge()
                    ->color(fn (ProductVariant $record): string => match (true) {
                        $record->stock_quantity === 0 => 'danger',
                        $record->isLowStock() => 'warning',
                        default => 'success',
                    })
                    ->description(fn (ProductVariant $record): string => 'warn below '.$record->low_stock_threshold),
            ])
            ->filters([
                Filter::make('low')
                    ->label('Low or out of stock')
                    ->query(fn (Builder $query): Builder => $query->whereColumn('stock_quantity', '<=', 'low_stock_threshold'))
                    ->toggle(),
                SelectFilter::make('product')
                    ->label('Category')
                    ->relationship('product.category', 'name')
                    ->searchable(),
            ])
            ->recordActions([
                Action::make('adjust')
                    ->label('Adjust stock')
                    ->icon('heroicon-m-adjustments-horizontal')
                    ->schema([
                        Select::make('type')
                            ->label('Why')
                            ->options(InventoryMovementType::manualOptions())
                            ->default(InventoryMovementType::Restock->value)
                            ->required(),
                        TextInput::make('change')
                            ->label('Change by')
                            ->numeric()
                            ->required()
                            ->helperText('Use a negative number to take stock away.'),
                        Textarea::make('note')->label('Note')->rows(2)->maxLength(200),
                    ])
                    ->action(function (ProductVariant $record, array $data): void {
                        $after = self::adjust($record, (int) $data['change'], InventoryMovementType::from($data['type']), $data['note'] ?? null);

                        Notification::make()
                            ->title($record->sku.' is now '.$after.' in stock')
                            ->success()
                            ->send();
                    }),

                Action::make('movements')
                    ->label('History')
                    ->icon('heroicon-m-clock')
                    ->color('gray')
                    ->modalHeading(fn (ProductVariant $record): string => 'Stock history for '.$record->sku)
                    ->modalSubmitAction(false)
                    ->modalCancelActionLabel('Close')
                    ->modalContent(fn (ProductVariant $record) => view('filament.inventory.movements', [
                        'movements' => $record->inventoryMovements()->latest('created_at')->limit(25)->get(),
                    ])),
            ])
            ->emptyStateHeading('Nothing in the catalogue yet');
    }

    /**
     * Phase 5 moves this into an AdjustStock action that locks the row; the
     * screen keeps calling the same thing.
     */
    public static function adjust(ProductVariant $variant, int $change, InventoryMovementType $type, ?string $note): int
    {
        return DB::transaction(function () use ($variant, $change, $type, $note): int {
            $variant->refresh();
            $after = max(0, $variant->stock_quantity + $change);

            $variant->update(['stock_quantity' => $after]);

            InventoryMovement::create([
                'product_variant_id' => $variant->id,
                'user_id' => auth()->id(),
                'type' => $type,
                'quantity_change' => $change,
                'stock_after' => $after,
                'note' => $note ?: null,
            ]);

            return $after;
        });
    }
}
