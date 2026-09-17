@props([
    'tone' => 'info',
    'title' => null,
])

@php
    [$classes, $icon] = match ($tone) {
        'success' => ['border-pistachio/30 bg-pistachio-tint text-pistachio', 'circle-check'],
        'warning' => ['border-marigold/60 bg-marigold-tint text-marigold-ink', 'triangle-alert'],
        'danger' => ['border-danger/30 bg-danger-tint text-danger', 'circle-alert'],
        default => ['border-info/30 bg-info-tint text-info', 'info'],
    };
@endphp

<div role="{{ $tone === 'danger' ? 'alert' : 'status' }}" {{ $attributes->class(['flex gap-3 rounded-card border p-4', $classes]) }}>
    <x-ui.icon :name="$icon" class="mt-0.5" />
    <div class="flex min-w-0 flex-col gap-1">
        @if ($title)
            <p class="font-semibold">{{ $title }}</p>
        @endif
        <div class="text-ink">{{ $slot }}</div>
    </div>
</div>
