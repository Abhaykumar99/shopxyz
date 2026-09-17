@props([
    'label' => null,
    'name' => null,
    'id' => null,
    'type' => 'text',
    'hint' => null,
    'error' => null,
    'prefix' => null,
    'icon' => null,
    'required' => false,
])

@php
    $id ??= $name ? 'field-'.str_replace(['.', '[', ']'], '-', $name) : 'field-'.uniqid();
    $message = $error ?? (isset($errors) && $name ? $errors->first($name) : null);
    $describedBy = $message ? "{$id}-error" : ($hint ? "{$id}-hint" : null);
@endphp

<x-ui.field :label="$label" :for="$id" :hint="$hint" :error="$message" :required="$required" :class="$attributes->get('class')">
    <div @class([
        'flex min-h-11 items-stretch overflow-hidden rounded-field border bg-surface focus-within:outline-2 focus-within:outline-offset-2 focus-within:outline-berry',
        'border-danger' => $message,
        'border-line-strong' => ! $message,
    ])>
        @if ($prefix)
            <span class="figures flex items-center border-e border-line bg-mist px-3 text-ink-soft">{{ $prefix }}</span>
        @endif
        @if ($icon)
            <span class="flex items-center ps-3 text-ink-soft"><x-ui.icon :name="$icon" /></span>
        @endif
        <input
            id="{{ $id }}"
            type="{{ $type }}"
            @if ($name) name="{{ $name }}" @endif
            @if ($required) required @endif
            @if ($message) aria-invalid="true" @endif
            @if ($describedBy) aria-describedby="{{ $describedBy }}" @endif
            {{ $attributes->except('class')->class('w-full min-w-0 bg-transparent px-3 text-base text-ink placeholder:text-ink-soft focus:outline-none') }}
        >
    </div>
</x-ui.field>
