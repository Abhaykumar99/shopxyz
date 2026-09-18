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
                    ->whereIn('step', [DeliveryStep::Assigned->value, DeliveryStep::Accepted->value, DeliveryStep::PickedUp->value, DeliveryStep::OutForDelivery->value])]))
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

                TextColumn::make('cash_with_partner')
                    ->label('Cash with them')
                    ->alignEnd()
                    ->state(fn (User $record): string => Money::format((int) $record->deliveryAssignments()
                        ->whereNull('cod_settlement_id')
                        ->sum('cash_collected_paise')))
                    ->description(fn (User $record): ?string => ($open = $record->codSettlements()
                        ->where('status', CashSettlementStatus::AwaitingVerification)
                        ->sum('amount_paise')) > 0
                        ? Money::format((int) $open).' waiting to be counted'
                        : null),

                ToggleColumn::make('is_active')->label('Can sign in')->alignCenter(),
            ])
            ->headerActions([CreateAction::make()->label('Add partner')])
            ->recordActions([EditAction::make()])
            ->emptyStateHeading('No delivery partners yet')
            ->emptyStateDescription('Add one and they can sign in to the delivery panel.');
    }
}
