@props([
    'label' => null,
    'name' => null,
    'id' => null,
    'hint' => null,
    'error' => null,
    'options' => [],
    'selected' => null,
    'placeholder' => null,
    'required' => false,
])

@php
    $id ??= $name ? 'field-'.str_replace(['.', '[', ']'], '-', $name) : 'field-'.uniqid();
    $message = $error ?? (isset($errors) && $name ? $errors->first($name) : null);
    $describedBy = $message ? "{$id}-error" : ($hint ? "{$id}-hint" : null);
@endphp

<x-ui.field :label="$label" :for="$id" :hint="$hint" :error="$message" :required="$required" :class="$attributes->get('class')">
    <div class="relative">
        <select
            id="{{ $id }}"
            @if ($name) name="{{ $name }}" @endif
            @if ($required) required @endif
            @if ($message) aria-invalid="true" @endif
            @if ($describedBy) aria-describedby="{{ $describedBy }}" @endif
            {{ $attributes->except('class')->class([
                'min-h-11 w-full appearance-none rounded-field border bg-surface ps-3 pe-10 text-base text-ink',
                'border-danger' => $message,
                'border-line-strong' => ! $message,
            ]) }}
        >
            @if ($placeholder)
                <option value="" disabled @selected($selected === null)>{{ $placeholder }}</option>
            @endif
            @foreach ($options as $value => $text)
                <option value="{{ $value }}" @selected((string) $selected === (string) $value)>{{ $text }}</option>
            @endforeach
            {{ $slot }}
        </select>
        <x-ui.icon name="chevron-down" class="pointer-events-none absolute end-3 top-1/2 -translate-y-1/2 text-ink-soft" />
    </div>
</x-ui.field>
