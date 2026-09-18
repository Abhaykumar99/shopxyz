@props([
    'orderNumber',
    'url' => '#',
    'customer',
    'area',
    'pincode',
    'items',
    'codPaise' => null,
    'collected' => false,
    'status',
    'tone' => 'info',
    'boxes' => 0,
    'boxesPickedUp' => 0,
])

<article {{ $attributes->class('relative flex flex-col gap-3 rounded-card border border-line bg-surface p-4') }}>
    <div class="flex items-start justify-between gap-3">
        <div class="min-w-0">
            <h3 class="figures font-display text-lg font-bold">
                <a href="{{ $url }}" class="after:absolute after:inset-0">{{ $orderNumber }}</a>
            </h3>
            <p class="text-ink">{{ $customer }}</p>
        </div>
        <x-ui.status-pill :tone="$tone">{{ $status }}</x-ui.status-pill>
    </div>

    <p class="flex items-start gap-2 text-ink-soft">
        <x-ui.icon name="map-pin" :size="18" class="mt-0.5" />
        <span>{{ $area }}, <span class="figures">{{ $pincode }}</span></span>
    </p>

    <div class="flex items-center justify-between gap-3 border-t border-line pt-3">
        <span class="figures flex items-center gap-2 text-sm text-ink-soft">
            {{ $items }} {{ \Illuminate\Support\Str::plural('item', $items) }}
            @if ($boxes > 0)
                @php($boxLabel = ($boxesPickedUp > 0 && $boxesPickedUp < $boxes ? $boxesPickedUp.'/'.$boxes : $boxes).' '.\Illuminate\Support\Str::plural('box', $boxes))
                <span class="flex items-center gap-1"><x-ui.icon name="boxes" :size="16" /> {{ $boxLabel }}</span>
            @endif
        </span>
        @if ($codPaise)
            <span class="flex items-center gap-1.5 font-semibold text-ink">
                <x-ui.icon name="banknote" :size="18" class="text-accent-ink" />
                {{ $collected ? 'Collected' : 'Collect' }} <x-shop.price :paise="$codPaise" size="sm" />
            </span>
        @else
            <x-ui.badge tone="success" icon="check">Paid by UPI</x-ui.badge>
        @endif
    </div>
</article>
