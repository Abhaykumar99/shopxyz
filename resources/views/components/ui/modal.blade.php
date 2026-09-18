{{--
    Accessible dialog built on the native <dialog> element (focus handling,
    Escape to close and background inertness come from the browser).
    Open:  $dispatch('open-modal', 'filters')      Close: $dispatch('close-modal', 'filters')
    `sheet` slides up from the bottom on phones (filters, quick actions).
--}}
@props([
    'name',
    'title',
    'sheet' => false,
    'maxWidth' => 'md',
])

@php
    $width = match ($maxWidth) {
        'sm' => 'sm:max-w-sm',
        'lg' => 'sm:max-w-2xl',
        default => 'sm:max-w-lg',
    };
@endphp

<dialog
    wire:ignore.self
    x-data
    x-on:open-modal.window="if ($event.detail === @js($name)) $el.showModal()"
    x-on:close-modal.window="if ($event.detail === @js($name)) $el.close()"
    x-on:click="if ($event.target === $el) $el.close()"
    aria-labelledby="modal-{{ $name }}-title"
    {{ $attributes->class([
        'max-h-[92dvh] bg-surface text-ink shadow-overlay backdrop:bg-ink/50',
        $sheet
            ? 'mx-0 mt-auto mb-0 w-full max-w-none animate-sheet-up rounded-t-sheet sm:m-auto sm:w-[calc(100%-2rem)] sm:rounded-sheet'
            : 'm-auto w-[calc(100%-2rem)] max-w-none animate-pop rounded-sheet',
        $width,
    ]) }}
>
    <div class="flex max-h-[92dvh] flex-col">
        <header class="flex items-center justify-between gap-3 border-b border-line px-5 py-3">
            <h2 id="modal-{{ $name }}-title" class="text-xl font-semibold">{{ $title }}</h2>
            <x-ui.icon-button icon="x" label="Close" x-on:click="$el.closest('dialog').close()" />
        </header>

        <div class="overflow-y-auto px-5 py-4">
            {{ $slot }}
        </div>

        @isset($footer)
            <footer class="pb-safe flex flex-col-reverse gap-2 border-t border-line px-5 py-3 sm:flex-row sm:justify-end">
                {{ $footer }}
            </footer>
        @endisset
    </div>
</dialog>
