{{--
    Toolbar, filters, product grid and pagination for a catalogue page that uses
    the FiltersCatalog trait. Slot `empty` replaces the default empty state.
--}}
@props([
    'products',
    'brands' => [],
    'priceRanges' => [],
    'sorts' => [],
    'sort' => 'popular',
    'activeBrands' => [],
    'price' => '',
    'inStock' => false,
    'filterCount' => 0,
])

<div {{ $attributes->class('grid gap-6 lg:grid-cols-[15rem_1fr] lg:gap-8') }}>
    <aside class="hidden lg:block" aria-label="Filters">
        <div class="sticky top-32 flex flex-col gap-4">
            <div class="flex items-center justify-between">
                <h2 class="text-lg font-bold">Filters</h2>
                @if ($filterCount)
                    <button type="button" wire:click="clearFilters" class="text-sm font-semibold text-brand hover:underline">Clear all</button>
                @endif
            </div>
            <x-shop.catalog-filters :brands="$brands" :price-ranges="$priceRanges" id-prefix="side" />
        </div>
    </aside>

    <div class="flex min-w-0 flex-col gap-4">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <p class="figures text-ink-soft" aria-live="polite">
                {{ $products->total() }} {{ \Illuminate\Support\Str::plural('product', $products->total()) }}
            </p>
            <div class="flex items-center gap-2">
                <x-ui.button
                    variant="secondary"
                    size="sm"
                    icon="funnel"
                    class="lg:hidden"
                    x-data
                    x-on:click="$dispatch('open-modal', 'filters')"
                >Filter{{ $filterCount ? " ({$filterCount})" : '' }}</x-ui.button>
                <label for="sort" class="sr-only">Sort by</label>
                <div class="relative">
                    <select id="sort" wire:model.live="sort" class="h-9 appearance-none rounded-field border border-line-strong bg-surface ps-3 pe-9 text-sm font-medium">
                        @foreach ($sorts as $value => $label)
                            <option value="{{ $value }}">{{ $label }}</option>
                        @endforeach
                    </select>
                    <x-ui.icon name="chevron-down" :size="16" class="pointer-events-none absolute end-3 top-1/2 -translate-y-1/2 text-ink-soft" />
                </div>
            </div>
        </div>

        @if ($filterCount)
            <ul class="flex flex-wrap gap-2" aria-label="Active filters">
                @if ($inStock)
                    <li><button type="button" wire:click="$set('inStock', false)" aria-label="Remove filter: in stock" class="inline-flex min-h-9 items-center gap-1.5 rounded-full bg-brand-tint px-3 text-sm font-medium text-brand-dark">In stock <x-ui.icon name="x" :size="14" /></button></li>
                @endif
                @if ($price !== '' && isset($priceRanges[$price]))
                    <li><button type="button" wire:click="$set('price', '')" aria-label="Remove filter: {{ $priceRanges[$price][0] }}" class="inline-flex min-h-9 items-center gap-1.5 rounded-full bg-brand-tint px-3 text-sm font-medium text-brand-dark">{{ $priceRanges[$price][0] }} <x-ui.icon name="x" :size="14" /></button></li>
                @endif
                @foreach ($activeBrands as $brand)
                    <li wire:key="chip-{{ \Illuminate\Support\Str::slug($brand) }}"><button type="button" wire:click="removeBrand(@js($brand))" aria-label="Remove filter: {{ $brand }}" class="inline-flex min-h-9 items-center gap-1.5 rounded-full bg-brand-tint px-3 text-sm font-medium text-brand-dark">{{ $brand }} <x-ui.icon name="x" :size="14" /></button></li>
                @endforeach
                <li><button type="button" wire:click="clearFilters" class="inline-flex min-h-9 items-center px-2 text-sm font-semibold text-brand hover:underline">Clear all</button></li>
            </ul>
        @endif

        <div wire:loading.class="opacity-60" wire:target="sort, brands, price, inStock, clearFilters, removeBrand, nextPage, previousPage" class="transition-opacity">
            @if ($products->isEmpty())
                @isset($empty)
                    {{ $empty }}
                @else
                    <x-ui.card>
                        <x-ui.empty-state icon="funnel" title="No products match these filters">
                            Try removing a filter or choosing a different price range.
                            <x-slot:action>
                                <x-ui.button variant="secondary" wire:click="clearFilters">Clear filters</x-ui.button>
                            </x-slot:action>
                        </x-ui.empty-state>
                    </x-ui.card>
                @endisset
            @else
                <x-shop.product-grid :products="$products" columns="lg:grid-cols-3 xl:grid-cols-4" />
            @endif
        </div>

        <x-ui.pagination :paginator="$products" livewire class="mt-2" />
    </div>

    <x-ui.modal name="filters" title="Filter" sheet>
        <x-shop.catalog-filters :brands="$brands" :price-ranges="$priceRanges" id-prefix="sheet" />
        <x-slot:footer>
            <x-ui.button variant="secondary" wire:click="clearFilters">Clear all</x-ui.button>
            <x-ui.button x-on:click="$dispatch('close-modal', 'filters')">
                Show {{ $products->total() }} {{ \Illuminate\Support\Str::plural('product', $products->total()) }}
            </x-ui.button>
        </x-slot:footer>
    </x-ui.modal>
</div>
