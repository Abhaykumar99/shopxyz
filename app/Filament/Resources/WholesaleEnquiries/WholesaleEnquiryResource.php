<?php

namespace App\Filament\Resources\WholesaleEnquiries;

use App\Filament\Resources\WholesaleEnquiries\Pages\EditWholesaleEnquiry;
use App\Filament\Resources\WholesaleEnquiries\Pages\ListWholesaleEnquiries;
use App\Filament\Resources\WholesaleEnquiries\Schemas\WholesaleEnquiryForm;
use App\Filament\Resources\WholesaleEnquiries\Tables\WholesaleEnquiriesTable;
use App\Models\WholesaleEnquiry;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class WholesaleEnquiryResource extends Resource
{
    protected static ?string $model = WholesaleEnquiry::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedChatBubbleLeftRight;

    protected static string|UnitEnum|null $navigationGroup = 'Orders';

    protected static ?int $navigationSort = 3;

    protected static ?string $navigationLabel = 'Wholesale quotes';

    protected static ?string $modelLabel = 'quote request';

    protected static ?string $recordTitleAttribute = 'reference';

    public static function form(Schema $schema): Schema
    {
        return WholesaleEnquiryForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return WholesaleEnquiriesTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function getPages(): array
    {
        return [
            'index' => ListWholesaleEnquiries::route('/'),
            'edit' => EditWholesaleEnquiry::route('/{record}/edit'),
        ];
    }
}
