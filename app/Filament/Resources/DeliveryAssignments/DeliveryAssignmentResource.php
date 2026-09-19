<?php

namespace App\Filament\Resources\DeliveryAssignments;

use App\Filament\Resources\DeliveryAssignments\Pages\ListDeliveryAssignments;
use App\Filament\Resources\DeliveryAssignments\Tables\DeliveryAssignmentsTable;
use App\Models\DeliveryAssignment;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class DeliveryAssignmentResource extends Resource
{
    protected static ?string $model = DeliveryAssignment::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedTruck;

    protected static string|UnitEnum|null $navigationGroup = 'Delivery';

    protected static ?int $navigationSort = 2;

    protected static ?string $navigationLabel = 'Deliveries';

    protected static ?string $modelLabel = 'delivery';

    protected static ?string $recordTitleAttribute = 'id';

    public static function table(Table $table): Table
    {
        return DeliveryAssignmentsTable::configure($table);
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
            'index' => ListDeliveryAssignments::route('/'),
        ];
    }
}
