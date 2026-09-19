<?php

namespace Database\Seeders;

use App\Enums\CashSettlementStatus;
use App\Enums\DeliveryFailureReason;
use App\Enums\DeliveryStep;
use App\Enums\InventoryMovementType;
use App\Enums\OrderStatus;
use App\Enums\PaymentAttemptStatus;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Enums\UserRole;
use App\Models\CodSettlement;
use App\Models\DeliveryAssignment;
use App\Models\InventoryMovement;
use App\Models\Invoice;
use App\Models\Order;
use App\Models\OrderPackage;
use App\Models\ProductVariant;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Database\Seeder;
use Illuminate\Support\Collection;

/**
 * A believable week of trading, so every admin screen has something to show:
 * orders in each status, UPI proofs waiting to be checked, packed boxes with
 * pickup codes, deliveries in progress, and COD cash from collection to
 * settlement (ADR-020 to ADR-022).
 */
class OrderSeeder extends Seeder
{
    private CarbonImmutable $now;

    /** @var Collection<int, User> */
    private Collection $customers;

    /** @var Collection<int, User> */
    private Collection $partners;

    /** @var Collection<int, ProductVariant> */
    private Collection $variants;

    private int $sequence = 10200;

    private int $invoiceSequence = 0;

    public function run(): void
    {
        if (app()->isProduction()) {
            return;
        }

        $this->now = CarbonImmutable::now();
        $this->customers = User::where('role', UserRole::Customer)->get();
        $this->partners = User::where('role', UserRole::Delivery)->get();
        $this->variants = ProductVariant::with('product')->where('stock_quantity', '>', 0)->get();

        if ($this->customers->isEmpty() || $this->variants->isEmpty()) {
            return;
        }

        // Finished business from the past fortnight, for the reports and history.
        foreach (range(1, 26) as $index) {
            $daysAgo = random_int(2, 14);
            $this->order(
                status: OrderStatus::Delivered,
                placedAt: $this->now->subDays($daysAgo)->setTime(random_int(9, 19), random_int(0, 59)),
                method: random_int(0, 1) === 1 ? PaymentMethod::Upi : PaymentMethod::Cod,
                settleCash: $daysAgo > 2,
            );
        }

        foreach (range(1, 3) as $index) {
            $this->order(
                status: OrderStatus::Cancelled,
                placedAt: $this->now->subDays(random_int(3, 12))->setTime(random_int(9, 19), 0),
                method: PaymentMethod::Cod,
            );
        }

        $this->order(status: OrderStatus::DeliveryFailed, placedAt: $this->now->subDay()->setTime(11, 5), method: PaymentMethod::Cod);

        // Today's board.
        $this->order(status: OrderStatus::Placed, placedAt: $this->now->subMinutes(12), method: PaymentMethod::Upi, paymentStatus: PaymentStatus::PendingVerification);
        $this->order(status: OrderStatus::Placed, placedAt: $this->now->subMinutes(38), method: PaymentMethod::Upi, paymentStatus: PaymentStatus::PendingVerification);
        $this->order(status: OrderStatus::Placed, placedAt: $this->now->subHours(4), method: PaymentMethod::Upi, paymentStatus: PaymentStatus::Rejected);
        $this->order(status: OrderStatus::Placed, placedAt: $this->now->subMinutes(25), method: PaymentMethod::Cod);
        $this->order(status: OrderStatus::Confirmed, placedAt: $this->now->subHours(2), method: PaymentMethod::Cod);
        $this->order(status: OrderStatus::Packing, placedAt: $this->now->subHours(3), method: PaymentMethod::Upi, paymentStatus: PaymentStatus::Verified);
        $this->order(status: OrderStatus::Packing, placedAt: $this->now->subHours(5), method: PaymentMethod::Cod, wholesale: true);
        $this->order(status: OrderStatus::Packed, placedAt: $this->now->subHours(6), method: PaymentMethod::Cod);
        $this->order(status: OrderStatus::Assigned, placedAt: $this->now->subHours(7), method: PaymentMethod::Upi, paymentStatus: PaymentStatus::Verified);
        $this->order(status: OrderStatus::OutForDelivery, placedAt: $this->now->subHours(8), method: PaymentMethod::Cod);
        $this->order(status: OrderStatus::OutForDelivery, placedAt: $this->now->subHours(9), method: PaymentMethod::Cod, wholesale: true);
    }

