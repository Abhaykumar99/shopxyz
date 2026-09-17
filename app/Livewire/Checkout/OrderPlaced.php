<?php

namespace App\Livewire\Checkout;

use App\Support\Demo\DemoOrders;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Locked;
use Livewire\Component;

class OrderPlaced extends Component
{
    #[Locked]
    public string $number;

    public function mount(string $order, DemoOrders $orders): void
    {
        abort_if($orders->find($order) === null, 404);

        $this->number = $order;
    }

    public function render(DemoOrders $orders): View
    {
        $order = $orders->find($this->number) ?? abort(404);

        return view('livewire.checkout.order-placed', ['order' => $order])
            ->layout('layouts::shop', [
                'title' => "Order {$order->number} placed",
                'active' => 'orders',
                'noindex' => true,
            ]);
    }
}
