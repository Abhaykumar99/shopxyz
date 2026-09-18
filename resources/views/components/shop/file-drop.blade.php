{{-- Screenshot upload field (payment proof). Validation happens server-side in Phase 6. --}}
@props([
    'label',
    'name',
    'id' => null,
    'hint' => 'JPG, PNG or WebP, up to 4 MB.',
    'error' => null,
    'accept' => 'image/jpeg,image/png,image/webp',
])

@php
    $id ??= 'field-'.$name;
    $message = $error ?? (isset($errors) ? $errors->first($name) : null);
@endphp

<x-ui.field :label="$label" :for="$id" :hint="$hint" :error="$message" required :class="$attributes->get('class')">
    <label
        x-data="{ file: '' }"
        for="{{ $id }}"
        @class([
            'flex cursor-pointer flex-col items-center gap-2 rounded-card border-2 border-dashed bg-paper px-4 py-6 text-center hover:bg-mist has-focus-visible:outline-2 has-focus-visible:outline-offset-2 has-focus-visible:outline-brand',
            'border-danger' => $message,
            'border-line-strong' => ! $message,
        ])
    >
        <x-ui.icon name="upload" :size="28" class="text-brand" />
        <span class="font-semibold text-ink" x-text="file || 'Choose screenshot'">Choose screenshot</span>
        <span class="text-sm text-ink-soft" x-show="! file">or take a photo</span>
        <input
            id="{{ $id }}"
            type="file"
            name="{{ $name }}"
            accept="{{ $accept }}"
            class="sr-only"
            x-on:change="file = $event.target.files[0]?.name ?? ''"
            aria-describedby="{{ $message ? $id.'-error' : $id.'-hint' }}"
            @if ($message) aria-invalid="true" @endif
            {{ $attributes->except('class') }}
        >
    </label>
</x-ui.field>
