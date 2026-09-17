<?php

namespace App\Livewire\Account;

use App\Support\Demo\DemoCart;
use App\Support\Demo\DemoOrders;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Locked;
use Livewire\Component;

class OrderShow extends Component
{
    #[Locked]
    public string $number;

    public function mount(string $order, DemoOrders $orders): void
    {
        abort_if($orders->find($order) === null, 404);

        $this->number = $order;
    }

    public function cancel(DemoOrders $orders): void
    {
        $this->dispatch('close-modal', 'cancel-order');

        if ($orders->cancel($this->number)) {
            $this->dispatch('toast', message: "Order {$this->number} is cancelled.", tone: 'info');

            return;
        }

        $this->dispatch('toast', message: 'This order is already being packed or on its way, so it can no longer be cancelled.', tone: 'warning');
    }

    public function buyAgain(DemoOrders $orders, DemoCart $cart): mixed
    {
        $order = $orders->find($this->number) ?? abort(404);
        $added = 0;

        foreach ($order->items as $item) {
            $added += $cart->add($item['sku'], $item['quantity']);
        }

        if ($added === 0) {
            $this->dispatch('toast', message: 'These items are out of stock right now.', tone: 'warning');

            return null;
        }

        session()->flash('toast', ['message' => 'Items added to your bag.', 'tone' => 'success']);

        return $this->redirectRoute('cart.show');
    }

    public function render(DemoOrders $orders): View
    {
        $order = $orders->find($this->number) ?? abort(404);

        return view('livewire.account.order-show', ['order' => $order])
            ->layout('layouts::account', [
                'title' => "Order {$order->number}",
                'heading' => false,
                'tab' => 'orders',
            ]);
    }
}
