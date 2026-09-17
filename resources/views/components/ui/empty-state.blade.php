@props([
    'icon' => 'package',
    'title',
    'level' => 2,
])

<div {{ $attributes->class('flex flex-col items-center gap-3 px-4 py-10 text-center') }}>
    <span class="flex size-14 items-center justify-center rounded-full bg-brand-tint text-brand">
        <x-ui.icon :name="$icon" :size="28" />
    </span>
    <h{{ $level }} class="text-xl font-semibold text-ink">{{ $title }}</h{{ $level }}>
    @if ($slot->isNotEmpty())
        <p class="max-w-sm text-ink-soft">{{ $slot }}</p>
    @endif
    @isset($action)
        <div class="mt-2">{{ $action }}</div>
    @endisset
</div>
