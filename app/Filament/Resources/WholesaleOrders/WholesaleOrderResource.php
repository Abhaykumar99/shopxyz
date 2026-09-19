<?php

namespace App\Filament\Resources\WholesaleOrders;

use App\Filament\Resources\Orders\Schemas\OrderInfolist;
use App\Filament\Resources\WholesaleOrders\Pages\ListWholesaleOrders;
use App\Filament\Resources\WholesaleOrders\Tables\WholesaleOrdersTable;
use App\Models\Order;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use UnitEnum;

/**
 * Bulk orders on their own (ADR-019): the same orders as everywhere else, but
 * shown the way a wholesale desk reads them — quantities, the slab price each
 * line got, what the customer saved, boxes, payment and delivery.
 */
class WholesaleOrderResource extends Resource
{
    protected static ?string $model = Order::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBuildingStorefront;

    protected static string|UnitEnum|null $navigationGroup = 'Orders';

    protected static ?int $navigationSort = 2;

    protected static ?string $navigationLabel = 'Wholesale orders';

    protected static ?string $modelLabel = 'wholesale order';

    protected static ?string $recordTitleAttribute = 'order_number';

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->where('has_wholesale_items', true);
    }

    public static function getNavigationBadge(): ?string
    {
        $open = self::getEloquentQuery()
            ->whereIn('status', collect(\App\Enums\OrderStatus::openStatuses())->map(fn ($status): string => $status->value))
            ->count();

        return $open > 0 ? (string) $open : null;
    }

    public static function infolist(Schema $schema): Schema
    {
        return OrderInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return WholesaleOrdersTable::configure($table);
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function getPages(): array
    {
        return [
            'index' => ListWholesaleOrders::route('/'),
        ];
    }
}
