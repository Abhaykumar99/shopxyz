<?php

namespace App\Filament\Resources\Products\Pages;

use App\Actions\Catalog\StoreProductImages;
use App\Filament\Resources\Products\ProductResource;
use App\Models\Product;
use Filament\Resources\Pages\CreateRecord;

class CreateProduct extends CreateRecord
{
    protected static string $resource = ProductResource::class;

    /**
     * The photo field is not a column, so it is saved separately once the
     * product exists (and resized on the way in).
     */
    protected function afterCreate(): void
    {
        $product = $this->record;

        if ($product instanceof Product) {
            app(StoreProductImages::class)->handle(
                $product,
                array_values((array) ($this->data['images'] ?? [])),
            );
        }
    }
}
