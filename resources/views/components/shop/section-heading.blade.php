@props([
    'title',
    'href' => null,
    'linkText' => 'See all',
    'id' => null,
])

<div {{ $attributes->class('flex items-end justify-between gap-3') }}>
    <div class="flex min-w-0 flex-col gap-1">
        <h2 @if ($id) id="{{ $id }}" @endif class="text-2xl font-bold">{{ $title }}</h2>
        @if ($slot->isNotEmpty())
            <p class="text-ink-soft">{{ $slot }}</p>
        @endif
    </div>
    @if ($href)
        <a href="{{ $href }}" class="inline-flex shrink-0 items-center gap-1 font-semibold text-brand hover:underline">
            {{ $linkText }}<span class="sr-only"> {{ \Illuminate\Support\Str::lower($title) }}</span>
            <x-ui.icon name="chevron-right" :size="18" />
        </a>
    @endif
</div>
