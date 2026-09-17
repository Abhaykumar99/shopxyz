{{--
    Delivery panel: phone-only, big touch targets, one primary action pinned
    to the bottom (the `action` slot) within thumb reach.
--}}
@props([
    'title',
    'back' => null,
])

<x-layouts::app :title="$title" noindex class="bg-mist">
    <header class="sticky top-0 z-30 border-b border-line bg-surface">
        <div class="mx-auto flex h-14 max-w-lg items-center gap-2 px-2">
            @if ($back)
                <x-ui.icon-button icon="arrow-left" label="Back" :href="$back" />
            @else
                <span class="flex items-center gap-2 px-2">
                    <x-ui.icon name="bike" class="text-berry" />
                </span>
            @endif
            <h1 class="figures min-w-0 grow truncate font-display text-lg font-bold">{{ $title }}</h1>
            <x-ui.dropdown>
                <x-slot:trigger>
                    <x-ui.icon-button icon="menu" label="Menu" />
                </x-slot:trigger>
                <x-ui.dropdown-item :href="url('/delivery')" icon="package">My deliveries</x-ui.dropdown-item>
                @if ($shop->phone)
                    <x-ui.dropdown-item :href="'tel:'.preg_replace('/\s+/', '', $shop->phone)" icon="phone">Call {{ $shop->name }}</x-ui.dropdown-item>
                @endif
                <x-ui.dropdown-item icon="log-out" tone="danger">Log out</x-ui.dropdown-item>
            </x-ui.dropdown>
        </div>
    </header>

    <main id="main" {{ $attributes->class(['mx-auto flex w-full max-w-lg flex-col gap-3 px-3 py-4', 'pb-28' => isset($action)]) }}>
        {{ $slot }}
    </main>

    @isset($action)
        <div class="pb-safe fixed inset-x-0 bottom-0 z-30 border-t border-line bg-surface">
            <div class="mx-auto flex max-w-lg flex-col gap-2 px-3 py-3">
                {{ $action }}
            </div>
        </div>
    @endisset
</x-layouts::app>
