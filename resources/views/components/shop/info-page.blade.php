{{-- Wrapper for shop information pages, with a side menu of all pages. --}}
@props([
    'title',
    'pages' => [],
    'current' => null,
    'placeholder' => true,
])

<x-layouts::shop :title="$title" class="grid gap-8 lg:grid-cols-[14rem_1fr]">
    <nav aria-label="Help and policies" class="order-last lg:order-first">
        <ul class="flex flex-col divide-y divide-line rounded-card border border-line bg-surface lg:sticky lg:top-32">
            @foreach ($pages as $slug => $label)
                <li>
                    <a
                        href="{{ route('pages.show', $slug) }}"
                        @if ($current === $slug) aria-current="page" @endif
                        @class([
                            'flex min-h-11 items-center justify-between gap-2 px-4 py-2',
                            'font-semibold text-brand' => $current === $slug,
                            'text-ink-soft hover:text-ink' => $current !== $slug,
                        ])
                    >
                        {{ $label }}
                        <x-ui.icon name="chevron-right" :size="16" />
                    </a>
                </li>
            @endforeach
        </ul>
    </nav>

    <article class="flex max-w-prose flex-col gap-5">
        <x-ui.breadcrumb :items="['Home' => route('shop.home'), $title => null]" />
        <h1 class="text-3xl font-bold">{{ $title }}</h1>
        @if ($placeholder)
            <x-ui.alert tone="warning">Sample wording. {{ $shop->name }} will replace this text before launch.</x-ui.alert>
        @endif
        <div class="flex flex-col gap-4 leading-relaxed [&_h2]:mt-2 [&_h2]:text-xl [&_h2]:font-bold [&_li]:ms-5 [&_ul]:flex [&_ul]:list-disc [&_ul]:flex-col [&_ul]:gap-1.5">
            {{ $slot }}
        </div>
    </article>
</x-layouts::shop>
