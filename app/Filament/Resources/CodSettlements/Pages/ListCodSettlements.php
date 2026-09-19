<?php

namespace App\Filament\Resources\CodSettlements\Pages;

use App\Enums\CashSettlementStatus;
use App\Filament\Resources\CodSettlements\CodSettlementResource;
use App\Filament\Widgets\CashPosition;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Tabs\Tab;
use Illuminate\Database\Eloquent\Builder;

/**
 * Cash handovers, split by what the shop still has to do with them (ADR-022),
 * with each partner's position above so nothing goes missing.
 */
class ListCodSettlements extends ListRecords
{
    protected static string $resource = CodSettlementResource::class;

    protected function getHeaderWidgets(): array
    {
        return [CashPosition::class];
    }

    public function getTabs(): array
    {
        return [
            'to_count' => Tab::make('To count')
                ->modifyQueryUsing(fn (Builder $query): Builder => $query->where('status', CashSettlementStatus::AwaitingVerification))
                ->badge(static::getResource()::getEloquentQuery()->where('status', CashSettlementStatus::AwaitingVerification)->count())
                ->badgeColor('warning'),

            'short' => Tab::make('Short')
                ->modifyQueryUsing(fn (Builder $query): Builder => $query->where('status', CashSettlementStatus::Short))
                ->badge(static::getResource()::getEloquentQuery()->where('status', CashSettlementStatus::Short)->count())
                ->badgeColor('danger'),

            'settled' => Tab::make('Settled')
                ->modifyQueryUsing(fn (Builder $query): Builder => $query->where('status', CashSettlementStatus::Settled)),

            'all' => Tab::make('All'),
        ];
    }
}
