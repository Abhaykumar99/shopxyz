<div class="flex flex-col gap-6">
    <div class="flex flex-col gap-3">
        <x-ui.breadcrumb :items="$breadcrumb" />
        <div class="flex flex-col gap-1">
            <h1 class="text-3xl font-bold">{{ $category->name }}</h1>
            <p class="text-ink-soft">{{ $category->description }}</p>
        </div>

        @if ($sections !== [])
            <nav aria-label="{{ $root->name }} sections" class="-mx-4 overflow-x-auto px-4 [scrollbar-width:none]">
                <ul class="flex min-w-max gap-2">
                    <li>
                        <a href="{{ route('shop.category', $root->slug) }}" @if ($category->slug === $root->slug) aria-current="page" @endif @class([
                            'inline-flex min-h-10 items-center rounded-full border px-4 text-sm font-medium',
                            'border-brand bg-brand text-white' => $category->slug === $root->slug,
                            'border-line bg-surface hover:border-brand hover:text-brand' => $category->slug !== $root->slug,
                        ])>All {{ \Illuminate\Support\Str::lower($root->name) }}</a>
                    </li>
                    @foreach ($sections as $section)
                        <li wire:key="section-{{ $section->slug }}">
                            <a href="{{ route('shop.category', $section->slug) }}" @if ($category->slug === $section->slug) aria-current="page" @endif @class([
                                'inline-flex min-h-10 items-center rounded-full border px-4 text-sm font-medium',
                                'border-brand bg-brand text-white' => $category->slug === $section->slug,
                                'border-line bg-surface hover:border-brand hover:text-brand' => $category->slug !== $section->slug,
                            ])>{{ $section->name }}</a>
                        </li>
                    @endforeach
                </ul>
            </nav>
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
    />
</div>
