<?php

namespace App\Livewire\Delivery;

use App\Support\Demo\DemoCash;
use App\Support\Money;
use Illuminate\Contracts\View\View;
use Livewire\Component;

/**
 * The cash trail (ADR-022): what the delivery boy collected today, what he has
 * handed to the shop and is waiting to be counted, and what is settled. The
 * admin does the counting, so the panel only records the handover.
 */
class CashSummary extends Component
{
    public function handOver(DemoCash $cash): void
    {
        $batch = $cash->handOver();

        if ($batch === null) {
            $this->dispatch('toast', message: 'There is no cash to hand over right now.', tone: 'info');

            return;
        }

        $this->dispatch('close-modal', 'hand-over');
        $this->dispatch('toast', message: "{$batch->reference}: ".Money::format($batch->amountPaise).' handed to the shop. They will count it and mark it settled.', tone: 'success');
    }

    /**
     * Stands in for the admin verifying the batch, so the whole trail can be
     * reviewed in the prototype. Never available outside local previews.
     */
    public function markVerified(string $reference, DemoCash $cash): void
    {
        if (app()->isProduction() || ! $cash->markVerified($reference)) {
            return;
        }

        $this->dispatch('toast', message: "{$reference} settled by the shop.", tone: 'success');
    }

    public function render(DemoCash $cash): View
    {
        return view('livewire.delivery.cash-summary', [
            'withYou' => $cash->withYou(),
            'withYouTotal' => $cash->withYouTotal(),
            'awaiting' => $cash->awaitingVerification(),
            'awaitingTotal' => $cash->awaitingVerificationTotal(),
            'settledToday' => $cash->settledTodayTotal(),
            // The admin panel does the counting (Phase 7); previews can stand in for it.
            'canPreviewVerification' => ! app()->isProduction(),
        ])->layout('layouts::delivery', [
            'title' => 'Cash',
            'active' => 'cash',
        ]);
    }
}
