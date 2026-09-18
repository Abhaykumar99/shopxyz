<?php

namespace App\Filament\Resources\DeliveryPartners;

use App\Enums\UserRole;
use App\Filament\Resources\DeliveryPartners\Pages\ListDeliveryPartners;
use App\Filament\Resources\DeliveryPartners\Schemas\DeliveryPartnerForm;
use App\Filament\Resources\DeliveryPartners\Tables\DeliveryPartnersTable;
use App\Models\User;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use UnitEnum;

/**
 * The delivery team. The admin creates these accounts and sets their password;
 * staff never sign in with Google (ADR-004).
 */
class DeliveryPartnerResource extends Resource
{
    protected static ?string $model = User::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedIdentification;

    protected static string|UnitEnum|null $navigationGroup = 'People';

    protected static ?int $navigationSort = 2;

    protected static ?string $navigationLabel = 'Delivery partners';

    protected static ?string $modelLabel = 'delivery partner';

    protected static ?string $recordTitleAttribute = 'name';

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->where('role', UserRole::Delivery);
    }

    public static function form(Schema $schema): Schema
    {
        return DeliveryPartnerForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return DeliveryPartnersTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListDeliveryPartners::route('/'),
        ];
    }
}
