@props([
    'name',
    'url' => '#',
    'slug' => null,
    'count' => null,
])

@php
    [$tint, $icon] = match ($slug) {
        'confectionery' => ['bg-marigold-tint text-marigold-ink', 'candy'],
        'gifts' => ['bg-pistachio-tint text-pistachio', 'gift'],
        'cosmetics' => ['bg-berry-tint text-berry-dark', 'sparkles'],
        default => ['bg-mist text-ink', 'store'],
    };
@endphp

<a href="{{ $url }}" {{ $attributes->class(['flex items-center gap-3 rounded-card p-4 transition-transform active:scale-[0.98]', $tint]) }}>
    <x-ui.icon :name="$icon" :size="32" />
    <span class="flex min-w-0 flex-col">
        <span class="font-display text-lg font-bold">{{ $name }}</span>
        @if ($count !== null)
            <span class="figures text-sm text-ink-soft">{{ $count }} {{ \Illuminate\Support\Str::plural('product', $count) }}</span>
        @endif
    </span>
    <x-ui.icon name="chevron-right" class="ms-auto" />
</a>
