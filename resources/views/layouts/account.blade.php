{{-- Customer account area. `tab`: profile | orders | addresses --}}
@props([
    'title',
    'heading' => null,
    'tab' => 'profile',
])

<x-layouts::shop :title="$title" :active="$tab === 'orders' ? 'orders' : 'account'" noindex>
    <div class="flex flex-col gap-5">
        <x-ui.tabs label="Your account">
            <x-ui.tab :href="route('account.profile')" icon="user" :active="$tab === 'profile'">Profile</x-ui.tab>
            <x-ui.tab :href="route('account.orders')" icon="package" :active="$tab === 'orders'">Orders</x-ui.tab>
            <x-ui.tab :href="route('account.addresses')" icon="map-pin" :active="$tab === 'addresses'">Addresses</x-ui.tab>
        </x-ui.tabs>

        <div {{ $attributes->class('flex flex-col gap-5') }}>
            @if ($heading !== false)
                <h1 class="text-2xl font-bold sm:text-3xl">{{ $heading ?? $title }}</h1>
            @endif
            {{ $slot }}
        </div>
    </div>
</x-layouts::shop>
