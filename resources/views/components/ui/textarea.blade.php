@props([
    'label' => null,
    'name' => null,
    'id' => null,
    'hint' => null,
    'error' => null,
    'rows' => 3,
    'required' => false,
])

@php
    $id ??= $name ? 'field-'.str_replace(['.', '[', ']'], '-', $name) : 'field-'.uniqid();
    $message = $error ?? (isset($errors) && $name ? $errors->first($name) : null);
    $describedBy = $message ? "{$id}-error" : ($hint ? "{$id}-hint" : null);
@endphp

<x-ui.field :label="$label" :for="$id" :hint="$hint" :error="$message" :required="$required" :class="$attributes->get('class')">
    <textarea
        id="{{ $id }}"
        rows="{{ $rows }}"
        @if ($name) name="{{ $name }}" @endif
        @if ($required) required @endif
        @if ($message) aria-invalid="true" @endif
        @if ($describedBy) aria-describedby="{{ $describedBy }}" @endif
        {{ $attributes->except('class')->class([
            'w-full rounded-field border bg-surface px-3 py-2.5 text-base text-ink placeholder:text-ink-soft',
            'border-danger' => $message,
            'border-line-strong' => ! $message,
        ]) }}
    >{{ $slot }}</textarea>
</x-ui.field>
