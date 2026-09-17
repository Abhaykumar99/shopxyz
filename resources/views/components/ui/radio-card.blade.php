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

<label
    for="{{ $id }}"
    {{ $attributes->only('class')->class('flex cursor-pointer items-start gap-3 rounded-card border border-line bg-surface p-4 transition-colors hover:border-line-strong has-checked:border-berry has-checked:bg-berry-tint has-focus-visible:outline-2 has-focus-visible:outline-offset-2 has-focus-visible:outline-berry') }}
>
    <input
        id="{{ $id }}"
        type="radio"
        name="{{ $name }}"
        value="{{ $value }}"
        {{ $attributes->except('class')->class('mt-1 size-5 shrink-0 accent-berry focus-visible:outline-none') }}
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
