{{--
    Works with wire:model (x-modelable) or as a plain form field via `name`.
    `editable` swaps the read-only count for a number field, which is what bulk
    quantities need: nobody taps "+" sixty times.
--}}
@props([
    'value' => 1,
    'min' => 1,
    'max' => 20,
    'step' => 1,
    'name' => null,
    'label' => 'Quantity',
    'editable' => false,
])

<div
    x-data="{ qty: {{ (int) $value }}, min: {{ (int) $min }}, max: {{ (int) $max }}, step: {{ max(1, (int) $step) }} }"
    x-modelable="qty"
    {{ $attributes->whereStartsWith('wire:model') }}
    {{ $attributes->whereDoesntStartWith('wire:model')->class('inline-flex h-11 items-center rounded-field border border-line-strong bg-surface') }}
    role="group"
    aria-label="{{ $label }}"
>
    <button type="button" class="flex size-11 items-center justify-center rounded-s-field text-ink hover:bg-mist disabled:opacity-40" x-on:click="qty = Math.max(min, qty - step)" x-bind:disabled="qty <= min" aria-label="Decrease quantity">
        <x-ui.icon name="minus" :size="18" />
    </button>
    @if ($editable)
        <label class="sr-only" for="qty-field-{{ $name ?? \Illuminate\Support\Str::slug($label) }}">{{ $label }}</label>
        <input
            id="qty-field-{{ $name ?? \Illuminate\Support\Str::slug($label) }}"
            type="number"
            inputmode="numeric"
            class="figures h-11 w-16 border-x border-line bg-transparent text-center font-semibold focus:outline-2 focus:-outline-offset-2 focus:outline-brand"
            min="{{ (int) $min }}"
            max="{{ (int) $max }}"
            step="1"
            x-model.number="qty"
            x-on:change="qty = Math.min(max, Math.max(min, Number(qty) || min))"
        >
    @else
        <output class="figures w-9 text-center font-semibold" x-text="qty" aria-live="polite">{{ (int) $value }}</output>
    @endif
    @if ($name)
        <input type="hidden" name="{{ $name }}" x-bind:value="qty" value="{{ (int) $value }}">
    @endif
    <button type="button" class="flex size-11 items-center justify-center rounded-e-field text-ink hover:bg-mist disabled:opacity-40" x-on:click="qty = Math.min(max, qty + step)" x-bind:disabled="qty >= max" aria-label="Increase quantity">
        <x-ui.icon name="plus" :size="18" />
    </button>
</div>
