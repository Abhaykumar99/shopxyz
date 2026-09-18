{{--
    Status with a coloured dot. Tones map to fixed meanings across the shop,
    delivery panel and admin: info = new/confirmed, offer = waiting on someone,
    brand = on the move, success = done/paid, danger = cancelled/failed.
--}}
@props(['tone' => 'neutral'])

<span {{ $attributes->class([
    'inline-flex items-center gap-1.5 rounded-full px-2.5 py-0.5 text-sm font-semibold whitespace-nowrap',
    match ($tone) {
        'info' => 'bg-info-tint text-info',
        'offer' => 'bg-accent-tint text-accent-ink',
        'brand' => 'bg-brand-tint text-brand-dark',
        'success' => 'bg-pistachio-tint text-pistachio',
        'danger' => 'bg-danger-tint text-danger',
        default => 'bg-mist text-ink-soft',
    },
]) }}>
    <span aria-hidden="true" class="size-2 rounded-full bg-current"></span>
    {{ $slot }}
</span>
