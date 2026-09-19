<?php

namespace App\Filament\Resources\Payments\Tables;

use App\Enums\PaymentAttemptStatus;
use App\Enums\PaymentStatus;
use App\Filament\Resources\Orders\Tables\OrdersTable;
use App\Models\Payment;
use App\Support\Money;
use Filament\Actions\Action;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

/**
 * The UPI verification queue: the screenshot and UTR a customer sent, checked
 * against the shop's account and then verified or rejected with a reason the
 * customer can act on.
 */
class PaymentsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('submitted_at', 'asc')
            ->modifyQueryUsing(fn (Builder $query): Builder => $query->with('order.customer'))
            ->columns([
                TextColumn::make('order.order_number')
                    ->label('Order')
                    ->searchable()
                    ->weight('semibold')
                    ->url(fn (Payment $record): string => route('filament.admin.resources.orders.view', $record->order_id))
                    ->description(fn (Payment $record): string => $record->order->customer->name),

                TextColumn::make('utr')
                    ->label('UTR')
                    ->searchable()
                    ->copyable()
                    ->copyMessage('UTR copied')
                    ->fontFamily('mono'),

                TextColumn::make('amount_paise')
                    ->label('Amount')
                    ->alignEnd()
                    ->sortable()
                    ->formatStateUsing(fn (int $state): string => Money::format($state)),

                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->formatStateUsing(fn (PaymentAttemptStatus $state): string => $state->label())
                    ->color(fn (PaymentAttemptStatus $state): string => OrdersTable::tone($state->tone())),

                TextColumn::make('submitted_at')
                    ->label('Sent')
                    ->since()
                    ->sortable()
                    ->tooltip(fn (Payment $record): ?string => $record->submitted_at?->format('j M Y, g:i a')),

                TextColumn::make('verified_at')
                    ->label('Checked')
                    ->dateTime('j M, g:i a')
                    ->placeholder('—')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->options(fn (): array => collect(PaymentAttemptStatus::cases())
                        ->mapWithKeys(fn (PaymentAttemptStatus $status): array => [$status->value => $status->label()])
                        ->all()),
            ])
            ->recordActions([
                Action::make('proof')
                    ->label('See proof')
                    ->icon('heroicon-m-photo')
                    ->color('gray')
                    ->modalHeading(fn (Payment $record): string => 'Payment proof for '.$record->order?->order_number)
                    ->modalSubmitAction(false)
                    ->modalCancelActionLabel('Close')
                    ->modalContent(fn (Payment $record) => view('filament.payments.proof', ['payment' => $record])),

                Action::make('verify')
                    ->label('Verify')
                    ->icon('heroicon-m-check-badge')
                    ->color('success')
                    ->visible(fn (Payment $record): bool => $record->status === PaymentAttemptStatus::Submitted)
                    ->requiresConfirmation()
                    ->modalHeading('Mark this payment as received?')
                    ->modalDescription(fn (Payment $record): string => 'Check '.Money::format($record->amount_paise).' against UTR '.$record->utr.' in your UPI account first.')
                    ->action(function (Payment $record): void {
                        self::verify($record);

                        Notification::make()
                            ->title('Payment verified')
                            ->body($record->order?->order_number.' can now be confirmed.')
                            ->success()
                            ->send();
                    }),

                Action::make('reject')
                    ->label('Reject')
                    ->icon('heroicon-m-x-circle')
                    ->color('danger')
                    ->visible(fn (Payment $record): bool => $record->status === PaymentAttemptStatus::Submitted)
                    ->schema([
                        Textarea::make('reason')
                            ->label('What should the customer do?')
                            ->required()
                            ->rows(3)
                            ->default('We could not find a payment with this UTR. Please check the number in your UPI app and upload the screenshot again.')
                            ->maxLength(200),
                    ])
                    ->action(function (Payment $record, array $data): void {
                        self::reject($record, $data['reason']);

                        Notification::make()
                            ->title('Payment rejected')
                            ->body('The customer can send fresh proof.')
                            ->warning()
                            ->send();
                    }),
            ])
            ->emptyStateHeading('No payments to check')
            ->emptyStateDescription('UPI payments appear here as customers send their proof.');
    }

    /**
     * Phase 6 moves this into a VerifyPayment action; the screen calls that instead.
     */
    public static function verify(Payment $payment): void
    {
        DB::transaction(function () use ($payment): void {
            $payment->update([
                'status' => PaymentAttemptStatus::Verified,
                'verified_by' => auth()->id(),
                'verified_at' => now(),
                'rejection_reason' => null,
            ]);

            $payment->order?->update(['payment_status' => PaymentStatus::Verified]);
        });
    }

    public static function reject(Payment $payment, string $reason): void
    {
        DB::transaction(function () use ($payment, $reason): void {
            $payment->update([
                'status' => PaymentAttemptStatus::Rejected,
                'verified_by' => auth()->id(),
                'verified_at' => now(),
                'rejection_reason' => $reason,
            ]);

            $payment->order?->update(['payment_status' => PaymentStatus::Rejected]);
        });
    }

    /**
     * Orders whose money is still in question, for the navigation badge.
     */
    public static function openCount(): int
    {
        return Payment::where('status', PaymentAttemptStatus::Submitted)->count();
    }
}
