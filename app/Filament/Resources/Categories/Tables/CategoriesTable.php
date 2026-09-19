<?php

namespace App\Filament\Resources\Categories\Tables;

use App\Models\Category;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class CategoriesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->reorderable('sort_order')
            ->defaultSort('sort_order')
            ->modifyQueryUsing(fn (Builder $query): Builder => $query->with('parent')->withCount('products'))
            ->columns([
                TextColumn::make('name')
                    ->label('Category')
                    ->searchable()
                    ->weight('semibold')
                    ->description(fn (Category $record): ?string => $record->parent?->name),
                TextColumn::make('slug')->label('Address')->prefix('/c/')->color('gray'),
                TextColumn::make('products_count')->label('Products')->alignCenter(),
                ToggleColumn::make('is_active')->label('Shown')->alignCenter(),
            ])
            ->recordActions([EditAction::make(), DeleteAction::make()])
            ->toolbarActions([BulkActionGroup::make([DeleteBulkAction::make()])])
            ->emptyStateHeading('No categories yet');
    }
}
