<?php

namespace App\Filament\Resources\Products\RelationManagers;

use App\Support\Money;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

/**
 * Wholesale price bands (ADR-019): from this many units, each one costs this
 * much. The smallest band is the minimum wholesale quantity.
 */
class PriceSlabsRelationManager extends RelationManager
{
    protected static string $relationship = 'priceSlabs';

    protected static ?string $title = 'Wholesale prices';

    public function form(Schema $schema): Schema
    {
        return $schema->columns(2)->components([
            Select::make('product_variant_id')
                ->label('Option')
                ->relationship('variant', 'name')
                ->required(),
            TextInput::make('min_quantity')
                ->label('From this many')
                ->integer()
                ->required()
                ->minValue(2)
                ->maxValue(100000),
            TextInput::make('unit_price_paise')
                ->label('Price each, in paise')
                ->integer()
                ->required()
                ->minValue(1)
                ->maxValue(100000000)
                ->helperText('Keep this below the retail price of that option.'),
            Toggle::make('is_active')->label('In use')->default(true),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('min_quantity')
            ->defaultSort('min_quantity')
            ->columns([
                TextColumn::make('variant.name')->label('Option'),
                TextColumn::make('min_quantity')->label('From')->alignEnd(),
                TextColumn::make('unit_price_paise')
                    ->label('Each')
                    ->alignEnd()
                    ->formatStateUsing(fn (int $state): string => Money::format($state)),
                TextColumn::make('is_active')->label('In use')->badge()
                    ->formatStateUsing(fn (bool $state): string => $state ? 'Yes' : 'No')
                    ->color(fn (bool $state): string => $state ? 'success' : 'gray'),
            ])
            ->headerActions([CreateAction::make()])
            ->recordActions([EditAction::make(), DeleteAction::make()])
            ->emptyStateHeading('Retail only')
            ->emptyStateDescription('Add a band to offer this product at wholesale prices.');
    }
}
