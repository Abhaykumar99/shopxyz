<div class="flex flex-col gap-8">
    <div class="flex flex-col gap-2">
        <x-ui.breadcrumb :items="['Home' => route('shop.home'), 'All categories' => null]" />
        <h1 class="text-3xl font-bold">All categories</h1>
    </div>

    <div class="grid gap-6 lg:grid-cols-3">
        @foreach ($categories as $category)
            <section wire:key="category-{{ $category->slug }}" aria-labelledby="heading-{{ $category->slug }}" class="flex flex-col gap-4 rounded-sheet border border-line bg-surface p-4 sm:p-5">
                <x-shop.category-tile :name="$category->name" :slug="$category->slug" :count="$counts[$category->slug]" :url="route('shop.category', $category->slug)" />
                <h2 id="heading-{{ $category->slug }}" class="sr-only">{{ $category->name }}</h2>
                <p class="text-ink-soft">{{ $category->description }}</p>
                <ul class="flex flex-col divide-y divide-line">
                    @foreach ($category->children as $child)
                        <li wire:key="child-{{ $child->slug }}">
                            <a href="{{ route('shop.category', $child->slug) }}" class="flex min-h-12 items-center justify-between gap-3 py-2 hover:text-brand">
                                <span class="flex flex-col">
                                    <span class="font-medium">{{ $child->name }}</span>
                                    <span class="text-sm text-ink-soft">{{ $child->description }}</span>
                                </span>
                                <span class="flex shrink-0 items-center gap-1 text-sm text-ink-soft">
                                    <span class="figures">{{ $counts[$child->slug] }}</span>
                                    <x-ui.icon name="chevron-right" :size="18" />
                                </span>
                            </a>
                        </li>
                    @endforeach
                </ul>
            </section>
        @endforeach
    </div>
</div>
