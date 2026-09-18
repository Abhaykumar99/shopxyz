<?php

namespace App\Filament\Resources\CodSettlements;

use App\Filament\Resources\CodSettlements\Pages\ListCodSettlements;
use App\Filament\Resources\CodSettlements\Schemas\CodSettlementForm;
use App\Filament\Resources\CodSettlements\Tables\CodSettlementsTable;
use App\Models\CodSettlement;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class CodSettlementResource extends Resource
{
    protected static ?string $model = CodSettlement::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBanknotes;

    protected static string|UnitEnum|null $navigationGroup = 'Money';

    protected static ?int $navigationSort = 2;

    protected static ?string $navigationLabel = 'COD settlement';

    protected static ?string $modelLabel = 'handover';

    protected static ?string $recordTitleAttribute = 'reference';

    public static function form(Schema $schema): Schema
    {
        return CodSettlementForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return CodSettlementsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function getPages(): array
    {
        return [
            'index' => ListCodSettlements::route('/'),
        ];
    }
}
