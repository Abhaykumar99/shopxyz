@use('App\Support\Money')

<div class="flex flex-col gap-12 lg:gap-16">
    {{-- Hero, phones and tablets: unchanged single banner --}}
    <section aria-labelledby="hero-title" class="relative -mx-4 overflow-hidden bg-brand px-4 py-8 text-white sm:mx-0 sm:rounded-sheet sm:px-8 lg:hidden">
        <div class="relative grid items-center gap-8 lg:grid-cols-[1.15fr_1fr]">
            <div class="flex flex-col gap-5">
                <p class="text-white/80">{{ $shop->deliveryArea ? 'Delivered across '.$shop->deliveryArea.' by our own team' : 'Delivered by our own team' }}</p>
                <h1 id="hero-title" class="text-[2.5rem] leading-[1.05] font-bold tracking-tight sm:text-5xl lg:text-6xl">
                    Sweets, beauty and gifts, at your door today.
                </h1>
                <p class="max-w-md text-lg text-white/85">
                    Fresh mithai from our kitchen, make-up you already love, and hampers wrapped by hand.
                    Pay in cash on delivery or by UPI.
                </p>

                <form action="{{ route('shop.search') }}" method="get" role="search" class="flex max-w-md items-center gap-2 rounded-full bg-white p-1.5 ps-4 text-ink focus-within:outline-2 focus-within:outline-offset-2 focus-within:outline-white">
                    <label for="hero-search" class="sr-only">Search products</label>
                    <x-ui.icon name="search" class="text-ink-soft" />
                    <input id="hero-search" type="search" name="q" placeholder="Try kaju katli or lipstick" class="min-w-0 grow bg-transparent focus:outline-none" autocomplete="off">
                    <x-ui.button type="submit" size="sm" class="rounded-full">Search</x-ui.button>
                </form>

                <ul class="flex flex-wrap gap-2" aria-label="Shop by category">
                    @foreach ($categories as $category)
                        <li wire:key="hero-{{ $category->slug }}">
                            <a href="{{ route('shop.category', $category->slug) }}" class="tag-shape inline-flex h-10 items-center bg-white pe-4 font-display font-bold text-brand transition-colors hover:bg-brand-tint">
                                {{ $category->name }}
                            </a>
                        </li>
                    @endforeach
                </ul>
            </div>

            {{-- Shop window: three signature picks --}}
            <div class="hidden grid-cols-2 gap-3 sm:grid lg:gap-4" aria-hidden="true">
                @foreach ($festive->take(3) as $pick)
                    <div @class(['overflow-hidden rounded-card bg-white/95 text-ink', 'row-span-2' => $loop->first])>
                        <x-shop.product-image :category="$pick->category" :alt="$pick->name" :class="$loop->first ? 'h-[calc(100%-4.5rem)] w-full' : 'aspect-[4/3] w-full'" />
                        <div class="flex h-[4.5rem] flex-col justify-center px-3">
                            <p class="truncate text-sm font-medium">{{ $pick->name }}</p>
                            <p class="figures font-display font-bold">{{ Money::format($pick->lowestPrice()) }}</p>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </section>

    {{-- Hero, desktop: auto-sliding banners --}}
    <x-shop.hero-carousel class="hidden lg:block" :labels="['Sweets, beauty and gifts', 'Festive hampers', 'Beauty offers', 'Wholesale']">
        <x-slot:slide1>
            <div class="grid min-h-[27rem] grid-cols-[1.2fr_1fr] items-center gap-10 bg-brand px-12 pt-10 pb-20 text-white">
                <div class="flex flex-col gap-5">
                    <p class="text-white/80">{{ $shop->deliveryArea ? 'Delivered across '.$shop->deliveryArea.' by our own team' : 'Delivered by our own team' }}</p>
                    <h1 class="text-5xl leading-[1.04] tracking-tight xl:text-6xl">Sweets, beauty and gifts, at your door today.</h1>
                    <p class="max-w-md text-lg text-white/85">Fresh mithai from our kitchen, make-up you already love, and hampers wrapped by hand. Pay in cash on delivery or by UPI.</p>
                    <form action="{{ route('shop.search') }}" method="get" role="search" class="flex max-w-md items-center gap-2 rounded-full bg-white p-1.5 ps-4 text-ink focus-within:outline-2 focus-within:outline-offset-2 focus-within:outline-white">
                        <label for="hero-search-desktop" class="sr-only">Search products</label>
                        <x-ui.icon name="search" class="text-ink-soft" />
                        <input id="hero-search-desktop" type="search" name="q" placeholder="Try kaju katli or lipstick" class="min-w-0 grow bg-transparent focus:outline-none" autocomplete="off">
                        <x-ui.button type="submit" size="sm" class="rounded-full">Search</x-ui.button>
                    </form>
                    <ul class="flex flex-wrap gap-2" aria-label="Shop by category">
                        @foreach ($categories as $category)
                            <li><a href="{{ route('shop.category', $category->slug) }}" class="tag-shape inline-flex h-10 items-center bg-white pe-4 font-display text-brand transition-colors hover:bg-brand-tint">{{ $category->name }}</a></li>
                        @endforeach
                    </ul>
                </div>
                <x-shop.hero-showcase :products="$festive" />
            </div>
        </x-slot:slide1>

        <x-slot:slide2>
            <div class="grid min-h-[27rem] grid-cols-[1.2fr_1fr] items-center gap-10 bg-brand-dark px-12 pt-10 pb-20 text-white">
                <div class="flex flex-col gap-5">
                    <span class="tag-shape inline-flex h-8 items-center self-start bg-accent pe-3 text-sm font-semibold text-ink">Festive gifting</span>
                    <h2 class="text-5xl leading-[1.04] tracking-tight xl:text-6xl">Hampers wrapped by hand, ready to give.</h2>
                    <p class="max-w-md text-lg text-white/85">Dry fruits, mithai, brass diyas and candles in keepsake boxes, with a handwritten card.</p>
                    <div class="flex flex-wrap gap-2">
                        <x-ui.button :href="route('shop.category', 'gifts')" size="lg" variant="inverse" icon-end="chevron-right">Shop gifts</x-ui.button>
                        <x-ui.button :href="route('shop.category', 'hampers')" size="lg" variant="inverse-outline">See hampers</x-ui.button>
                    </div>
                </div>
                <x-shop.hero-showcase :products="$slideHampers" />
            </div>
        </x-slot:slide2>

        <x-slot:slide3>
            <div class="grid min-h-[27rem] grid-cols-[1.2fr_1fr] items-center gap-10 bg-accent-tint px-12 pt-10 pb-20 text-ink">
                <div class="flex flex-col gap-5">
                    <span class="tag-shape inline-flex h-8 items-center self-start bg-accent pe-3 text-sm font-semibold text-ink">Up to {{ $slideBeauty->max(fn ($product) => $product->bestDiscount()) }}% off</span>
                    <h2 class="text-5xl leading-[1.04] tracking-tight xl:text-6xl">Beauty favourites, gently priced.</h2>
                    <p class="max-w-md text-lg text-ink-soft">Lipsticks, kajal, serums and compacts from brands we trust, at our best prices this week.</p>
                    <div class="flex flex-wrap gap-2">
                        <x-ui.button :href="route('shop.category', ['category' => 'cosmetics', 'sort' => 'discount'])" size="lg" icon-end="chevron-right">Shop beauty offers</x-ui.button>
                        <x-ui.button :href="route('shop.category', 'lips')" size="lg" variant="secondary">Shop lips</x-ui.button>
                    </div>
                </div>
                <x-shop.hero-showcase :products="$slideBeauty" />
            </div>
        </x-slot:slide3>

        <x-slot:slide4>
            <div class="grid min-h-[27rem] grid-cols-[1.2fr_1fr] items-center gap-10 bg-ink px-12 pt-10 pb-20 text-white">
                <div class="flex flex-col gap-5">
                    <span class="tag-shape inline-flex h-8 items-center self-start bg-accent pe-3 text-sm font-semibold text-ink">Wholesale</span>
                    <h2 class="text-5xl leading-[1.04] tracking-tight xl:text-6xl">Buying in bulk? Talk to us.</h2>
                    <p class="max-w-md text-lg text-white/80">Price slabs for shops, weddings, hotels and corporate gifting, with custom packing and delivery.</p>
                    <div class="flex flex-wrap gap-2">
                        <x-ui.button :href="route('wholesale.index')" size="lg" variant="inverse" icon-end="chevron-right">See wholesale prices</x-ui.button>
                        <x-ui.button :href="route('wholesale.index').'#enquiry'" size="lg" variant="inverse-outline">Request a quote</x-ui.button>
                    </div>
                </div>
                @if ($slideWholesale)
                    <x-shop.wholesale-slabs :item="$slideWholesale" class="shadow-overlay" />
                @endif
            </div>
        </x-slot:slide4>
    </x-shop.hero-carousel>

    {{-- Promises --}}
    <section aria-label="Why order from us" class="-mt-4 grid gap-3 sm:grid-cols-3 lg:mt-0">
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

    <section aria-labelledby="festive-title" class="flex flex-col gap-4">
        <x-shop.section-heading id="festive-title" title="Festive gifting" :href="route('shop.category', 'gifts')" link-text="All gifts">
            Hampers, diyas and sweets that arrive ready to give.
        </x-shop.section-heading>
        <x-shop.product-grid :products="$festive" rail />
    </section>

    <section aria-labelledby="categories-title" class="flex flex-col gap-4">
        <x-shop.section-heading id="categories-title" title="Shop by category" :href="route('shop.categories')" link-text="All categories" />
        <div class="grid gap-3 md:grid-cols-3">
            @foreach ($categories as $category)
                <div wire:key="cat-{{ $category->slug }}" class="flex flex-col gap-3">
                    <x-shop.category-tile :name="$category->name" :slug="$category->slug" :count="$counts[$category->slug]" :url="route('shop.category', $category->slug)" />
                    <ul class="flex flex-wrap gap-2" aria-label="{{ $category->name }} sections">
                        @foreach ($category->children as $child)
                            <li wire:key="sub-{{ $child->slug }}">
                                <a href="{{ route('shop.category', $child->slug) }}" class="inline-flex min-h-9 items-center rounded-full border border-line bg-surface px-3 text-sm font-medium hover:border-brand hover:text-brand">{{ $child->name }}</a>
                            </li>
                        @endforeach
                    </ul>
                </div>
            @endforeach
        </div>
    </section>

    @if ($offers->isNotEmpty())
        <section aria-labelledby="offers-title" class="flex flex-col gap-4">
            <x-shop.section-heading id="offers-title" title="Today's offers" :href="route('shop.search', ['sort' => 'discount'])" link-text="More offers">
                Savings of 15% or more, while stocks last.
            </x-shop.section-heading>
            <x-shop.product-grid :products="$offers" rail />
        </section>
    @endif

    <section aria-labelledby="bestsellers-title" class="flex flex-col gap-4">
        <x-shop.section-heading id="bestsellers-title" title="Bestsellers" :href="route('shop.search')" link-text="Shop all">
            The picks our customers keep coming back for.
        </x-shop.section-heading>
        <x-shop.product-grid :products="$bestsellers" rail />
    </section>

    {{-- Wholesale, phones and tablets (desktop shows it in the banner) --}}
    <section aria-labelledby="wholesale-promo-title" class="flex flex-col gap-4 rounded-sheet bg-ink p-6 text-white lg:hidden">
        <span class="tag-shape inline-flex h-8 items-center self-start bg-accent pe-3 text-sm font-semibold text-ink">Wholesale</span>
        <h2 id="wholesale-promo-title" class="text-3xl">Buying in bulk? Talk to us.</h2>
        <p class="text-white/80">Price slabs for shops, weddings, hotels and corporate gifting.</p>
        <x-ui.button :href="route('wholesale.index')" variant="inverse" icon-end="chevron-right" class="self-start">See wholesale prices</x-ui.button>
    </section>

    {{-- How ordering works: a real sequence --}}
    <section aria-labelledby="how-title" class="grid gap-6 rounded-sheet bg-mist p-6 lg:grid-cols-[1fr_2fr] lg:p-10">
        <div class="flex flex-col gap-3">
            <h2 id="how-title" class="text-2xl font-bold">How ordering works</h2>
            <p class="text-ink-soft">The same shop you know, now open online. Prefer to talk? We're a message away.</p>
            @if ($shop->whatsappLink())
                <x-ui.button variant="secondary" icon="message-circle" :href="$shop->whatsappLink()" target="_blank" rel="noopener" class="self-start">Chat on WhatsApp</x-ui.button>
            @endif
        </div>
        <ol class="grid gap-4 sm:grid-cols-3">
            @foreach ([
                ['Fill your bag', 'Browse sweets, beauty and gifts, then sign in with Google at checkout.'],
                ['Choose how to pay', 'Cash on delivery, or UPI with a quick screenshot so we can confirm it.'],
                ['We pack and deliver', 'Track your order and share the delivery code when it arrives.'],
            ] as $index => [$title, $text])
                <li class="flex flex-col gap-2 rounded-card bg-surface p-4">
                    <span class="figures flex size-8 items-center justify-center rounded-full bg-brand font-display font-bold text-white">{{ $index + 1 }}</span>
                    <p class="font-semibold">{{ $title }}</p>
                    <p class="text-sm text-ink-soft">{{ $text }}</p>
                </li>
            @endforeach
        </ol>
    </section>
</div>
