<?php

namespace App\Filament\Resources\HomeSections\Tables;

use App\Enums\HomeSectionType;
use App\Models\HomeSection;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Table;

/**
 * The homepage, top to bottom. Drag a row to move a block up or down (ADR-024).
 */
class HomeSectionsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->reorderable('sort_order')
            ->defaultSort('sort_order')
            ->columns([
                TextColumn::make('title')
                    ->label('Block')
                    ->searchable()
                    ->weight('semibold')
                    ->wrap()
                    ->state(fn (HomeSection $record): string => $record->title ?? $record->type->label())
                    ->description(fn (HomeSection $record): ?string => $record->subtitle),

                TextColumn::make('type')
                    ->label('Kind')
                    ->badge()
                    ->formatStateUsing(fn (HomeSectionType $state): string => $state->label()),

                TextColumn::make('shows')
                    ->label('Shows')
                    ->state(fn (HomeSection $record): string => match (true) {
                        $record->type !== HomeSectionType::ProductRail => '—',
                        $record->source() === null => 'Not set',
                        $record->source()->needsCategory() => $record->source()->label().': '.($record->categorySlug() ?? 'not set'),
                        default => $record->source()->label(),
                    })
                    ->description(fn (HomeSection $record): ?string => $record->type === HomeSectionType::ProductRail
                        ? 'Up to '.$record->limit()
                        : null),

                TextColumn::make('liveState')
                    ->label('Status')
                    ->badge()
                    ->state(fn (HomeSection $record): string => $record->liveState())
                    ->color(fn (string $state): string => match ($state) {
                        'Live' => 'success',
                        'Scheduled' => 'info',
                        'Finished' => 'gray',
                        default => 'warning',
                    }),

                ToggleColumn::make('is_active')
                    ->label('On')
                    ->alignCenter(),
            ])
            ->headerActions([
                Action::make('preview')
                    ->label('Preview homepage')
                    ->icon('heroicon-m-arrow-top-right-on-square')
                    ->color('gray')
                    ->url(url('/'), shouldOpenInNewTab: true),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ])
            ->emptyStateHeading('The homepage is empty')
            ->emptyStateDescription('Add a block and it appears on the homepage straight away.');
    }
}
