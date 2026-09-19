<?php

namespace App\Livewire\Cart;

use App\Support\Cart\Bag;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\On;
use Livewire\Component;

/**
 * Bag link with a live item count, in the header or the phone bottom navigation.
 */
class CartCount extends Component
{
    public string $variant = 'header';

    public bool $active = false;

    #[On('cart-updated')]
    public function refreshCount(): void
    {
        // Re-renders with the latest count.
    }

    public function render(Bag $bag): View
    {
        return view('livewire.cart.cart-count', ['count' => $bag->count()]);
    }
}
