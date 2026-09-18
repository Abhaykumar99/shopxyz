<?php

namespace App\Livewire\Delivery;

use App\Enums\DeliveryStep;
use App\Support\Demo\DemoDeliveries;
use App\Support\Demo\DemoDeliveryBoy;
use App\Support\Demo\DemoDeliveryJob;
use Illuminate\Contracts\View\View;
use Livewire\Component;

/**
 * Today's round: what is still to be delivered, then what is finished.
 */
class DeliveryList extends Component
{
    public function accept(string $number, DemoDeliveries $deliveries): void
    {
        if ($deliveries->advance($number) === DeliveryStep::Accepted) {
            $this->dispatch('toast', message: "{$number} accepted. Collect it from the shop counter.", tone: 'success');
        }
    }

    public function render(DemoDeliveries $deliveries, DemoDeliveryBoy $deliveryBoy): View
    {
        $today = $deliveries->today();
        $cash = $deliveries->cash();

        return view('livewire.delivery.delivery-list', [
            'jobs' => $today,
            'active' => array_values(array_filter($today, fn (DemoDeliveryJob $job): bool => ! $job->isFinished())),
            'finished' => array_values(array_filter($today, fn (DemoDeliveryJob $job): bool => $job->isFinished())),
            'cash' => $cash,
            'firstName' => $deliveryBoy->firstName(),
        ])->layout('layouts::delivery', [
            'title' => 'My deliveries',
            'active' => 'deliveries',
        ]);
    }
}
