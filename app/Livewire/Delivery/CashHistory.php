<?php

namespace App\Livewire\Delivery;

use App\Support\Demo\DemoCash;
use App\Support\Demo\DemoCashSettlement;
use Illuminate\Contracts\View\View;
use Livewire\Component;

/**
 * Every cash handover this delivery boy has made, with what the shop did with it.
 */
class CashHistory extends Component
{
    public function render(DemoCash $cash): View
    {
        $batches = $cash->all();

        return view('livewire.delivery.cash-history', [
            'batches' => $batches,
            'settledTotal' => array_sum(array_map(
                fn (DemoCashSettlement $batch): int => $batch->isOpen() ? 0 : $batch->amountPaise,
                $batches,
            )),
        ])->layout('layouts::delivery', [
            'title' => 'Cash history',
            'back' => route('delivery.cash'),
            'active' => 'cash',
        ]);
    }
}
