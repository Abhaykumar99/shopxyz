<?php

namespace App\Filament\Resources\WholesaleEnquiries\Schemas;

use App\Enums\WholesaleEnquiryStatus;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

/**
 * The shop answers a quote request by phone or WhatsApp; this records where it got to.
 */
class WholesaleEnquiryForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(3)
            ->components([
                Section::make('What they asked for')
                    ->columnSpan(2)
                    ->columns(2)
                    ->schema([
                        TextInput::make('business_name')->label('Business')->required()->maxLength(120),
                        TextInput::make('contact_name')->label('Contact')->required()->maxLength(80),
                        TextInput::make('phone')->label('Phone')->required()->maxLength(15),
                        TextInput::make('email')->label('Email')->email()->maxLength(120),
                        TextInput::make('gstin')->label('GSTIN')->maxLength(15),
                        TextInput::make('city')->label('City')->required()->maxLength(60),
                        Textarea::make('message')->label('Their message')->rows(4)->columnSpanFull(),
                    ]),

                Section::make('Where it got to')
                    ->columnSpan(1)
                    ->schema([
                        Select::make('status')
                            ->options(WholesaleEnquiryStatus::options())
                            ->required(),
                        TextInput::make('estimate_paise')
                            ->label('Estimate in paise')
                            ->numeric()
                            ->helperText('Money is stored in paise: ₹1,000 is 100000.'),
                        Select::make('handled_by')
                            ->label('Handled by')
                            ->relationship('handledBy', 'name')
                            ->searchable(),
                    ]),
            ]);
    }
}
