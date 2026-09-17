{{-- Compact, thumb-friendly pagination for any Laravel paginator. --}}
@props(['paginator'])

@if ($paginator->hasPages())
    <nav aria-label="Pagination" {{ $attributes->class('flex items-center justify-between gap-3') }}>
        @if ($paginator->onFirstPage())
            <x-ui.button variant="secondary" icon="chevron-left" disabled>Previous</x-ui.button>
        @else
            <x-ui.button variant="secondary" icon="chevron-left" :href="$paginator->previousPageUrl()" rel="prev">Previous</x-ui.button>
        @endif

        <p class="figures text-sm text-ink-soft">
            @if ($paginator instanceof \Illuminate\Contracts\Pagination\LengthAwarePaginator)
                Page {{ $paginator->currentPage() }} of {{ $paginator->lastPage() }}
            @else
                Page {{ $paginator->currentPage() }}
            @endif
        </p>

        @if ($paginator->hasMorePages())
            <x-ui.button variant="secondary" icon-end="chevron-right" :href="$paginator->nextPageUrl()" rel="next">Next</x-ui.button>
        @else
            <x-ui.button variant="secondary" icon-end="chevron-right" disabled>Next</x-ui.button>
        @endif
    </nav>
@endif
