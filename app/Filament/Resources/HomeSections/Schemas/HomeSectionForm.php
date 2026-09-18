<?php

namespace App\Filament\Resources\HomeSections\Schemas;

use App\Enums\HomeSectionType;
use App\Enums\ProductRailSource;
use App\Models\Category;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Illuminate\Support\Str;

/**
 * One block of the homepage: what it is, what it shows and when (ADR-024).
 */
class HomeSectionForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(3)
            ->components([
                Section::make('The block')
                    ->columnSpan(2)
                    ->columns(2)
                    ->schema([
                        Select::make('type')
                            ->label('Kind of block')
                            ->options(HomeSectionType::options())
                            ->default(HomeSectionType::ProductRail->value)
                            ->required()
                            ->live()
                            ->helperText(fn ($state): string => $state ? HomeSectionType::from($state)->hint() : ''),

                        TextInput::make('key')
                            ->label('Internal name')
                            ->required()
                            ->maxLength(40)
                            ->unique(ignoreRecord: true)
                            ->default(fn (): string => 'section-'.Str::random(6))
                            ->helperText('Only used in the code. Customers never see it.'),

                        TextInput::make('title')
                            ->label('Heading')
                            ->maxLength(255)
                            ->columnSpanFull(),

                        TextInput::make('subtitle')
                            ->label('Line under the heading')
                            ->maxLength(255)
                            ->columnSpanFull(),

                        TextInput::make('link_label')
                            ->label('Link text')
                            ->maxLength(40)
                            ->placeholder('Shop all'),

                        TextInput::make('link_url')
                            ->label('Link address')
                            ->maxLength(255)
                            ->placeholder('/c/gifts'),
                    ]),

                Section::make('When it shows')
                    ->columnSpan(1)
                    ->schema([
                        Toggle::make('is_active')
                            ->label('Show on the homepage')
                            ->default(true),
                        DateTimePicker::make('starts_at')
                            ->label('Starts')
                            ->seconds(false)
                            ->helperText('Leave empty to start straight away.'),
                        DateTimePicker::make('ends_at')
                            ->label('Ends')
                            ->seconds(false)
                            ->after('starts_at'),
                    ]),

                Section::make('Which products')
                    ->columnSpan(3)
                    ->columns(3)
                    ->visible(fn (Get $get): bool => $get('type') === HomeSectionType::ProductRail->value)
                    ->schema([
                        Select::make('settings.source')
                            ->label('Where they come from')
                            ->options(ProductRailSource::options())
                            ->default(ProductRailSource::Bestsellers->value)
                            ->required()
                            ->live(),

                        Select::make('settings.category')
                            ->label('Category')
                            ->options(fn (): array => Category::orderBy('name')->pluck('name', 'slug')->all())
                            ->searchable()
                            ->visible(fn (Get $get): bool => $get('settings.source') === ProductRailSource::Category->value)
                            ->required(fn (Get $get): bool => $get('settings.source') === ProductRailSource::Category->value),

                        TextInput::make('settings.limit')
                            ->label('How many to show')
                            ->numeric()
                            ->default(8)
                            ->minValue(2)
                            ->maxValue(12),

                        Toggle::make('settings.rail')
                            ->label('Swipeable row on phones')
                            ->default(true)
                            ->helperText('Off shows a grid instead.'),
                    ]),
            ]);
    }
}
