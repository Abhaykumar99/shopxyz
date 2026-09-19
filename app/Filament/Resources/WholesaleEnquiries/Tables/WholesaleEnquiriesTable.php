<?php

namespace App\Filament\Resources\WholesaleEnquiries\Tables;

use App\Enums\WholesaleEnquiryStatus;
use App\Filament\Resources\Orders\Tables\OrdersTable;
use App\Models\WholesaleEnquiry;
use App\Support\IndianPhone;
use App\Support\Money;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

/**
 * Quote requests from businesses (ADR-019): who asked, what they need and
 * where the conversation has got to.
 */
class WholesaleEnquiriesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('reference')
                    ->label('Request')
                    ->searchable()
                    ->weight('semibold')
                    ->description(fn (WholesaleEnquiry $record): string => $record->created_at->diffForHumans()),

                TextColumn::make('business_name')
                    ->label('Business')
                    ->searchable()
                    ->wrap()
                    ->description(fn (WholesaleEnquiry $record): string => $record->contact_name.' · '.IndianPhone::format($record->phone)),

                TextColumn::make('message')
                    ->label('What they need')
                    ->wrap()
                    ->limit(90)
                    ->tooltip(fn (WholesaleEnquiry $record): ?string => $record->message),

                TextColumn::make('estimate_paise')
                    ->label('Estimate')
                    ->alignEnd()
                    ->formatStateUsing(fn (?int $state): string => $state ? Money::format($state) : '—'),

                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->formatStateUsing(fn (WholesaleEnquiryStatus $state): string => $state->label())
                    ->color(fn (WholesaleEnquiryStatus $state): string => OrdersTable::tone($state->tone())),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->options(WholesaleEnquiryStatus::options())
                    ->multiple()
                    ->default([WholesaleEnquiryStatus::New->value, WholesaleEnquiryStatus::Quoted->value]),
            ])
            ->recordActions([
                Action::make('call')
                    ->label('Call')
                    ->icon('heroicon-m-phone')
                    ->color('gray')
                    ->url(fn (WholesaleEnquiry $record): string => 'tel:+91'.$record->phone),

                Action::make('markQuoted')
                    ->label('Quote sent')
                    ->icon('heroicon-m-paper-airplane')
                    ->visible(fn (WholesaleEnquiry $record): bool => $record->status === WholesaleEnquiryStatus::New)
                    ->action(function (WholesaleEnquiry $record): void {
                        $record->update(['status' => WholesaleEnquiryStatus::Quoted, 'handled_by' => auth()->id()]);

                        Notification::make()->title('Marked as quoted')->success()->send();
                    }),

                EditAction::make(),
            ])
            ->emptyStateHeading('No quote requests')
            ->emptyStateDescription('Requests from the wholesale page land here.');
    }
}
