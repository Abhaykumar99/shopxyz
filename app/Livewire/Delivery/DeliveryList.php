<?php

namespace App\Livewire\Delivery;

use App\Enums\DeliveryStep;
use App\Support\Demo\DemoCash;
use App\Support\Demo\DemoDeliveries;
use App\Support\Demo\DemoDeliveryJob;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Url;
use Livewire\Component;

/**
 * Today's round, split into the groups a delivery boy works through:
 * to pick up, picked up, out for delivery, delivered and failed (ADR-022).
 */
class DeliveryList extends Component
{
    #[Url(as: 'show', except: '')]
    public string $filter = '';

    public function accept(string $number, DemoDeliveries $deliveries): void
    {
        if ($deliveries->advance($number) === DeliveryStep::Accepted) {
            $this->dispatch('toast', message: "{$number} accepted. Enter the pickup code on each box at the counter.", tone: 'success');
        }
    }

    public function startDelivery(string $number, DemoDeliveries $deliveries): void
    {
        if ($deliveries->advance($number) === DeliveryStep::OutForDelivery) {
            $this->dispatch('toast', message: "{$number} is out for delivery. The customer can see you are on the way.", tone: 'success');
        }
    }

    public function render(DemoDeliveries $deliveries, DemoCash $cash): View
    {
        $groups = $deliveries->grouped();
        $filter = array_key_exists($this->filter, $groups) ? $this->filter : '';

        return view('livewire.delivery.delivery-list', [
            'groups' => $filter === '' ? $groups : [$filter => $groups[$filter]],
            'counts' => array_map(fn (array $jobs): int => count($jobs), $groups),
            'filter' => $filter,
            'summary' => $deliveries->summary(),
            'cashWithYou' => $cash->withYouTotal(),
            'firstName' => auth()->user()?->firstName() ?? '',
            'nextJob' => collect($deliveries->today())->first(fn (DemoDeliveryJob $job): bool => ! $job->isFinished()),
        ])->layout('layouts::delivery', [
            'title' => 'My deliveries',
            'active' => 'deliveries',
        ]);
    }
}
