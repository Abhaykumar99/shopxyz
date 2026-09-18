{{--
    Delivery panel: phone-only, big touch targets, one primary action pinned
    to the bottom (the `action` slot) within thumb reach.

    `active`: deliveries | cash | history | profile
--}}
@props([
    'title',
    'back' => null,
    'active' => 'deliveries',
])

@php
    $tabs = [
        ['deliveries', 'Deliveries', 'package', route('delivery.index')],
        ['cash', 'Cash', 'banknote', route('delivery.cash')],
        ['history', 'History', 'clock', route('delivery.history')],
        ['profile', 'Profile', 'user', route('delivery.profile')],
    ];
@endphp

<x-layouts::app :title="$title" noindex class="min-h-dvh bg-mist">
    <header class="sticky top-0 z-30 border-b border-line bg-surface">
        <div class="mx-auto flex h-14 max-w-lg items-center gap-2 px-2">
            @if ($back)
                <x-ui.icon-button icon="arrow-left" label="Back" :href="$back" wire:navigate />
            @else
                <span class="flex items-center gap-2 px-2">
                    <x-ui.icon name="bike" class="text-brand" />
                </span>
            @endif
            <h1 class="figures min-w-0 grow truncate font-display text-lg font-bold">{{ $title }}</h1>
            <x-ui.dropdown>
                <x-slot:trigger>
                    <x-ui.icon-button icon="menu" label="Menu" />
                </x-slot:trigger>
                <x-ui.dropdown-item :href="route('delivery.index')" icon="package">My deliveries</x-ui.dropdown-item>
                <x-ui.dropdown-item :href="route('delivery.cash')" icon="banknote">Cash to hand over</x-ui.dropdown-item>
                @if ($shop->phone)
                    <x-ui.dropdown-item :href="'tel:'.preg_replace('/\s+/', '', $shop->phone)" icon="phone">Call {{ $shop->name }}</x-ui.dropdown-item>
                @endif
                <x-ui.dropdown-item :href="route('delivery.profile')" icon="user">Profile and sign out</x-ui.dropdown-item>
            </x-ui.dropdown>
        </div>
    </header>

    <main id="main" {{ $attributes->class(['mx-auto flex w-full max-w-lg flex-col gap-3 px-3 py-4 pb-24', 'pb-44' => isset($action)]) }}>
        {{ $slot }}
    </main>

    @isset($action)
        <div class="pb-safe fixed inset-x-0 bottom-16 z-30 border-t border-line bg-surface">
            <div class="mx-auto flex max-w-lg flex-col gap-2 px-3 py-3">
                {{ $action }}
            </div>
        </div>
    @endisset

    <nav aria-label="Delivery panel" class="pb-safe fixed inset-x-0 bottom-0 z-40 border-t border-line bg-surface">
        <ul class="mx-auto flex max-w-lg">
            @foreach ($tabs as [$key, $label, $icon, $href])
                <li class="flex-1">
                    <a
                        href="{{ $href }}"
                        wire:navigate
                        @if ($active === $key) aria-current="page" @endif
                        @class([
                            'flex min-h-16 flex-col items-center justify-center gap-1 text-xs font-medium',
                            'text-brand' => $active === $key,
                            'text-ink-soft hover:text-ink' => $active !== $key,
                        ])
                    >
                        <x-ui.icon :name="$icon" :size="22" />
                        {{ $label }}
                    </a>
                </li>
            @endforeach
        </ul>
    </nav>
</x-layouts::app>
