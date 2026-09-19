<?php

use App\Enums\CashSettlementStatus;
use App\Enums\DeliveryStep;
use App\Filament\Widgets\CashPosition;
use App\Models\CodSettlement;
use App\Models\DeliveryAssignment;
use App\Models\User;
use Livewire\Livewire;

/**
 * The admin has to be able to answer "who is holding the shop's cash?" without
 * opening each delivery partner (ADR-022).
 */
it('adds up what each partner collected, still holds, handed over and settled', function () {
    $this->actingAs(User::factory()->admin()->create());

    $partner = User::factory()->deliveryPartner()->create(['name' => 'Rahul Kumar']);

    $settlement = CodSettlement::factory()->create([
        'user_id' => $partner->id,
        'amount_paise' => 50000,
        'status' => CashSettlementStatus::AwaitingVerification,
    ]);

    CodSettlement::factory()->settled()->create([
        'user_id' => $partner->id,
        'amount_paise' => 30000,
    ]);

    // Handed over, so it is no longer in their pocket.
    DeliveryAssignment::factory()->step(DeliveryStep::Delivered)->create([
        'user_id' => $partner->id,
        'cash_collected_paise' => 50000,
        'cod_settlement_id' => $settlement->id,
    ]);

    // Still with them.
    DeliveryAssignment::factory()->step(DeliveryStep::Delivered)->create([
        'user_id' => $partner->id,
        'cash_collected_paise' => 20000,
        'cod_settlement_id' => null,
    ]);

    $row = Livewire::test(CashPosition::class)
        ->assertCanSeeTableRecords([$partner])
        ->instance()
        ->getTable()
        ->getRecords()
        ->firstWhere('id', $partner->id);

    expect((int) $row->delivered_cod_count)->toBe(2)
        ->and((int) $row->collected_paise)->toBe(70000)
        ->and((int) $row->with_partner_paise)->toBe(20000)
        ->and((int) $row->awaiting_paise)->toBe(50000)
        ->and((int) $row->settled_paise)->toBe(30000);
});

it('lists only delivery partners, never customers or admins', function () {
    $this->actingAs($admin = User::factory()->admin()->create());
    $partner = User::factory()->deliveryPartner()->create();
    $customer = User::factory()->googleCustomer()->create();

    Livewire::test(CashPosition::class)
        ->assertCanSeeTableRecords([$partner])
        ->assertCanNotSeeTableRecords([$admin, $customer]);
});
