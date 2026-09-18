<?php

namespace App\Filament\Resources\Banners\Tables;

use App\Enums\BannerPlacement;
use App\Models\Banner;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ReplicateAction;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Grouping\Group;
use Filament\Tables\Table;

/**
 * Banners in the order they appear on the homepage. Drag a row to reorder it;
 * the switch takes it on and off the site straight away (ADR-024).
 */
class BannersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->reorderable('sort_order')
            ->defaultGroup(
                Group::make('placement')
                    ->label('Where it shows')
                    ->getTitleFromRecordUsing(fn (Banner $record): string => $record->placement->label()),
            )
            ->defaultSort('sort_order')
            ->columns([
                ImageColumn::make('image_path')
                    ->label('Picture')
                    ->disk('public')
                    ->height(44)
                    ->width(72)
                    ->defaultImageUrl(null)
                    ->placeholder('—'),

                TextColumn::make('title')
                    ->label('Banner')
                    ->searchable()
                    ->wrap()
                    ->weight('semibold')
                    ->description(fn (Banner $record): ?string => $record->subtitle),

                TextColumn::make('placement')
                    ->label('Where')
                    ->badge()
                    ->formatStateUsing(fn (BannerPlacement $state): string => $state->label()),

                TextColumn::make('liveState')
                    ->label('Status')
                    ->badge()
                    ->state(fn (Banner $record): string => $record->liveState())
                    ->color(fn (string $state): string => match ($state) {
                        'Live' => 'success',
                        'Scheduled' => 'info',
                        'Finished' => 'gray',
                        default => 'warning',
                    })
                    ->description(fn (Banner $record): ?string => match (true) {
                        $record->starts_at !== null && $record->starts_at->isFuture() => 'From '.$record->starts_at->format('j M, g:i a'),
                        $record->ends_at !== null => 'Until '.$record->ends_at->format('j M, g:i a'),
                        default => null,
                    }),

                ToggleColumn::make('is_active')
                    ->label('On')
                    ->alignCenter(),
            ])
            ->filters([
                SelectFilter::make('placement')
                    ->label('Where it shows')
                    ->options(BannerPlacement::options()),
            ])
            ->recordActions([
                EditAction::make(),
                ReplicateAction::make()
                    ->label('Duplicate')
                    ->excludeAttributes(['created_at', 'updated_at'])
                    ->beforeReplicaSaved(function (Banner $replica): void {
                        $replica->title = $replica->title.' (copy)';
                        $replica->is_active = false;
                        $replica->sort_order = $replica->sort_order + 1;
                    }),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ])
            ->emptyStateHeading('No banners yet')
            ->emptyStateDescription('Add a hero slide or a promotion card and it appears on the homepage.');
    }
}
