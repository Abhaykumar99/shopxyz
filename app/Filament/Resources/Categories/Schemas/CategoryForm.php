<?php

namespace App\Filament\Resources\Categories\Schemas;

use App\Models\Category;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;
use Illuminate\Support\Str;

class CategoryForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->columns(2)->components([
            TextInput::make('name')
                ->required()
                ->maxLength(255)
                ->live(onBlur: true)
                ->afterStateUpdated(fn (string $state, callable $set) => $set('slug', Str::slug($state))),
            TextInput::make('slug')->required()->maxLength(255)->unique(ignoreRecord: true)->prefix('/c/'),
            Select::make('parent_id')
                ->label('Sits under')
                ->options(fn (): array => Category::whereNull('parent_id')->orderBy('name')->pluck('name', 'id')->all())
                ->placeholder('Top level')
                ->searchable(),
            TextInput::make('sort_order')->label('Order')->integer()->minValue(0)->maxValue(9999)->default(0),
            TextInput::make('description')->maxLength(255)->columnSpanFull(),
            FileUpload::make('image_path')->label('Picture')->image()->directory('categories')->visibility('public'),
            Toggle::make('is_active')->label('Show in the shop')->default(true),
        ]);
    }
}
