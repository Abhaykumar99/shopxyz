<?php

namespace App\Livewire\Delivery;

use App\Support\Demo\DemoDeliveries;
use Illuminate\Contracts\View\View;
use Livewire\Component;

/**
 * Cash collected today and what still has to be handed over at the shop.
 * The admin confirms the handover (Phase 9), so the panel only reports.
 */
class CashSummary extends Component
{
    public function render(DemoDeliveries $deliveries): View
    {
        return view('livewire.delivery.cash-summary', [
            'cash' => $deliveries->cash(),
        ])->layout('layouts::delivery', [
            'title' => 'Cash to hand over',
            'active' => 'cash',
        ]);
    }
}
