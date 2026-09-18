<?php

namespace App\Livewire\Account;

use App\Support\Demo\DemoOrder;
use App\Support\Demo\DemoOrders;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Url;
use Livewire\Component;

class OrderList extends Component
{
    #[Url(except: 'active')]
    public string $show = 'active';

    public function render(DemoOrders $orders): View
    {
        $all = $orders->all();
        $active = array_values(array_filter($all, fn (DemoOrder $order): bool => $order->isActive()));
        $past = array_values(array_filter($all, fn (DemoOrder $order): bool => ! $order->isActive()));
        $show = $this->show === 'past' ? 'past' : 'active';

        return view('livewire.account.order-list', [
            'orders' => $show === 'past' ? $past : $active,
            'counts' => ['active' => count($active), 'past' => count($past)],
            'current' => $show,
        ])->layout('layouts::account', [
            'title' => 'Your orders',
            'tab' => 'orders',
        ]);
    }
}
