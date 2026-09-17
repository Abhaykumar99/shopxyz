@props([
    'as' => 'div',
    'padding' => 'md',
])

<{{ $as }} {{ $attributes->class([
    'rounded-card border border-line bg-surface',
    match ($padding) {
        'none' => '',
        'sm' => 'p-3',
        'lg' => 'p-5 sm:p-6',
        default => 'p-4',
    },
]) }}>
    @isset($header)
        <div class="mb-3 flex items-center justify-between gap-3">
            {{ $header }}
        </div>
    @endisset

    {{ $slot }}
</{{ $as }}>
