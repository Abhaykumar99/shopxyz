<?php

namespace App\Filament\Resources\Products\RelationManagers;

use App\Models\ProductVariant;
use App\Support\Money;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\ColorPicker;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

/**
 * A product's options: shades, weights or simply "Standard". Price and stock
 * live here, in paise (ADR-005).
 */
class VariantsRelationManager extends RelationManager
{
    protected static string $relationship = 'variants';

    protected static ?string $title = 'Options, price and stock';

    public function form(Schema $schema): Schema
    {
        return $schema->columns(2)->components([
            TextInput::make('name')->label('Option name')->required()->maxLength(255)->default('Standard'),
            TextInput::make('sku')->label('SKU')->required()->maxLength(32)->unique(ignoreRecord: true),
            TextInput::make('mrp_paise')
                ->label('MRP in paise')
                ->integer()
                ->minValue(0)
                ->maxValue(100000000)
                ->gte('price_paise')
                ->helperText('₹499 is 49900. Leave empty if there is no MRP.'),
            TextInput::make('price_paise')
                ->label('Selling price in paise')
                ->integer()
                ->minValue(1)
                ->maxValue(100000000)
                ->required(),
            TextInput::make('stock_quantity')->label('In stock')->integer()->minValue(0)->maxValue(1000000)->default(0)->required(),
            TextInput::make('low_stock_threshold')->label('Warn below')->integer()->minValue(0)->maxValue(1000000)->default(5)->required(),
            TextInput::make('weight_grams')->label('Weight in grams')->integer()->minValue(0)->maxValue(1000000),
            ColorPicker::make('swatch_hex')->label('Shade colour'),
            Toggle::make('is_active')->label('Available')->default(true),
            TextInput::make('sort_order')->label('Order')->integer()->minValue(0)->maxValue(9999)->default(0),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('name')
            ->defaultSort('sort_order')
            ->columns([
                TextColumn::make('name')->label('Option')->weight('medium'),
                TextColumn::make('sku')->label('SKU')->fontFamily('mono')->searchable(),
                TextColumn::make('price_paise')
                    ->label('Price')
                    ->alignEnd()
                    ->formatStateUsing(fn (int $state): string => Money::format($state))
                    ->description(fn (ProductVariant $record): ?string => $record->mrp_paise && $record->mrp_paise > $record->price_paise
                        ? 'MRP '.Money::format($record->mrp_paise)
                        : null),
                TextColumn::make('stock_quantity')
                    ->label('Stock')
                    ->alignEnd()
                    ->badge()
                    ->color(fn (ProductVariant $record): string => match (true) {
                        $record->stock_quantity === 0 => 'danger',
                        $record->isLowStock() => 'warning',
                        default => 'success',
                    }),
                TextColumn::make('price_slabs_count')
                    ->label('Wholesale bands')
                    ->counts('priceSlabs')
                    ->alignCenter(),
            ])
            ->headerActions([CreateAction::make()])
            ->recordActions([EditAction::make(), DeleteAction::make()]);
    }
}
