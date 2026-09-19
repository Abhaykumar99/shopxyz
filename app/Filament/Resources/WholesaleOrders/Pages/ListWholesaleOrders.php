<?php

namespace App\Filament\Resources\WholesaleOrders\Pages;

use App\Enums\OrderStatus;
use App\Filament\Resources\WholesaleOrders\WholesaleOrderResource;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Tabs\Tab;
use Illuminate\Database\Eloquent\Builder;

class ListWholesaleOrders extends ListRecords
{
    protected static string $resource = WholesaleOrderResource::class;

    public function getTabs(): array
    {
        $open = collect(OrderStatus::openStatuses())->map(fn (OrderStatus $status): string => $status->value)->all();

        return [
            'open' => Tab::make('To do')
                ->modifyQueryUsing(fn (Builder $query): Builder => $query->whereIn('status', $open))
                ->badge(static::getResource()::getEloquentQuery()->whereIn('status', $open)->count()),

            'delivered' => Tab::make('Delivered')
                ->modifyQueryUsing(fn (Builder $query): Builder => $query->where('status', OrderStatus::Delivered)),

            'all' => Tab::make('All'),
        ];
    }
}
