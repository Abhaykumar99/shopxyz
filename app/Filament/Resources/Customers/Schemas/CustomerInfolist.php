<?php

namespace App\Filament\Resources\Customers\Schemas;

use App\Models\Address;
use App\Models\Order;
use App\Models\User;
use App\Support\IndianPhone;
use App\Support\Money;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class CustomerInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Grid::make(['default' => 1, 'lg' => 3])->schema([
                Section::make('Customer')
                    ->columnSpan(['lg' => 1])
                    ->schema([
                        TextEntry::make('name'),
                        TextEntry::make('email')->copyable(),
                        TextEntry::make('phone')
                            ->formatStateUsing(fn (?string $state): string => $state ? IndianPhone::format($state) : 'Not given yet'),
                        TextEntry::make('created_at')->label('Joined')->date('j M Y'),
                    ]),

                Section::make('Addresses')
                    ->columnSpan(['lg' => 1])
                    ->schema([
                        RepeatableEntry::make('addresses')
                            ->hiddenLabel()
                            ->placeholder('No addresses saved')
                            ->schema([
                                TextEntry::make('label')
                                    ->hiddenLabel()
                                    ->weight('medium')
                                    ->belowContent(fn (Address $record): string => collect([
                                        $record->line1, $record->line2, $record->city.' '.$record->pincode,
                                    ])->filter()->join(', ')),
                            ]),
                    ]),

                Section::make('Orders')
                    ->columnSpan(['lg' => 1])
                    ->schema([
                        TextEntry::make('orders_count')
                            ->label('Orders placed')
                            ->state(fn (User $record): int => $record->orders()->count()),
                        TextEntry::make('spent')
                            ->label('Spent in total')
                            ->state(fn (User $record): string => Money::format((int) $record->orders()->sum('total_paise'))),
                        RepeatableEntry::make('orders')
                            ->label('Recent')
                            ->schema([
                                TextEntry::make('order_number')
                                    ->hiddenLabel()
                                    ->url(fn (Order $record): string => route('filament.admin.resources.orders.view', $record))
                                    ->belowContent(fn (Order $record): string => $record->placed_at->format('j M Y').' · '.Money::format($record->total_paise)),
                            ]),
                    ]),
            ]),
        ]);
    }
}
