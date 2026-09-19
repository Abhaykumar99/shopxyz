<?php

namespace App\Filament\Resources\WholesaleEnquiries\Pages;

use App\Filament\Resources\WholesaleEnquiries\WholesaleEnquiryResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditWholesaleEnquiry extends EditRecord
{
    protected static string $resource = WholesaleEnquiryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
