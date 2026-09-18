<?php

namespace App\Filament\Resources\Orders\Pages;

use App\Enums\DeliveryStep;
use App\Enums\OrderStatus;
use App\Enums\UserRole;
use App\Filament\Resources\Orders\OrderResource;
use App\Filament\Resources\Orders\Tables\OrdersTable;
use App\Models\DeliveryAssignment;
use App\Models\Order;
use App\Models\User;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;
use Illuminate\Support\Facades\DB;

/**
 * One order, with the actions that move it along: confirm, pack into boxes,
 * hand to a delivery partner, print, or cancel.
 */
class ViewOrder extends ViewRecord
{
    protected static string $resource = OrderResource::class;

    public function getTitle(): string
    {
        /** @var Order $order */
        $order = $this->record;

        return $order->order_number;
    }

    public function getSubheading(): string
    {
        /** @var Order $order */
        $order = $this->record;

        return $order->status->label().' · placed '.$order->placed_at->format('j M Y, g:i a');
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('confirm')
                ->label('Confirm order')
                ->icon('heroicon-m-check-circle')
                ->visible(fn (Order $record): bool => $record->status === OrderStatus::Placed)
                ->requiresConfirmation()
                ->action(fn (Order $record) => $this->moveTo($record, OrderStatus::Confirmed)),

            Action::make('startPacking')
                ->label('Start packing')
                ->icon('heroicon-m-archive-box')
                ->visible(fn (Order $record): bool => $record->status === OrderStatus::Confirmed)
                ->action(fn (Order $record) => $this->moveTo($record, OrderStatus::Packing)),

            Action::make('pack')
                ->label('Pack into boxes')
                ->icon('heroicon-m-cube')
                ->visible(fn (Order $record): bool => $record->status === OrderStatus::Packing)
                ->schema([
                    TextInput::make('boxes')
                        ->label('How many boxes?')
                        ->numeric()
                        ->default(1)
                        ->minValue(1)
                        ->maxValue(12)
                        ->required()
                        ->helperText('Each box gets its own package id and pickup code, printed on its label.'),
                ])
                ->action(function (Order $record, array $data): void {
                    $this->packIntoBoxes($record, (int) $data['boxes']);
                }),

            Action::make('assign')
                ->label('Assign delivery partner')
                ->icon('heroicon-m-truck')
                ->visible(fn (Order $record): bool => in_array($record->status, [OrderStatus::Packed, OrderStatus::DeliveryFailed], true))
                ->schema([
                    Select::make('user_id')
                        ->label('Delivery partner')
                        ->options(fn (): array => User::where('role', UserRole::Delivery)
                            ->where('is_active', true)
                            ->orderBy('name')
                            ->pluck('name', 'id')
                            ->all())
                        ->searchable()
                        ->required(),
                ])
                ->action(function (Order $record, array $data): void {
                    $this->assign($record, (int) $data['user_id']);
                }),

            ActionGroup::make([
                Action::make('printLabel')
                    ->label('Print labels')
                    ->icon('heroicon-m-printer')
                    ->url(fn (Order $record): string => route('admin.print.label', $record), shouldOpenInNewTab: true),

                Action::make('printInvoice')
                    ->label('Print invoice')
                    ->icon('heroicon-m-document-text')
                    ->url(fn (Order $record): string => route('admin.print.invoice', $record), shouldOpenInNewTab: true),

                Action::make('cancel')
                    ->label('Cancel order')
                    ->icon('heroicon-m-x-circle')
                    ->color('danger')
                    ->visible(fn (Order $record): bool => $record->status->canTransitionTo(OrderStatus::Cancelled))
                    ->schema([
                        Textarea::make('reason')
                            ->label('Why is it being cancelled?')
                            ->required()
                            ->maxLength(200),
                    ])
                    ->requiresConfirmation()
                    ->action(function (Order $record, array $data): void {
                        $record->update(['cancel_reason' => $data['reason']]);
                        $this->moveTo($record, OrderStatus::Cancelled);
                    }),
            ])->label('More')->icon('heroicon-m-ellipsis-horizontal'),
        ];
    }

    private function moveTo(Order $order, OrderStatus $status): void
    {
        OrdersTable::moveTo($order, $status);

        Notification::make()
            ->title($order->order_number.' is now '.$status->label())
            ->success()
            ->send();
    }

    /**
     * Splits the order into boxes, each with its own pickup code (ADR-021).
     * Phase 9 replaces this with a PackOrder action; the screen stays the same.
     */
    private function packIntoBoxes(Order $order, int $boxes): void
    {
        DB::transaction(function () use ($order, $boxes): void {
            $order->packages()->delete();
            $items = $order->items()->get();

            foreach (range(1, $boxes) as $sequence) {
                $package = $order->packages()->create([
                    'package_id' => str_replace('ORD-', 'PKG-', $order->order_number).'-'.$sequence,
                    'sequence' => $sequence,
                    'pickup_code' => (string) random_int(100000, 999999),
                ]);

                foreach ($items as $index => $item) {
                    if ($boxes === 1 || $index % $boxes === $sequence - 1) {
                        $package->items()->create(['order_item_id' => $item->id, 'quantity' => $item->quantity]);
                    }
                }
            }

            OrdersTable::moveTo($order, OrderStatus::Packed);
        });

        Notification::make()
            ->title('Packed into '.$boxes.' '.str('box')->plural($boxes))
            ->body('Print the labels and stick one on each box.')
            ->success()
            ->send();
    }

    /**
     * Hands the order to a delivery partner with a fresh OTP for the customer.
     */
    private function assign(Order $order, int $userId): void
    {
        $otp = (string) random_int(100000, 999999);

        DB::transaction(function () use ($order, $userId, $otp): void {
            $order->assignments()->update(['is_active' => false]);

            DeliveryAssignment::create([
                'order_id' => $order->id,
                'user_id' => $userId,
                'assigned_by' => auth()->id(),
                'step' => DeliveryStep::Assigned,
                'is_active' => true,
                'assigned_at' => now(),
                'otp' => $otp,
            ]);

            OrdersTable::moveTo($order, OrderStatus::Assigned);
        });

        Notification::make()
            ->title('Assigned to '.User::find($userId)?->name)
            ->body('The customer can now see their delivery OTP.')
            ->success()
            ->send();
    }
}
