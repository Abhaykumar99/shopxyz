@props([
    'label',
    'name' => null,
    'id' => null,
    'hint' => null,
])

@php($id ??= $name ? 'field-'.str_replace(['.', '[', ']'], '-', $name) : 'field-'.uniqid())

<div {{ $attributes->only('class')->class('flex items-start gap-3') }}>
    <input
        id="{{ $id }}"
        type="checkbox"
        @if ($name) name="{{ $name }}" @endif
        @if ($hint) aria-describedby="{{ $id }}-hint" @endif
        {{ $attributes->except('class')->class('mt-0.5 size-5 shrink-0 accent-brand') }}
    >
    <div class="flex flex-col">
        <label for="{{ $id }}" class="text-base text-ink">{{ $label }}</label>
        @if ($hint)
            <p id="{{ $id }}-hint" class="text-sm text-ink-soft">{{ $hint }}</p>
        @endif
    </div>
</div>
