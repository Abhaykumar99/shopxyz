{{-- A large selectable option (payment method, delivery address). --}}
@props([
    'name',
    'value',
    'title',
    'description' => null,
    'icon' => null,
    'id' => null,
])

@php($id ??= 'option-'.$name.'-'.$value)
@php($disabled = (bool) $attributes->get('disabled', false))

<label
    for="{{ $id }}"
    {{ $attributes->only('class')->class([
        'flex items-start gap-3 rounded-card border border-line bg-surface p-4 transition-colors has-checked:border-brand has-checked:bg-brand-tint has-focus-visible:outline-2 has-focus-visible:outline-offset-2 has-focus-visible:outline-brand',
        'cursor-pointer hover:border-line-strong' => ! $disabled,
        'cursor-not-allowed opacity-60' => $disabled,
    ]) }}
>
    <input
        id="{{ $id }}"
        type="radio"
        name="{{ $name }}"
        value="{{ $value }}"
        {{ $attributes->except('class')->class('mt-1 size-5 shrink-0 accent-brand focus-visible:outline-none') }}
    >
    <span class="flex min-w-0 grow flex-col gap-0.5">
        <span class="font-semibold text-ink">{{ $title }}</span>
        @if ($description)
            <span class="text-sm text-ink-soft">{{ $description }}</span>
        @endif
        {{ $slot }}
    </span>
    @if ($icon)
        <x-ui.icon :name="$icon" :size="24" class="text-ink-soft" />
    @endif
</label>
