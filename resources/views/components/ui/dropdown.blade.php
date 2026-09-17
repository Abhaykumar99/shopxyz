{{--
    Disclosure-style dropdown. The first button or link in the `trigger` slot
    gets aria-expanded/aria-controls. Put links or <x-ui.dropdown-item> in the slot.
--}}
@props([
    'align' => 'end',
    'width' => 'w-56',
])

@php($panelId = 'dropdown-'.uniqid())

<div
    x-data="{ open: false, button() { return this.$refs.trigger.querySelector('button, a') } }"
    x-init="button()?.setAttribute('aria-controls', @js($panelId))"
    x-effect="button()?.setAttribute('aria-expanded', open ? 'true' : 'false')"
    x-on:keydown.escape.prevent.stop="open = false; button()?.focus()"
    x-on:focusout="if (! $el.contains($event.relatedTarget)) open = false"
    {{ $attributes->class('relative inline-block') }}
>
    <div x-ref="trigger" x-on:click="open = ! open">
        {{ $trigger }}
    </div>

    <div
        id="{{ $panelId }}"
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
