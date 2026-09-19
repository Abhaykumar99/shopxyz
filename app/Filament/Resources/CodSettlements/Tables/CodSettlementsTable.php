<?php

namespace App\Filament\Resources\CodSettlements\Tables;

use App\Enums\CashSettlementStatus;
use App\Filament\Resources\CodSettlements\Pages\ListCodSettlements;
use App\Filament\Resources\Orders\Tables\OrdersTable;
use App\Models\CodSettlement;
use App\Support\Money;
use Filament\Actions\Action;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

/**
 * Cash the delivery partners have handed in: count it, then settle it (ADR-022).
 * A short count is recorded rather than hidden, so it can be sorted out.
 */
class CodSettlementsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('handed_over_at', 'desc')
            ->modifyQueryUsing(fn (Builder $query): Builder => $query->with('deliveryPartner')->withCount('assignments'))
            ->columns([
                TextColumn::make('reference')
                    ->label('Handover')
                    ->searchable()
                    ->weight('semibold')
                    ->description(fn (CodSettlement $record): string => $record->assignments_count.' '.str('order')->plural($record->assignments_count)),

                TextColumn::make('deliveryPartner.name')
                    ->label('From')
                    ->searchable(),

                TextColumn::make('amount_paise')
                    ->label('Handed in')
                    ->alignEnd()
                    ->sortable()
                    ->formatStateUsing(fn (int $state): string => Money::format($state)),

                TextColumn::make('counted_paise')
                    ->label('Counted')
                    ->alignEnd()
                    ->placeholder('Not counted')
                    ->formatStateUsing(fn (?int $state): string => $state === null ? '' : Money::format($state))
                    ->description(fn (CodSettlement $record): ?string => $record->differencePaise() === 0
                        ? null
                        : ($record->differencePaise() > 0 ? 'Over by ' : 'Short by ').Money::format(abs($record->differencePaise())))
                    ->color(fn (CodSettlement $record): string => $record->differencePaise() === 0 ? 'gray' : 'danger'),

                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->formatStateUsing(fn (CashSettlementStatus $state): string => $state->label())
                    ->color(fn (CashSettlementStatus $state): string => OrdersTable::tone($state->tone())),

                TextColumn::make('handed_over_at')
                    ->label('Handed over')
                    ->dateTime('j M, g:i a')
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->options(fn (): array => collect(CashSettlementStatus::cases())
                        ->mapWithKeys(fn (CashSettlementStatus $status): array => [$status->value => $status->label()])
                        ->all()),
                SelectFilter::make('user_id')
                    ->label('Delivery partner')
                    ->relationship('deliveryPartner', 'name'),
            ])
            ->recordActions([
                Action::make('settle')
                    ->label('Count and settle')
                    ->icon('heroicon-m-banknotes')
                    ->color('success')
                    ->visible(fn (CodSettlement $record): bool => $record->status === CashSettlementStatus::AwaitingVerification)
                    ->schema([
                        TextInput::make('counted')
                            ->label('How much did you count?')
                            ->numeric()
                            ->required()
                            ->prefix('₹')
                            ->default(fn (CodSettlement $record): int => (int) round($record->amount_paise / 100))
                            ->helperText(fn (CodSettlement $record): string => 'The partner handed in '.Money::format($record->amount_paise).'.'),
                        Textarea::make('note')
                            ->label('Note')
                            ->rows(2)
                            ->maxLength(200)
                            ->helperText('Only needed if the amount does not match.'),
                    ])
                    ->action(function (CodSettlement $record, array $data): void {
                        $counted = (int) round((float) $data['counted'] * 100);
                        self::settle($record, $counted, $data['note'] ?? null);

                        Notification::make()
                            ->title($counted === $record->amount_paise ? 'Settled' : 'Recorded as short')
                            ->body($record->reference.' · '.Money::format($counted))
                            ->color($counted === $record->amount_paise ? 'success' : 'warning')
                            ->send();
                    }),
            ])
            ->emptyStateHeading(fn (ListCodSettlements $livewire): string => match ($livewire->activeTab ?? 'to_count') {
                'to_count' => 'Nothing left to count',
                'short' => 'Nothing came up short',
                'settled' => 'Nothing settled yet',
                default => 'No cash handed in yet',
            })
            ->emptyStateDescription(fn (ListCodSettlements $livewire): string => match ($livewire->activeTab ?? 'to_count') {
                'to_count' => 'Every handover has been counted and closed.',
                'short' => 'Every handover matched the cash the partner collected.',
                default => 'Handovers appear here when a delivery partner gives the cash to the shop.',
            });
    }

    /**
     * Phase 9 moves this into a SettleCod action; the screen calls that instead.
     */
    public static function settle(CodSettlement $settlement, int $countedPaise, ?string $note): void
    {
        DB::transaction(function () use ($settlement, $countedPaise, $note): void {
            $settlement->update([
                'status' => $countedPaise === $settlement->amount_paise
                    ? CashSettlementStatus::Settled
                    : CashSettlementStatus::Short,
                'counted_paise' => $countedPaise,
                'verified_by' => auth()->id(),
                'verified_at' => now(),
                'note' => $note ?: null,
            ]);
        });
    }

    public static function openCount(): int
    {
        return CodSettlement::where('status', CashSettlementStatus::AwaitingVerification)->count();
    }
}
