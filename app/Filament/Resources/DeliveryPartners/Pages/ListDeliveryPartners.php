<?php

namespace App\Filament\Resources\DeliveryPartners\Pages;

use App\Filament\Resources\DeliveryPartners\DeliveryPartnerResource;
use Filament\Resources\Pages\ManageRecords;

/**
 * Partners are added and edited in a sheet on the list itself: there is not
 * enough on an account to justify its own page.
 */
class ListDeliveryPartners extends ManageRecords
{
    protected static string $resource = DeliveryPartnerResource::class;
}
