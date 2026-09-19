@use('App\Enums\HomeSectionType')

{{--
    Every banner and block on this page is a row the admin manages (ADR-024):
    order, dates and wording all come from the database.
--}}
<div class="flex flex-col gap-12 lg:gap-16">
    {{-- Hero, phones and tablets --}}
    @if ($mobileHero)
        <x-shop.banner-panel
            :banner="$mobileHero"
            layout="hero"
            heading="h1"
            heading-id="hero-title"
            class="-mx-4 sm:mx-0 sm:rounded-sheet lg:hidden"
        >
            <form action="{{ route('shop.search') }}" method="get" role="search" class="flex max-w-md items-center gap-2 rounded-full bg-white p-1.5 ps-4 text-ink focus-within:outline-2 focus-within:outline-offset-2 focus-within:outline-white">
                <label for="hero-search" class="sr-only">Search products</label>
                <x-ui.icon name="search" class="text-ink-soft" />
                <input id="hero-search" type="search" name="q" placeholder="Try kaju katli or lipstick" class="min-w-0 grow bg-transparent focus:outline-none" autocomplete="off">
                <x-ui.button type="submit" size="sm" class="rounded-full">Search</x-ui.button>
            </form>

            <ul class="flex flex-wrap gap-2" aria-label="Shop by category">
                @foreach ($categories as $category)
                    <li wire:key="hero-{{ $category->slug }}">
                        <a href="{{ route('shop.category', $category->slug) }}" wire:navigate class="tag-shape inline-flex h-10 items-center bg-white pe-4 font-display font-bold text-brand transition-colors hover:bg-brand-tint">
                            {{ $category->name }}
                        </a>
                    </li>
                @endforeach
            </ul>
        </x-shop.banner-panel>
    @endif

    {{-- Hero, desktop: the admin's slides, auto-advancing --}}
    <x-shop.hero-carousel :banners="$heroSlides" class="hidden lg:block" />

    @foreach ($blocks as $block)
        @php($section = $block['section'])

        @switch ($section->type)
            @case (HomeSectionType::Promise)
                <section wire:key="block-{{ $section->id }}" aria-label="{{ $section->title ?? 'Why order from us' }}" class="-mt-4 grid gap-3 sm:grid-cols-3 lg:mt-0">
                    @foreach ([
                        ['truck', $shop->deliveryEta ?? 'Fast local delivery', 'Our own delivery team, never a courier.'],
                        ['banknote', 'Cash on delivery or UPI', 'No card needed. Pay the way you prefer.'],
                        ['gift', 'Gift-ready packing', 'Hampers and boxes wrapped by hand.'],
                    ] as [$icon, $title, $text])
                        <div class="flex items-start gap-3 rounded-card border border-line bg-surface p-4">
                            <span class="flex size-10 shrink-0 items-center justify-center rounded-full bg-brand-tint text-brand"><x-ui.icon :name="$icon" /></span>
                            <div>
                                <p class="font-semibold">{{ $title }}</p>
                                <p class="text-sm text-ink-soft">{{ $text }}</p>
                            </div>
                        </div>
                    @endforeach
                </section>
                @break

            @case (HomeSectionType::ProductRail)
                <section wire:key="block-{{ $section->id }}" aria-labelledby="section-{{ $section->id }}" class="flex flex-col gap-4">
                    <x-shop.section-heading
                        :id="'section-'.$section->id"
                        :title="$section->title ?? ''"
                        :href="$section->link_url"
                        :link-text="$section->link_label"
                    >{{ $section->subtitle }}</x-shop.section-heading>
                    <x-shop.product-grid :products="$block['products']" :rail="$section->showsAsRail()" />
                </section>
                @break

            @case (HomeSectionType::CategoryGrid)
                <section wire:key="block-{{ $section->id }}" aria-labelledby="section-{{ $section->id }}" class="flex flex-col gap-4">
                    <x-shop.section-heading
                        :id="'section-'.$section->id"
                        :title="$section->title ?? 'Shop by category'"
                        :href="$section->link_url"
                        :link-text="$section->link_label"
                    >{{ $section->subtitle }}</x-shop.section-heading>
                    <div class="grid gap-3 md:grid-cols-3">
                        @foreach ($categories as $category)
                            <div wire:key="cat-{{ $category->slug }}" class="flex flex-col gap-3">
                                <x-shop.category-tile :name="$category->name" :slug="$category->slug" :count="$counts[$category->slug]" :url="route('shop.category', $category->slug)" />
                                <ul class="flex flex-wrap gap-2" aria-label="{{ $category->name }} sections">
                                    @foreach ($category->children as $child)
                                        <li wire:key="sub-{{ $child->slug }}">
                                            <a href="{{ route('shop.category', $child->slug) }}" wire:navigate class="inline-flex min-h-9 items-center rounded-full border border-line bg-surface px-3 text-sm font-medium hover:border-brand hover:text-brand">{{ $child->name }}</a>
                                        </li>
                                    @endforeach
                                </ul>
                            </div>
                        @endforeach
                    </div>
                </section>
                @break

            @case (HomeSectionType::Promo)
                @foreach ($promos as $promo)
                    <x-shop.banner-panel
                        wire:key="promo-{{ $promo->id }}"
                        :banner="$promo"
                        layout="promo"
                        :heading-id="'promo-'.$promo->id"
                    />
                @endforeach
                @break

            @case (HomeSectionType::HowItWorks)
                <section wire:key="block-{{ $section->id }}" aria-labelledby="section-{{ $section->id }}" class="grid gap-6 rounded-sheet bg-mist p-6 lg:grid-cols-[1fr_2fr] lg:p-10">
                    <div class="flex flex-col gap-3">
                        <h2 id="section-{{ $section->id }}" class="text-2xl font-bold">{{ $section->title ?? 'How ordering works' }}</h2>
                        @if ($section->subtitle)
                            <p class="text-ink-soft">{{ $section->subtitle }}</p>
                        @endif
                        @if ($shop->whatsappLink())
                            <x-ui.button variant="secondary" icon="message-circle" :href="$shop->whatsappLink()" target="_blank" rel="noopener" class="self-start">Chat on WhatsApp</x-ui.button>
                        @endif
                    </div>
                    <ol class="grid gap-4 sm:grid-cols-3">
                        @foreach ([
                            ['Fill your bag', 'Browse sweets, beauty and gifts, then sign in with Google at checkout.'],
                            ['Choose how to pay', 'Cash on delivery, or UPI with a quick screenshot so we can confirm it.'],
                            ['We pack and deliver', 'Track your order and share the delivery OTP when it arrives.'],
                        ] as $index => [$title, $text])
                            <li class="flex flex-col gap-2 rounded-card bg-surface p-4">
                                <span class="figures flex size-8 items-center justify-center rounded-full bg-brand font-display font-bold text-white">{{ $index + 1 }}</span>
                                <p class="font-semibold">{{ $title }}</p>
                                <p class="text-sm text-ink-soft">{{ $text }}</p>
                            </li>
                        @endforeach
                    </ol>
                </section>
                @break
        @endswitch
    @endforeach
</div>
