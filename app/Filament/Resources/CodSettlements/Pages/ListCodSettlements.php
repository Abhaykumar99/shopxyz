<?php

namespace App\Filament\Resources\CodSettlements\Pages;

use App\Filament\Resources\CodSettlements\CodSettlementResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListCodSettlements extends ListRecords
{
    protected static string $resource = CodSettlementResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
