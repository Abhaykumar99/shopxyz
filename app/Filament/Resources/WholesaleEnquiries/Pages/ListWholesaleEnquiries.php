<?php

namespace App\Filament\Resources\WholesaleEnquiries\Pages;

use App\Filament\Resources\WholesaleEnquiries\WholesaleEnquiryResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListWholesaleEnquiries extends ListRecords
{
    protected static string $resource = WholesaleEnquiryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
