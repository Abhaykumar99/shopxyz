{{-- Customer account area. `tab`: profile | orders | addresses --}}
@props([
    'title',
    'tab' => 'profile',
    'cartCount' => 0,
])

<x-layouts::shop :title="$title" :active="$tab === 'orders' ? 'orders' : 'account'" :cart-count="$cartCount">
    <div class="flex flex-col gap-5">
        <h1 class="text-2xl font-bold sm:text-3xl">{{ $title }}</h1>

        <x-ui.tabs label="Your account">
            <x-ui.tab :href="url('/account')" icon="user" :active="$tab === 'profile'">Profile</x-ui.tab>
            <x-ui.tab :href="url('/account/orders')" icon="package" :active="$tab === 'orders'">Orders</x-ui.tab>
            <x-ui.tab :href="url('/account/addresses')" icon="map-pin" :active="$tab === 'addresses'">Addresses</x-ui.tab>
        </x-ui.tabs>

        <div {{ $attributes }}>
            {{ $slot }}
        </div>
    </div>
</x-layouts::shop>
