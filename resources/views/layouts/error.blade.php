{{--
    Minimal layout for error pages. It avoids Livewire components and session data
    so it still renders when the error happened outside the normal request flow.
--}}
@props([
    'title',
    'code',
])

<x-layouts::app :title="$title" noindex class="flex flex-col">
    <header class="border-b border-line bg-paper">
        <div class="mx-auto flex h-16 max-w-6xl items-center px-4">
            <x-shop.logo :href="url('/')" />
        </div>
    </header>
    <main id="main" class="mx-auto flex w-full max-w-lg grow flex-col items-center justify-center gap-5 px-4 py-16 text-center">
        <span class="tag-shape figures inline-flex h-10 items-center bg-brand-tint pe-4 font-display text-lg font-bold text-brand-dark">{{ $code }}</span>
        <h1 class="text-3xl font-bold">{{ $title }}</h1>
        <div class="text-lg text-ink-soft">{{ $slot }}</div>
        @isset($actions)
            <div class="flex flex-wrap justify-center gap-2">{{ $actions }}</div>
        @endisset
    </main>
</x-layouts::app>
