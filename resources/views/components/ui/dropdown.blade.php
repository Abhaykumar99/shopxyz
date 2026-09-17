{{-- Disclosure-style dropdown. Put links or <x-ui.dropdown-item> in the slot. --}}
@props([
    'align' => 'end',
    'width' => 'w-56',
])

<div
    x-data="{ open: false }"
    x-on:keydown.escape.prevent.stop="open = false; $refs.trigger.focus()"
    x-on:focusout="if (! $el.contains($event.relatedTarget)) open = false"
    {{ $attributes->class('relative inline-block') }}
>
    <div x-ref="trigger" x-on:click="open = ! open" x-bind:aria-expanded="open.toString()">
        {{ $trigger }}
    </div>

    <div
        x-cloak
        x-show="open"
        x-transition.opacity.duration.100ms
        x-on:click.outside="open = false"
        @class([
            'absolute z-40 mt-2 flex flex-col overflow-hidden rounded-card border border-line bg-surface py-1 shadow-overlay',
            $width,
            'end-0' => $align === 'end',
            'start-0' => $align !== 'end',
        ])
    >
        {{ $slot }}
    </div>
</div>
