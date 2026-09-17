{{-- Works with wire:model (x-modelable) or as a plain form field via `name`. --}}
@props([
    'value' => 1,
    'min' => 1,
    'max' => 20,
    'name' => null,
    'label' => 'Quantity',
])

<div
    x-data="{ qty: {{ (int) $value }}, min: {{ (int) $min }}, max: {{ (int) $max }} }"
    x-modelable="qty"
    {{ $attributes->whereStartsWith('wire:model') }}
    {{ $attributes->whereDoesntStartWith('wire:model')->class('inline-flex h-11 items-center rounded-field border border-line-strong bg-surface') }}
    role="group"
    aria-label="{{ $label }}"
>
    <button type="button" class="flex size-11 items-center justify-center rounded-s-field text-ink hover:bg-mist disabled:opacity-40" x-on:click="qty = Math.max(min, qty - 1)" x-bind:disabled="qty <= min" aria-label="Decrease quantity">
        <x-ui.icon name="minus" :size="18" />
    </button>
    <output class="figures w-9 text-center font-semibold" x-text="qty" aria-live="polite">{{ (int) $value }}</output>
    @if ($name)
        <input type="hidden" name="{{ $name }}" x-bind:value="qty" value="{{ (int) $value }}">
    @endif
    <button type="button" class="flex size-11 items-center justify-center rounded-e-field text-ink hover:bg-mist disabled:opacity-40" x-on:click="qty = Math.min(max, qty + 1)" x-bind:disabled="qty >= max" aria-label="Increase quantity">
        <x-ui.icon name="plus" :size="18" />
    </button>
</div>
