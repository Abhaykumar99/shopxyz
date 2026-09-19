<?php

namespace App\Filament\Widgets;

use App\Enums\CashSettlementStatus;
use App\Enums\DeliveryStep;
use App\Enums\UserRole;
use App\Models\User;
use App\Support\Money;
use Filament\Actions\Action;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;
use Illuminate\Database\Eloquent\Builder;

/**
 * Where the COD cash is, partner by partner (ADR-022): what they collected,
 * what is still in their pocket, what the shop is counting and what is settled.
 */
class CashPosition extends TableWidget
{
    protected static ?string $heading = 'Cash by delivery partner';

    protected int|string|array $columnSpan = 'full';

    protected static ?int $sort = 5;

    public function table(Table $table): Table
    {
        return $table
            ->query(
                User::query()
                    ->where('role', UserRole::Delivery)
                    ->withCount([
                        'deliveryAssignments as delivered_cod_count' => fn (Builder $query): Builder => $query
                            ->where('step', DeliveryStep::Delivered)
                            ->where('cash_collected_paise', '>', 0),
                    ])
                    ->withSum('deliveryAssignments as collected_paise', 'cash_collected_paise')
                    ->withSum([
                        'deliveryAssignments as with_partner_paise' => fn (Builder $query): Builder => $query->whereNull('cod_settlement_id'),
                    ], 'cash_collected_paise')
                    ->withSum([
                        'codSettlements as awaiting_paise' => fn (Builder $query): Builder => $query
                            ->where('status', CashSettlementStatus::AwaitingVerification),
                    ], 'amount_paise')
                    ->withSum([
                        'codSettlements as settled_paise' => fn (Builder $query): Builder => $query
                            ->where('status', CashSettlementStatus::Settled),
                    ], 'amount_paise')
                    ->orderByDesc('with_partner_paise'),
            )
            ->columns([
                TextColumn::make('name')
                    ->label('Partner')
                    ->weight('semibold')
                    ->description(function (User $record): string {
                        $deliveries = (int) $record->getAttribute('delivered_cod_count');

                        return $deliveries.' cash '.str('delivery')->plural($deliveries);
                    }),

                TextColumn::make('collected_paise')
                    ->default(0)
                    ->label('Collected')
                    ->visibleFrom('md')
                    ->alignEnd()
                    ->formatStateUsing(fn (?int $state): string => Money::format((int) $state)),

                TextColumn::make('with_partner_paise')
                    ->default(0)
                    ->label('Still with them')
                    ->alignEnd()
                    ->badge()
                    ->color(fn (?int $state): string => (int) $state > 0 ? 'warning' : 'gray')
                    ->formatStateUsing(fn (?int $state): string => Money::format((int) $state)),

                TextColumn::make('awaiting_paise')
                    ->default(0)
                    ->label('Waiting to be counted')
                    ->alignEnd()
                    ->badge()
                    ->color(fn (?int $state): string => (int) $state > 0 ? 'info' : 'gray')
                    ->formatStateUsing(fn (?int $state): string => Money::format((int) $state)),

                TextColumn::make('settled_paise')
                    ->default(0)
                    ->label('Settled')
                    ->visibleFrom('md')
                    ->alignEnd()
                    ->color('success')
                    ->formatStateUsing(fn (?int $state): string => Money::format((int) $state)),
            ])
            ->recordActions([
                Action::make('deliveries')
                    ->label('Deliveries')
                    ->icon('heroicon-m-truck')
                    ->url(fn (User $record): string => route('filament.admin.resources.delivery-assignments.index', [
                        'tableFilters' => ['user_id' => ['value' => $record->getKey()]],
                    ])),
            ])
            ->paginated(false)
            ->emptyStateHeading('No delivery partners yet');
    }
}
