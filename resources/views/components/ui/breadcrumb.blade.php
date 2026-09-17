{{-- $items: ['Home' => url, 'Cosmetics' => url, 'Current page' => null] --}}
@props(['items' => []])

<nav aria-label="Breadcrumb" {{ $attributes->class('text-sm') }}>
    <ol class="flex flex-wrap items-center gap-1 text-ink-soft">
        @foreach ($items as $label => $url)
            <li class="flex items-center gap-1">
                @if (! $loop->first)
                    <x-ui.icon name="chevron-right" :size="14" />
                @endif
                @if ($url && ! $loop->last)
                    <a href="{{ $url }}" class="hover:text-ink hover:underline">{{ $label }}</a>
                @else
                    <span aria-current="page" class="text-ink">{{ $label }}</span>
                @endif
            </li>
        @endforeach
    </ol>
</nav>
