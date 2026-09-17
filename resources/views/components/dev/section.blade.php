@props(['id', 'title', 'description' => null])

<section id="{{ $id }}" aria-labelledby="{{ $id }}-title" {{ $attributes->class('flex scroll-mt-20 flex-col gap-4 border-t border-line pt-8 lg:scroll-mt-32') }}>
    <div class="flex flex-col gap-1">
        <h2 id="{{ $id }}-title" class="text-2xl font-bold">{{ $title }}</h2>
        @if ($description)
            <p class="max-w-2xl text-ink-soft">{{ $description }}</p>
        @endif
    </div>
    {{ $slot }}
</section>
