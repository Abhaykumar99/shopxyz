<?php

namespace App\Livewire\Delivery;

use App\Support\Demo\DemoDeliveries;
use App\Support\Demo\DemoDeliveryJob;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Str;
use Livewire\Attributes\Url;
use Livewire\Component;

/**
 * Finished deliveries, so the delivery boy can check what happened with an order.
 */
class DeliveryHistory extends Component
{
    #[Url(as: 'q', except: '')]
    public string $search = '';

    public function render(DemoDeliveries $deliveries): View
    {
        $search = Str::lower(trim(Str::limit($this->search, 30, '')));
        $jobs = collect($deliveries->history())
            ->when($search !== '', fn ($history) => $history->filter(
                fn (DemoDeliveryJob $job): bool => str_contains(Str::lower("{$job->number} {$job->customerName} {$job->area}"), $search),
            ))
            ->values()
            ->all();

        return view('livewire.delivery.delivery-history', [
            'jobs' => $jobs,
        ])->layout('layouts::delivery', [
            'title' => 'History',
            'active' => 'history',
        ]);
    }
}
