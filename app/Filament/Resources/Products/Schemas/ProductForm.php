<?php

namespace App\Filament\Resources\Products\Schemas;

use App\Models\Category;
use App\Models\Product;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Support\Str;

/**
 * A product and its selling copy. Prices and stock live on its variants.
 */
class ProductForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(3)
            ->components([
                Section::make('The product')
                    ->columnSpan(2)
                    ->columns(2)
                    ->schema([
                        TextInput::make('name')
                            ->required()
                            ->maxLength(255)
                            ->live(onBlur: true)
                            ->afterStateUpdated(fn (string $state, callable $set) => $set('slug', Str::slug($state)))
                            ->columnSpanFull(),

                        TextInput::make('slug')
                            ->label('Web address')
                            ->required()
                            ->maxLength(255)
                            ->unique(ignoreRecord: true)
                            ->prefix('/p/')
                            ->helperText('Changing this breaks old links.'),

                        TextInput::make('brand')->maxLength(255),

                        TextInput::make('short_description')
                            ->label('One-line summary')
                            ->maxLength(255)
                            ->columnSpanFull(),

                        Textarea::make('description')
                            ->label('Full description')
                            ->rows(5)
                            ->columnSpanFull(),
                    ]),

                Section::make('Where it sits')
                    ->columnSpan(1)
                    ->schema([
                        Select::make('category_id')
                            ->label('Category')
                            ->options(fn (): array => Category::orderBy('name')->pluck('name', 'id')->all())
                            ->searchable()
                            ->required(),
                        Toggle::make('is_active')->label('On sale')->default(true),
                        Toggle::make('is_featured')->label('Feature on the homepage'),
                        TextInput::make('hsn_code')->label('HSN code')->maxLength(8),
                        TextInput::make('tax_rate_bp')
                            ->label('Tax rate (basis points)')
                            ->integer()
                            ->minValue(0)
                            ->maxValue(10000)
                            ->helperText('1800 means 18%.'),
                    ]),

                Section::make('Pictures')
                    ->columnSpan(3)
                    ->schema([
                        FileUpload::make('images')
                            ->label('Product photos')
                            ->image()
                            ->multiple()
                            ->reorderable()
                            ->disk('public')
                            ->directory('products')
                            ->visibility('public')
                            ->maxFiles(6)
                            ->maxSize(3072)
                            ->helperText('Square photos look best. The first one is the one listings show.')
                            // Saved by StoreProductImages, which also writes the
                            // card-sized copy, so it is not a column on products.
                            ->dehydrated(false)
                            ->afterStateHydrated(fn (FileUpload $component, ?Product $record) => $component->state(
                                $record?->images()->orderBy('sort_order')->orderBy('id')->pluck('path')->all() ?? [],
                            )),
                    ]),
            ]);
    }
}
