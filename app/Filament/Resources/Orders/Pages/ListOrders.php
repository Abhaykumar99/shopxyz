<?php

namespace App\Filament\Resources\Orders\Pages;

use App\Enums\OrderStatus;
use App\Filament\Resources\Orders\OrderResource;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Tabs\Tab;
use Illuminate\Database\Eloquent\Builder;

/**
 * The board the shop works through: everything still open first, then the
 * queues that need attention, with a count on each tab.
 */
class ListOrders extends ListRecords
{
    protected static string $resource = OrderResource::class;

    public function getTabs(): array
    {
        $count = fn (?callable $modify = null): int => $this->tabCount($modify);

        return [
            'open' => Tab::make('To do')
                ->modifyQueryUsing(fn (Builder $query): Builder => $query->whereIn('status', self::openValues()))
                ->badge($count(fn (Builder $query): Builder => $query->whereIn('status', self::openValues())))
                ->badgeColor('primary'),

            'new' => Tab::make('New')
                ->modifyQueryUsing(fn (Builder $query): Builder => $query->where('status', OrderStatus::Placed))
                ->badge($count(fn (Builder $query): Builder => $query->where('status', OrderStatus::Placed))),

            'packing' => Tab::make('Packing')
                ->modifyQueryUsing(fn (Builder $query): Builder => $query->whereIn('status', [OrderStatus::Confirmed->value, OrderStatus::Packing->value]))
                ->badge($count(fn (Builder $query): Builder => $query->whereIn('status', [OrderStatus::Confirmed->value, OrderStatus::Packing->value]))),

            'ready' => Tab::make('Ready to send')
                ->modifyQueryUsing(fn (Builder $query): Builder => $query->where('status', OrderStatus::Packed))
                ->badge($count(fn (Builder $query): Builder => $query->where('status', OrderStatus::Packed))),

            'delivering' => Tab::make('Out for delivery')
                ->modifyQueryUsing(fn (Builder $query): Builder => $query->whereIn('status', [OrderStatus::Assigned->value, OrderStatus::OutForDelivery->value]))
                ->badge($count(fn (Builder $query): Builder => $query->whereIn('status', [OrderStatus::Assigned->value, OrderStatus::OutForDelivery->value]))),

            'delivered' => Tab::make('Delivered')
                ->modifyQueryUsing(fn (Builder $query): Builder => $query->where('status', OrderStatus::Delivered)),

            'problems' => Tab::make('Problems')
                ->modifyQueryUsing(fn (Builder $query): Builder => $query->whereIn('status', [OrderStatus::DeliveryFailed->value, OrderStatus::Cancelled->value]))
                ->badge($count(fn (Builder $query): Builder => $query->whereIn('status', [OrderStatus::DeliveryFailed->value, OrderStatus::Cancelled->value])))
                ->badgeColor('danger'),

            'all' => Tab::make('All'),
        ];
    }

    /**
     * @return list<string>
     */
    private static function openValues(): array
    {
        return collect(OrderStatus::openStatuses())->map(fn (OrderStatus $status): string => $status->value)->all();
    }

    private function tabCount(callable $modify): int
    {
        return $modify(static::getResource()::getEloquentQuery())->count();
    }
}