    private function order(
        OrderStatus $status,
        CarbonImmutable $placedAt,
        PaymentMethod $method,
        ?PaymentStatus $paymentStatus = null,
        bool $wholesale = false,
        bool $settleCash = false,
    ): Order {
        $customer = $this->customers->random();
        $address = $customer->addresses()->first();
        $lines = $this->lines($wholesale);

        // The flag follows the lines: an order is only wholesale if a line reached
        // a price band, whatever was asked for.
        $hasWholesaleLines = in_array(true, array_column($lines, 'is_wholesale'), true);

        $subtotal = array_sum(array_column($lines, 'line_total_paise'));
        $mrpTotal = array_sum(array_map(fn (array $line): int => $line['mrp_paise'] * $line['quantity'], $lines));
        $delivery = $subtotal >= (int) config('shop.delivery.free_above_paise') ? 0 : (int) config('shop.delivery.charge_paise');
        $paymentStatus ??= $method->initialPaymentStatus();

        if ($status === OrderStatus::Delivered) {
            $paymentStatus = $method === PaymentMethod::Cod ? PaymentStatus::CodCollected : PaymentStatus::Verified;
        }

        $order = Order::create([
            'order_number' => 'ORD-'.(++$this->sequence),
            'user_id' => $customer->id,
            'status' => $status,
            'payment_method' => $method,
            'payment_status' => $paymentStatus,
            ...($address?->snapshot() ?? [
                'ship_name' => $customer->name,
                'ship_phone' => (string) $customer->phone,
                'ship_line1' => 'Shop counter',
                'ship_city' => 'Patna',
                'ship_state' => 'Bihar',
                'ship_pincode' => '800001',
            ]),
            'subtotal_paise' => $subtotal,
            'discount_paise' => max(0, $mrpTotal - $subtotal),
            'delivery_charge_paise' => $delivery,
            'total_paise' => $subtotal + $delivery,
            'has_wholesale_items' => $hasWholesaleLines,
            'customer_note' => random_int(0, 4) === 0 ? 'Please call before arriving.' : null,
            'placed_at' => $placedAt,
            'confirmed_at' => $status->position() >= OrderStatus::Confirmed->position() ? $placedAt->addMinutes(12) : null,
            'packed_at' => $status->position() >= OrderStatus::Packed->position() ? $placedAt->addMinutes(45) : null,
            'delivered_at' => $status === OrderStatus::Delivered ? $placedAt->addHours(3) : null,
            'cancelled_at' => $status === OrderStatus::Cancelled ? $placedAt->addMinutes(30) : null,
            'cancel_reason' => $status === OrderStatus::Cancelled ? 'Customer changed their mind' : null,
            'created_at' => $placedAt,
            'updated_at' => $placedAt,
        ]);

        foreach ($lines as $line) {
            $order->items()->create($line);
        }

        $this->history($order, $status, $placedAt);
        $this->payment($order, $method, $paymentStatus, $placedAt);
        $this->stockMovements($order, $status, $placedAt);

        if ($status->position() >= OrderStatus::Packed->position() && $status !== OrderStatus::Cancelled) {
            $this->packages($order, $placedAt);
        }

        if (in_array($status, [OrderStatus::Assigned, OrderStatus::OutForDelivery, OrderStatus::Delivered, OrderStatus::DeliveryFailed], true)) {
            $this->assignment($order, $status, $placedAt, $settleCash);
        }

        if ($status === OrderStatus::Delivered) {
            Invoice::create([
                'order_id' => $order->id,
                'invoice_number' => sprintf('INV/2026-27/%05d', ++$this->invoiceSequence),
                'issued_at' => $placedAt->addHours(3),
                'subtotal_paise' => $order->subtotal_paise,
                'discount_paise' => $order->discount_paise,
                'delivery_charge_paise' => $order->delivery_charge_paise,
                'total_paise' => $order->total_paise,
            ]);
        }

        return $order;
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function lines(bool $wholesale): array
    {
        $lines = [];
        $pool = $wholesale
            ? $this->variants->filter(fn (ProductVariant $variant): bool => $variant->minimumWholesaleQuantity() !== null)
            : $this->variants;
        $pool = $pool->isEmpty() ? $this->variants : $pool;
        $picked = $pool->random(min($pool->count(), random_int(1, 3)));

        foreach ($picked as $variant) {
            $minimum = $variant->minimumWholesaleQuantity();
            $quantity = $wholesale && $minimum ? $minimum * random_int(1, 4) : random_int(1, 3);
            $unit = $variant->unitPriceFor($quantity);

            $lines[] = [
                'product_id' => $variant->product_id,
                'product_variant_id' => $variant->id,
                'product_name' => $variant->product->name,
                'variant_name' => $variant->name,
                'sku' => $variant->sku,
                'mrp_paise' => $variant->mrp_paise ?? $variant->price_paise,
                'unit_price_paise' => $unit,
                'quantity' => $quantity,
                'line_total_paise' => $unit * $quantity,
                'is_wholesale' => $minimum !== null && $quantity >= $minimum,
            ];
        }

        return $lines;
    }

    private function history(Order $order, OrderStatus $status, CarbonImmutable $placedAt): void
    {
        $journey = [OrderStatus::Placed, OrderStatus::Confirmed, OrderStatus::Packing, OrderStatus::Packed,
            OrderStatus::Assigned, OrderStatus::OutForDelivery, OrderStatus::Delivered];
        $previous = null;
        $minutes = 0;

        foreach ($journey as $step) {
            if ($step->position() > $status->position()) {
                break;
            }

            $order->statusHistories()->create([
                'from_status' => $previous,
                'to_status' => $step,
                'created_at' => $placedAt->addMinutes($minutes += 15),
            ]);
            $previous = $step;
        }

        if (in_array($status, [OrderStatus::Cancelled, OrderStatus::DeliveryFailed], true)) {
            $order->statusHistories()->create([
                'from_status' => $previous,
                'to_status' => $status,
                'note' => $status === OrderStatus::Cancelled ? 'Cancelled by the customer' : 'Nobody at the address',
                'created_at' => $placedAt->addMinutes($minutes + 20),
            ]);
        }
    }

    private function payment(Order $order, PaymentMethod $method, PaymentStatus $paymentStatus, CarbonImmutable $placedAt): void
    {
        if ($method !== PaymentMethod::Upi) {
            return;
        }

        $status = match ($paymentStatus) {
            PaymentStatus::Verified => PaymentAttemptStatus::Verified,
            PaymentStatus::Rejected => PaymentAttemptStatus::Rejected,
            default => PaymentAttemptStatus::Submitted,
        };

        $order->payments()->create([
            'method' => PaymentMethod::Upi,
            'amount_paise' => $order->total_paise,
            'status' => $status,
            'utr' => (string) random_int(100000000000, 999999999999),
            'proof_path' => 'payment-proofs/sample-'.$order->order_number.'.jpg',
            'submitted_at' => $placedAt->addMinutes(6),
            'verified_at' => $status === PaymentAttemptStatus::Verified ? $placedAt->addMinutes(20) : null,
            'rejection_reason' => $status === PaymentAttemptStatus::Rejected
                ? 'We could not find a payment with this UTR. Please check the number in your UPI app.'
                : null,
            'created_at' => $placedAt->addMinutes(6),
        ]);
    }

    private function stockMovements(Order $order, OrderStatus $status, CarbonImmutable $placedAt): void
    {
        foreach ($order->items as $item) {
            if ($item->product_variant_id === null) {
                continue;
            }

            InventoryMovement::create([
                'product_variant_id' => $item->product_variant_id,
                'order_id' => $order->id,
                'type' => InventoryMovementType::Sale,
                'quantity_change' => -$item->quantity,
                'stock_after' => max(0, ($item->variant->stock_quantity ?? $item->quantity) - $item->quantity),
                'created_at' => $placedAt,
            ]);

            if ($status === OrderStatus::Cancelled) {
                InventoryMovement::create([
                    'product_variant_id' => $item->product_variant_id,
                    'order_id' => $order->id,
                    'type' => InventoryMovementType::CancelRestore,
                    'quantity_change' => $item->quantity,
                    'stock_after' => $item->variant->stock_quantity ?? $item->quantity,
                    'created_at' => $placedAt->addMinutes(30),
                ]);
            }
        }
    }

    /**
     * Packs the order into one or two boxes, each with its own pickup code (ADR-021).
     */
    private function packages(Order $order, CarbonImmutable $placedAt): void
    {
        $items = $order->items;
        $boxes = $items->count() > 1 && random_int(0, 1) === 1 ? 2 : 1;
        $pickedUp = in_array($order->status, [OrderStatus::OutForDelivery, OrderStatus::Delivered, OrderStatus::DeliveryFailed], true);

        foreach (range(1, $boxes) as $sequence) {
            $package = OrderPackage::create([
                'order_id' => $order->id,
                'package_id' => str_replace('ORD-', 'PKG-', $order->order_number).'-'.$sequence,
                'sequence' => $sequence,
                'pickup_code' => (string) random_int(100000, 999999),
                'picked_up_at' => $pickedUp ? $placedAt->addMinutes(70) : null,
                'created_at' => $placedAt->addMinutes(45),
            ]);

            foreach ($items as $index => $item) {
                if ($boxes === 1 || $index % $boxes === $sequence - 1) {
                    $package->items()->create([
                        'order_item_id' => $item->id,
                        'quantity' => $item->quantity,
                    ]);
                }
            }
        }
    }

    private function assignment(Order $order, OrderStatus $status, CarbonImmutable $placedAt, bool $settleCash): void
    {
        $partner = $this->partners->random();
        $step = match ($status) {
            OrderStatus::Assigned => DeliveryStep::Accepted,
            OrderStatus::OutForDelivery => DeliveryStep::OutForDelivery,
            OrderStatus::Delivered => DeliveryStep::Delivered,
            default => DeliveryStep::Failed,
        };
        $cash = $status === OrderStatus::Delivered && $order->isCod() ? $order->total_paise : 0;

        $assignment = DeliveryAssignment::create([
            'order_id' => $order->id,
            'user_id' => $partner->id,
            'step' => $step,
            'is_active' => true,
            'assigned_at' => $placedAt->addMinutes(50),
            'accepted_at' => $placedAt->addMinutes(55),
            'picked_up_at' => $step === DeliveryStep::Accepted ? null : $placedAt->addMinutes(70),
            'out_for_delivery_at' => in_array($step, [DeliveryStep::OutForDelivery, DeliveryStep::Delivered, DeliveryStep::Failed], true)
                ? $placedAt->addMinutes(75) : null,
            'delivered_at' => $step === DeliveryStep::Delivered ? $placedAt->addHours(3) : null,
            'failed_at' => $step === DeliveryStep::Failed ? $placedAt->addHours(2) : null,
            'failure_reason' => $step === DeliveryStep::Failed ? DeliveryFailureReason::NobodyHome : null,
            'otp' => (string) random_int(100000, 999999),
            'cash_collected_paise' => $cash,
            'created_at' => $placedAt->addMinutes(50),
        ]);

        if ($cash > 0 && $settleCash) {
            $this->settle($assignment, $partner, $placedAt);
        }
    }

    private function settle(DeliveryAssignment $assignment, User $partner, CarbonImmutable $placedAt): void
    {
        $handedOverAt = $placedAt->endOfDay()->subHours(2);
        $reference = 'CS-'.$partner->id.$handedOverAt->format('md');

        $settlement = CodSettlement::firstOrCreate(
            ['reference' => $reference],
            [
                'user_id' => $partner->id,
                'amount_paise' => 0,
                'status' => CashSettlementStatus::Settled,
                'handed_over_at' => $handedOverAt,
                'verified_at' => $handedOverAt->addMinutes(20),
                'counted_paise' => 0,
                'created_at' => $handedOverAt,
            ],
        );

        $assignment->update(['cod_settlement_id' => $settlement->id]);
        $settlement->update([
            'amount_paise' => $settlement->amount_paise + $assignment->cash_collected_paise,
            'counted_paise' => $settlement->amount_paise + $assignment->cash_collected_paise,
        ]);
    }
}
