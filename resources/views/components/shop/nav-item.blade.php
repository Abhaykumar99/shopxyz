{{-- One item of the phone bottom navigation. --}}
@props([
    'href',
    'icon',
    'active' => false,
    'count' => 0,
])

<a
    href="{{ $href }}"
    @if ($active) aria-current="page" @endif
    {{ $attributes->class([
        'relative flex h-16 flex-col items-center justify-center gap-0.5 text-xs font-medium',
        'text-brand' => $active,
        'text-ink-soft' => ! $active,
    ]) }}
>
    @if ($active)
        <span aria-hidden="true" class="absolute top-0 h-0.5 w-8 rounded-full bg-brand"></span>
    @endif
    <span class="relative">
        <x-ui.icon :name="$icon" :size="22" />
        @if ($count)
            <span aria-hidden="true" class="figures absolute -top-1.5 -right-2.5 inline-flex h-4 min-w-4 items-center justify-center rounded-full bg-brand px-1 text-[0.6875rem] font-semibold text-white">{{ $count > 99 ? '99+' : $count }}</span>
        @endif
    </span>
    <span>
        {{ $slot }}
        @if ($count)
            <span class="sr-only">({{ $count }} {{ \Illuminate\Support\Str::plural('item', $count) }})</span>
        @endif
    </span>
</a>
