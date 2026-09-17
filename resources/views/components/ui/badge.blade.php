@props([
    'tone' => 'neutral',
    'icon' => null,
])

<span {{ $attributes->class([
    'inline-flex items-center gap-1 rounded-full px-2.5 py-0.5 text-sm font-medium whitespace-nowrap',
    match ($tone) {
        'brand' => 'bg-brand-tint text-brand-dark',
        'offer' => 'bg-accent-tint text-accent-ink',
        'success' => 'bg-pistachio-tint text-pistachio',
        'info' => 'bg-info-tint text-info',
        'danger' => 'bg-danger-tint text-danger',
        default => 'bg-mist text-ink',
    },
]) }}>
    @if ($icon)
        <x-ui.icon :name="$icon" :size="14" />
    @endif
    {{ $slot }}
</span>
