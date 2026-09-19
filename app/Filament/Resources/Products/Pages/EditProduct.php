<?php

namespace App\Filament\Resources\Products\Pages;

use App\Actions\Catalog\StoreProductImages;
use App\Filament\Resources\Products\ProductResource;
use App\Models\Product;
use Filament\Actions\DeleteAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Resources\Pages\EditRecord;

class EditProduct extends EditRecord
{
    protected static string $resource = ProductResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
            ForceDeleteAction::make(),
            RestoreAction::make(),
        ];
    }

    /**
     * The photo field is not a column, so it is saved separately once the
     * product exists (and resized on the way in).
     */
    protected function afterSave(): void
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
