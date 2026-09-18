@props([
    'href',
    'active' => false,
    'icon' => null,
])

<li>
    <a
        href="{{ $href }}"
        @if ($active) aria-current="page" @endif
        {{ $attributes->class([
            '-mb-px flex min-h-11 items-center gap-2 border-b-2 px-3 font-medium whitespace-nowrap transition-colors',
            'border-brand text-brand' => $active,
            'border-transparent text-ink-soft hover:border-line-strong hover:text-ink' => ! $active,
        ]) }}
    >
        @if ($icon)
            <x-ui.icon :name="$icon" :size="18" />
        @endif
        {{ $slot }}
    </a>
</li>
