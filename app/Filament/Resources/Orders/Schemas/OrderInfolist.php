<?php

namespace App\Filament\Resources\Orders\Schemas;

use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Filament\Resources\Orders\Tables\OrdersTable;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\OrderPackage;
use App\Models\OrderStatusHistory;
use App\Models\Payment;
use App\Support\Money;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

/**
 * Everything the shop needs about one order on a single screen: what was
 * ordered, where it goes, how it is being paid for, which boxes it was packed
 * into and what has happened so far.
 */
class OrderInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Grid::make(['default' => 1, 'lg' => 3])->schema([
                Section::make('Items')
                    ->columnSpan(['lg' => 2])
                    ->schema([
                        RepeatableEntry::make('items')
                            ->hiddenLabel()
                            ->schema([
                                Grid::make(['default' => 4])->schema([
                                    TextEntry::make('product_name')
                                        ->hiddenLabel()
                                        ->columnSpan(2)
                                        ->weight('medium')
                                        ->belowContent(fn (OrderItem $record): string => $record->variant_name.' · '.$record->sku),
                                    TextEntry::make('quantity')
                                        ->hiddenLabel()
                                        ->formatStateUsing(fn (OrderItem $record): string => $record->quantity.' × '.Money::format($record->unit_price_paise))
                                        ->badge()
                                        ->color(fn (OrderItem $record): string => $record->is_wholesale ? 'primary' : 'gray'),
                                    TextEntry::make('line_total_paise')
                                        ->hiddenLabel()
                                        ->alignEnd()
                                        ->weight('semibold')
                                        ->formatStateUsing(fn (int $state): string => Money::format($state)),
                                ]),
                            ]),

                        Grid::make(['default' => 2])->schema([
                            TextEntry::make('subtotal_paise')
                                ->label('Items')
                                ->formatStateUsing(fn (int $state): string => Money::format($state)),
                            TextEntry::make('delivery_charge_paise')
                                ->label('Delivery')
                                ->formatStateUsing(fn (int $state): string => $state > 0 ? Money::format($state) : 'Free'),
                            TextEntry::make('discount_paise')
                                ->label('Discount')
                                ->visible(fn (Order $record): bool => $record->discount_paise > 0)
                                ->formatStateUsing(fn (int $state): string => '−'.Money::format($state))
                                ->color('success'),
                            TextEntry::make('total_paise')
                                ->label('Total')
                                ->weight('bold')
                                ->formatStateUsing(fn (int $state): string => Money::format($state)),
                        ]),
                    ]),

                Section::make('Customer')
                    ->columnSpan(['lg' => 1])
                    ->schema([
                        TextEntry::make('customer.name')->label('Name'),
                        TextEntry::make('ship_phone')
                            ->label('Phone')
                            ->url(fn (Order $record): string => 'tel:+91'.$record->ship_phone),
                        TextEntry::make('ship_line1')
                            ->label('Deliver to')
                            ->formatStateUsing(fn (Order $record): string => collect([
                                $record->ship_line1, $record->ship_line2, $record->ship_landmark,
                                $record->ship_city.' '.$record->ship_pincode,
                            ])->filter()->join(', ')),
                        TextEntry::make('customer_note')
                            ->label('Note from the customer')
                            ->placeholder('None')
                            ->color('warning'),
                    ]),
            ]),

            Grid::make(['default' => 1, 'lg' => 3])->schema([
                Section::make('Payment')
                    ->columnSpan(['lg' => 1])
                    ->schema([
                        TextEntry::make('payment_method')
                            ->label('Method')
                            ->formatStateUsing(fn ($state): string => $state->label()),
                        TextEntry::make('payment_status')
                            ->label('Status')
                            ->badge()
                            ->formatStateUsing(fn (PaymentStatus $state): string => $state->label())
                            ->color(fn (PaymentStatus $state): string => OrdersTable::tone($state->tone())),
                        RepeatableEntry::make('payments')
                            ->label('Attempts')
                            ->visible(fn (Order $record): bool => $record->payments()->exists())
                            ->schema([
                                TextEntry::make('utr')
                                    ->label('UTR')
                                    ->copyable()
                                    ->belowContent(fn (Payment $record): string => $record->status->label().
                                        ($record->submitted_at ? ' · '.$record->submitted_at->format('j M, g:i a') : '')),
                            ]),
                    ]),

                Section::make('Boxes')
                    ->columnSpan(['lg' => 1])
                    ->description('Each box has its own pickup code on the label (ADR-021).')
                    ->schema([
                        RepeatableEntry::make('packages')
                            ->hiddenLabel()
                            ->placeholder('Not packed yet')
                            ->schema([
                                TextEntry::make('package_id')
                                    ->hiddenLabel()
                                    ->weight('medium')
                                    ->belowContent(fn (OrderPackage $record): string => $record->picked_up_at
                                        ? 'Picked up '.$record->picked_up_at->format('j M, g:i a')
                                        : 'With the shop'),
                            ]),
                    ]),

                Section::make('History')
                    ->columnSpan(['lg' => 1])
                    ->schema([
                        RepeatableEntry::make('statusHistories')
                            ->hiddenLabel()
                            ->schema([
                                TextEntry::make('to_status')
                                    ->hiddenLabel()
                                    ->formatStateUsing(fn (OrderStatus $state): string => $state->label())
                                    ->belowContent(fn (OrderStatusHistory $record): string => $record->created_at->format('j M, g:i a').
                                        ($record->note ? ' · '.$record->note : '')),
                            ]),
                    ]),
            ]),
        ]);
    }
}
