<div class="flex flex-col gap-6">
    <div class="flex flex-col gap-4">
        <h1 class="text-3xl font-bold">
            @if ($query !== '')
                Results for “{{ $query }}”
            @else
                Search the shop
            @endif
        </h1>

        <form wire:submit="$refresh" role="search" class="max-w-xl">
            <label for="search-q" class="sr-only">Search products</label>
            <div class="flex h-12 items-center gap-2 rounded-full border border-line-strong bg-surface ps-4 pe-1 focus-within:outline-2 focus-within:outline-offset-2 focus-within:outline-brand">
                <x-ui.icon name="search" class="text-ink-soft" />
                <input
                    id="search-q"
                    type="search"
                    wire:model.live.debounce.400ms="q"
                    placeholder="Search lipsticks, mithai, gift hampers"
                    maxlength="80"
                    autocomplete="off"
                    @if ($query === '') autofocus @endif
                    class="min-w-0 grow bg-transparent text-base focus:outline-none"
                >
                <x-ui.icon name="loader-circle" class="animate-spin text-ink-soft" wire:loading wire:target="q" />
            </div>
        </form>

        @if ($query === '')
            <div class="flex flex-col gap-2">
                <p class="text-sm font-semibold">Popular searches</p>
                <ul class="flex flex-wrap gap-2">
                    @foreach ($suggestions as $suggestion)
                        <li wire:key="suggest-{{ $loop->index }}">
                            <button type="button" wire:click="$set('q', @js($suggestion))" class="inline-flex min-h-9 items-center rounded-full border border-line bg-surface px-3 text-sm font-medium hover:border-brand hover:text-brand">{{ $suggestion }}</button>
                        </li>
                    @endforeach
                </ul>
            </div>
        @endif
    </div>

    <x-shop.catalog-results
        :products="$products"
        :brands="$brandOptions"
        :price-ranges="$priceRanges"
        :sorts="$sorts"
        :sort="$sort"
        :active-brands="$brands"
        :price="$price"
        :in-stock="$inStock"
        :filter-count="$filterCount"
    >
        <x-slot:empty>
            <x-ui.card>
                <x-ui.empty-state icon="search" :title="$filterCount ? 'No products match these filters' : 'Nothing found for “'.$query.'”'">
                    @if ($filterCount)
                        Try removing a filter.
                    @else
                        Check the spelling, try a shorter word, or browse a category.
                    @endif
                    <x-slot:action>
                        <div class="flex flex-wrap justify-center gap-2">
                            @if ($filterCount)
                                <x-ui.button variant="secondary" wire:click="clearFilters">Clear filters</x-ui.button>
                            @endif
                            @foreach ($categories as $category)
                                <x-ui.button variant="secondary" :href="route('shop.category', $category->slug)">{{ $category->name }}</x-ui.button>
                            @endforeach
                        </div>
                    </x-slot:action>
                </x-ui.empty-state>
            </x-ui.card>
        </x-slot:empty>
    </x-shop.catalog-results>
</div>
