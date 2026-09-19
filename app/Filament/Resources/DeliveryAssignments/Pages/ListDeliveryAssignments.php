<?php

namespace App\Filament\Resources\DeliveryAssignments\Pages;

use App\Filament\Resources\DeliveryAssignments\DeliveryAssignmentResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListDeliveryAssignments extends ListRecords
{
    protected static string $resource = DeliveryAssignmentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
