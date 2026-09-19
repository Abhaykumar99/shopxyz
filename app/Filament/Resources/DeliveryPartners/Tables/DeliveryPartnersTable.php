<?php

namespace App\Filament\Resources\DeliveryPartners\Tables;

use App\Enums\CashSettlementStatus;
use App\Enums\DeliveryStep;
use App\Models\User;
use App\Support\IndianPhone;
use App\Support\Money;
use Filament\Actions\CreateAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class DeliveryPartnersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('name')
            ->modifyQueryUsing(fn (Builder $query): Builder => $query
                ->withCount(['deliveryAssignments as active_deliveries_count' => fn (Builder $query): Builder => $query
                    ->whereIn('step', [DeliveryStep::Assigned->value, DeliveryStep::Accepted->value, DeliveryStep::PickedUp->value, DeliveryStep::OutForDelivery->value])])
                ->withSum(['deliveryAssignments as cash_with_partner_paise' => fn (Builder $query): Builder => $query
                    ->whereNull('cod_settlement_id')], 'cash_collected_paise')
                ->withSum(['codSettlements as cash_awaiting_paise' => fn (Builder $query): Builder => $query
                    ->where('status', CashSettlementStatus::AwaitingVerification)], 'amount_paise'))
            ->columns([
                TextColumn::make('name')
                    ->label('Partner')
                    ->searchable()
                    ->weight('semibold')
                    ->description(fn (User $record): string => IndianPhone::format((string) $record->phone)),

                TextColumn::make('active_deliveries_count')
                    ->label('On the road')
                    ->alignCenter()
                    ->badge()
                    ->color(fn (int $state): string => $state > 0 ? 'primary' : 'gray'),

                TextColumn::make('cash_with_partner_paise')
                    ->label('Cash with them')
                    ->alignEnd()
                    ->sortable()
                    ->badge()
                    ->color(fn (?int $state): string => (int) $state > 0 ? 'warning' : 'gray')
                    ->formatStateUsing(fn (?int $state): string => Money::format((int) $state))
                    ->description(function (User $record): ?string {
                        $awaiting = (int) $record->getAttribute('cash_awaiting_paise');

                        return $awaiting > 0 ? Money::format($awaiting).' waiting to be counted' : null;
                    }),

                ToggleColumn::make('is_active')->label('Can sign in')->alignCenter(),
            ])
            ->headerActions([CreateAction::make()->label('Add partner')])
            ->recordActions([EditAction::make()])
            ->emptyStateHeading('No delivery partners yet')
            ->emptyStateDescription('Add one and they can sign in to the delivery panel.');
    }
}
