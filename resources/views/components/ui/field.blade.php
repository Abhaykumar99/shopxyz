{{-- Label, hint and error wrapper shared by every form control. --}}
@props([
    'label' => null,
    'for' => null,
    'hint' => null,
    'error' => null,
    'required' => false,
])

<div {{ $attributes->class('flex flex-col gap-1.5') }}>
    @if ($label)
        <label for="{{ $for }}" class="text-sm font-semibold text-ink">
            {{ $label }}
            @unless ($required)
                <span class="font-normal text-ink-soft">(optional)</span>
            @endunless
        </label>
    @endif

    {{ $slot }}

    @if ($hint && ! $error)
        <p id="{{ $for }}-hint" class="text-sm text-ink-soft">{{ $hint }}</p>
    @endif

    @if ($error)
        <p id="{{ $for }}-error" class="flex items-start gap-1.5 text-sm font-medium text-danger">
            <x-ui.icon name="circle-alert" :size="16" class="mt-0.5" />
            <span>{{ $error }}</span>
        </p>
    @endif
</div>
