<?php

namespace App\Filament\Resources\DeliveryAssignments\Tables;

use App\Enums\DeliveryStep;
use App\Enums\UserRole;
use App\Filament\Resources\Orders\Tables\OrdersTable;
use App\Models\DeliveryAssignment;
use App\Models\User;
use App\Support\Money;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

/**
 * Who is carrying what, and how far along they are (ADR-020 to ADR-022).
 */
class DeliveryAssignmentsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('assigned_at', 'desc')
            ->modifyQueryUsing(fn (Builder $query): Builder => $query->with(['order.customer', 'deliveryPartner']))
            ->columns([
                TextColumn::make('order.order_number')
                    ->label('Order')
                    ->searchable()
                    ->weight('semibold')
                    ->url(fn (DeliveryAssignment $record): string => route('filament.admin.resources.orders.view', $record->order_id))
                    ->description(fn (DeliveryAssignment $record): string => $record->order?->ship_city.' '.$record->order?->ship_pincode),

                TextColumn::make('deliveryPartner.name')
                    ->label('Partner')
                    ->searchable(),

                TextColumn::make('step')
                    ->label('Step')
                    ->badge()
                    ->formatStateUsing(fn (DeliveryStep $state): string => $state->label())
                    ->color(fn (DeliveryStep $state): string => OrdersTable::tone($state->tone())),

                TextColumn::make('cash_collected_paise')
                    ->label('Cash')
                    ->alignEnd()
                    ->formatStateUsing(fn (int $state): string => $state > 0 ? Money::format($state) : '—')
                    ->description(fn (DeliveryAssignment $record): ?string => $record->cash_collected_paise > 0
                        ? ($record->cod_settlement_id ? 'Handed in' : 'With the partner')
                        : null),

                TextColumn::make('assigned_at')
                    ->label('Assigned')
                    ->since()
                    ->sortable()
                    ->tooltip(fn (DeliveryAssignment $record): string => $record->assigned_at->format('j M Y, g:i a')),

                TextColumn::make('failure_reason')
                    ->label('Problem')
                    ->placeholder('—')
                    ->formatStateUsing(fn ($state): string => $state?->label() ?? '')
                    ->color('danger')
                    ->toggleable(),
            ])
            ->filters([
                SelectFilter::make('step')
                    ->options(fn (): array => collect(DeliveryStep::cases())
                        ->mapWithKeys(fn (DeliveryStep $step): array => [$step->value => $step->label()])
                        ->all())
                    ->multiple(),
                SelectFilter::make('user_id')
                    ->label('Delivery partner')
                    ->relationship('deliveryPartner', 'name'),
                Filter::make('cash_with_partner')
                    ->label('Cash still with the partner')
                    ->query(fn (Builder $query): Builder => $query
                        ->where('cash_collected_paise', '>', 0)
                        ->whereNull('cod_settlement_id'))
                    ->toggle(),

                Filter::make('on_the_road')
                    ->label('On the road now')
                    ->query(fn (Builder $query): Builder => $query->whereIn('step', [
                        DeliveryStep::Accepted->value, DeliveryStep::PickedUp->value, DeliveryStep::OutForDelivery->value,
                    ]))
                    ->toggle(),
            ])
            ->recordActions([
                Action::make('reassign')
                    ->label('Reassign')
                    ->icon('heroicon-m-arrow-path')
                    ->visible(fn (DeliveryAssignment $record): bool => ! $record->step->isFinished())
                    ->schema([
                        Select::make('user_id')
                            ->label('Give it to')
                            ->options(fn (DeliveryAssignment $record): array => User::where('role', UserRole::Delivery)
                                ->where('is_active', true)
                                ->whereKeyNot($record->user_id)
                                ->orderBy('name')
                                ->pluck('name', 'id')
                                ->all())
                            ->required(),
                    ])
                    ->action(function (DeliveryAssignment $record, array $data): void {
                        self::reassign($record, (int) $data['user_id']);

                        Notification::make()
                            ->title('Reassigned')
                            ->body('The new partner sees it in their round with a fresh OTP.')
                            ->success()
                            ->send();
                    }),
            ])
            ->emptyStateHeading('Nothing out for delivery')
            ->emptyStateDescription('Assign a packed order to a delivery partner and it appears here.');
    }

    /**
     * Hands a delivery to someone else, with a new OTP for the customer.
     */
    public static function reassign(DeliveryAssignment $assignment, int $userId): void
    {
        DB::transaction(function () use ($assignment, $userId): void {
            $assignment->update(['is_active' => false]);

            DeliveryAssignment::create([
                'order_id' => $assignment->order_id,
                'user_id' => $userId,
                'assigned_by' => auth()->id(),
                'step' => DeliveryStep::Assigned,
                'is_active' => true,
                'assigned_at' => now(),
                'otp' => (string) random_int(100000, 999999),
            ]);
        });
    }
}
