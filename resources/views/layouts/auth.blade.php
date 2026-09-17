{{-- Sign-in pages: customers (Google) and staff (password). --}}
@props(['title'])

<x-layouts::app :title="$title" noindex class="flex flex-col">
    <main id="main" class="flex grow items-center justify-center px-4 py-10">
        <div class="flex w-full max-w-sm flex-col items-center gap-6">
            <x-shop.logo :href="url('/')" />
            <div {{ $attributes->class('w-full rounded-sheet border border-line bg-surface p-6') }}>
                {{ $slot }}
            </div>
            @isset($footer)
                <div class="text-center text-sm text-ink-soft">{{ $footer }}</div>
            @endisset
        </div>
    </main>
</x-layouts::app>
