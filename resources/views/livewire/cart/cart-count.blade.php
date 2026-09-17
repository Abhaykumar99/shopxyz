<div class="contents">
    @if ($variant === 'nav')
        <x-shop.nav-item :href="route('cart.show')" icon="shopping-bag" :active="$active" :count="$count">Bag</x-shop.nav-item>
    @else
        <x-ui.icon-button icon="shopping-bag" label="Your bag" :href="route('cart.show')" :count="$count" />
    @endif
</div>
