<?php

namespace App\Filament\Resources\Banners\Schemas;

use App\Enums\BannerPlacement;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

/**
 * Everything a homepage banner says and when it says it (ADR-024).
 */
class BannerForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(3)
            ->components([
                Section::make('What it says')
                    ->columnSpan(2)
                    ->schema([
                        TextInput::make('eyebrow')
                            ->label('Small label above the headline')
                            ->maxLength(40)
                            ->placeholder('Festive'),
                        TextInput::make('title')
                            ->label('Headline')
                            ->required()
                            ->maxLength(255)
                            ->columnSpanFull(),
                        TextInput::make('subtitle')
                            ->label('Supporting line')
                            ->maxLength(255)
                            ->columnSpanFull(),
                        Textarea::make('body')
                            ->label('Longer text')
                            ->rows(2)
                            ->maxLength(255)
                            ->helperText('Used on promotion cards. Leave empty for hero slides.')
                            ->columnSpanFull(),
                    ])
                    ->columns(2),

                Section::make('Where and when')
                    ->columnSpan(1)
                    ->schema([
                        Select::make('placement')
                            ->options(BannerPlacement::options())
                            ->default(BannerPlacement::DesktopHero->value)
                            ->required()
                            ->live()
                            ->helperText(fn ($state): string => $state
                                ? BannerPlacement::from($state)->hint()
                                : ''),
                        Select::make('theme')
                            ->label('Colour')
                            ->options([
                                'brand' => 'Rose (brand)',
                                'ink' => 'Dark',
                                'accent' => 'Soft rose gold',
                                'mist' => 'Pale',
                            ])
                            ->default('brand')
                            ->required(),
                        Toggle::make('is_active')
                            ->label('Show on the site')
                            ->default(true),
                        DateTimePicker::make('starts_at')
                            ->label('Starts')
                            ->seconds(false)
                            ->helperText('Leave empty to start straight away.'),
                        DateTimePicker::make('ends_at')
                            ->label('Ends')
                            ->seconds(false)
                            ->after('starts_at')
                            ->helperText('Leave empty to run until you switch it off.'),
                    ]),

                Section::make('Buttons')
                    ->columnSpan(2)
                    ->columns(2)
                    ->schema([
                        TextInput::make('cta_label')->label('Button text')->maxLength(40)->placeholder('Start shopping'),
                        TextInput::make('cta_url')->label('Button link')->maxLength(255)->placeholder('/categories'),
                        TextInput::make('secondary_cta_label')->label('Second button text')->maxLength(40),
                        TextInput::make('secondary_cta_url')->label('Second button link')->maxLength(255),
                    ]),

                Section::make('Picture')
                    ->columnSpan(1)
                    ->schema([
                        FileUpload::make('image_path')
                            ->label('Image')
                            ->image()
                            ->imageEditor()
                            ->directory('banners')
                            ->visibility('public')
                            ->maxSize(3072)
                            ->helperText('Landscape works best, about 1200 × 800. Optional.'),
                        TextInput::make('image_alt')
                            ->label('Describe the picture')
                            ->maxLength(255)
                            ->helperText('For people using a screen reader.'),
                    ]),
            ]);
    }
}
