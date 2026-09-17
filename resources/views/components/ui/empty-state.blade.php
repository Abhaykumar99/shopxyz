@props([
    'icon' => 'package',
    'title',
])

<div {{ $attributes->class('flex flex-col items-center gap-3 px-4 py-10 text-center') }}>
    <span class="flex size-14 items-center justify-center rounded-full bg-berry-tint text-berry">
        <x-ui.icon :name="$icon" :size="28" />
    </span>
    <h3 class="text-xl font-semibold text-ink">{{ $title }}</h3>
    @if ($slot->isNotEmpty())
        <p class="max-w-sm text-ink-soft">{{ $slot }}</p>
    @endif
    @isset($action)
        <div class="mt-2">{{ $action }}</div>
    @endisset
</div>
