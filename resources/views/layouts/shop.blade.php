{{--
    Customer-facing layout: delivery strip, sticky header with search and bag,
    desktop category bar, phone bottom navigation and footer.
    `active`: home | categories | cart | orders | account | wholesale
--}}
@props([
    'title' => null,
    'description' => null,
    'active' => null,
    'search' => '',
    'noindex' => false,
    'ogType' => 'website',
])

@use('App\Http\Controllers\InfoPageController')
@use('App\Support\Money')

@php
    $categories = [
        'Cosmetics' => route('shop.category', 'cosmetics'),
        'Confectionery' => route('shop.category', 'confectionery'),
        'Gifts' => route('shop.category', 'gifts'),
    ];
    $navItems = [
        'home' => ['Home', 'house', route('shop.home')],
        'categories' => ['Shop', 'layout-grid', route('shop.categories')],
        'orders' => ['Orders', 'package', route('account.orders')],
        'account' => ['Account', 'user', route('account.profile')],
    ];
@endphp

<x-layouts::app :title="$title" :description="$description" :noindex="$noindex" :og-type="$ogType" class="pb-safe-nav lg:pb-0">
    <section aria-label="Shop notices">
        @unless (app()->isProduction())
            <p class="bg-accent-tint px-4 py-1.5 text-center text-sm text-accent-ink">
                Preview with sample products and orders. Nothing here is real yet.
            </p>
        @endunless

        @if ($shop->freeDeliveryAbovePaise > 0)
            <p class="bg-brand px-4 py-1.5 text-center text-sm text-white">
                Free delivery{{ $shop->deliveryArea ? ' in '.$shop->deliveryArea : '' }} on orders above {{ Money::format($shop->freeDeliveryAbovePaise) }}
            </p>
        @endif
    </section>

    <header class="sticky top-0 z-30 border-b border-line bg-paper">
        <div class="mx-auto flex h-16 max-w-6xl items-center gap-2 px-4 sm:gap-3">
            <x-shop.logo :href="route('shop.home')" class="me-auto min-w-0 lg:me-0" />

            <form action="{{ route('shop.search') }}" method="get" role="search" class="hidden grow lg:mx-8 lg:block">
                <label for="header-search" class="sr-only">Search products</label>
                <div class="flex h-11 items-center gap-2 rounded-full border border-line-strong bg-surface ps-4 pe-1 focus-within:outline-2 focus-within:outline-offset-2 focus-within:outline-brand">
                    <x-ui.icon name="search" class="text-ink-soft" />
                    <input id="header-search" type="search" name="q" value="{{ $search }}" placeholder="Search lipsticks, mithai, gift hampers" class="min-w-0 grow bg-transparent focus:outline-none" autocomplete="off">
                    <x-ui.button type="submit" size="sm" class="rounded-full">Search</x-ui.button>
                </div>
            </form>

            <div class="flex shrink-0 items-center gap-1">
                <span class="lg:hidden"><x-ui.icon-button icon="search" label="Search" :href="route('shop.search')" /></span>
                <span class="hidden lg:block"><x-ui.icon-button icon="package" label="Your orders" :href="route('account.orders')" /></span>
                <span class="hidden lg:block"><x-ui.icon-button icon="user" label="Your account" :href="route('account.profile')" /></span>
                <livewire:cart.cart-count />
            </div>
        </div>

        <nav aria-label="Categories" class="border-t border-line">
            <ul class="mx-auto flex max-w-6xl items-center gap-3.5 overflow-x-auto px-4 no-scrollbar sm:gap-5 lg:gap-6">
                @foreach ($categories as $label => $href)
                    <li class="shrink-0">
                        <a href="{{ $href }}" @if (request()->url() === $href) aria-current="page" @endif @class([
                            'flex h-11 items-center border-b-2 text-sm font-medium whitespace-nowrap transition-colors sm:text-base',
                            'border-brand text-brand' => request()->url() === $href,
                            'border-transparent text-ink-soft hover:text-brand' => request()->url() !== $href,
                        ])>{{ $label }}</a>
                    </li>
                @endforeach
                <li class="shrink-0">
                    <a href="{{ route('wholesale.index') }}" @if ($active === 'wholesale') aria-current="page" @endif @class([
                        'my-1.5 flex h-8 items-center gap-1.5 rounded-full px-2.5 text-sm font-semibold whitespace-nowrap transition-colors sm:px-3',
                        'bg-brand text-white' => $active === 'wholesale',
                        'bg-brand-tint text-brand-dark hover:bg-brand hover:text-white' => $active !== 'wholesale',
                    ])>
                        <x-ui.icon name="store" :size="16" />
                        Wholesale
                    </a>
                </li>
                <li class="ms-auto hidden shrink-0 lg:block">
                    <a href="{{ route('shop.categories') }}" class="flex h-11 items-center font-medium text-ink-soft hover:text-brand">All categories</a>
                </li>
            </ul>
        </nav>
    </header>

    <main id="main" {{ $attributes->class('mx-auto w-full max-w-6xl px-4 py-5 lg:py-8') }}>
        {{ $slot }}
    </main>

    <footer class="mt-12 border-t border-line bg-surface">
        <div class="mx-auto grid max-w-6xl gap-8 px-4 py-10 sm:grid-cols-2 lg:grid-cols-4">
            <div class="flex flex-col gap-3">
                <x-shop.logo />
                @if ($shop->tagline)
                    <p class="text-ink-soft">{{ $shop->tagline }}</p>
                @endif
            </div>
            <div class="flex flex-col gap-1.5 text-ink-soft">
                <h2 class="mb-1 font-sans text-base font-semibold text-ink">Visit or call us</h2>
                @if ($shop->address)
                    <p>{{ $shop->address }}</p>
                @endif
                @if ($shop->hours)
                    <p>{{ $shop->hours }}</p>
                @endif
                @if ($shop->phone)
                    <a href="tel:{{ preg_replace('/\s+/', '', $shop->phone) }}" class="figures hover:text-brand">{{ $shop->phone }}</a>
                @endif
                @if ($shop->whatsappLink())
                    <a href="{{ $shop->whatsappLink() }}" class="inline-flex items-center gap-1.5 hover:text-brand" rel="noopener" target="_blank">
                        <x-ui.icon name="message-circle" :size="18" />
                        Chat on WhatsApp
                    </a>
                @endif
            </div>
            <nav aria-label="Shop" class="flex flex-col gap-1.5">
                <h2 class="mb-1 font-sans text-base font-semibold text-ink">Shop</h2>
                @foreach ($categories as $label => $href)
                    <a href="{{ $href }}" class="text-ink-soft hover:text-brand">{{ $label }}</a>
                @endforeach
                <a href="{{ route('wholesale.index') }}" class="text-ink-soft hover:text-brand">Wholesale and bulk orders</a>
                <a href="{{ route('account.orders') }}" class="text-ink-soft hover:text-brand">Track an order</a>
            </nav>
            <nav aria-label="Help and policies" class="flex flex-col gap-1.5">
                <h2 class="mb-1 font-sans text-base font-semibold text-ink">Help</h2>
                @foreach (InfoPageController::PAGES as $slug => $label)
                    <a href="{{ route('pages.show', $slug) }}" class="text-ink-soft hover:text-brand">{{ $label }}</a>
                @endforeach
            </nav>
        </div>
        <div class="border-t border-line">
            <div class="mx-auto flex max-w-6xl flex-col items-center justify-between gap-2 px-4 py-4 text-sm text-ink-soft sm:flex-row">
                <p>© {{ now()->year }} {{ $shop->name }}</p>
                <p>Cash on delivery and UPI accepted</p>
            </div>
        </div>
    </footer>

    <nav aria-label="Main" class="pb-safe fixed inset-x-0 bottom-0 z-30 border-t border-line bg-surface lg:hidden">
        <ul class="grid grid-cols-5">
            @foreach (array_slice($navItems, 0, 2, true) as $key => [$label, $icon, $href])
                <li><x-shop.nav-item :href="$href" :icon="$icon" :active="$active === $key">{{ $label }}</x-shop.nav-item></li>
            @endforeach
            <li><livewire:cart.cart-count variant="nav" :active="$active === 'cart'" /></li>
            @foreach (array_slice($navItems, 2, null, true) as $key => [$label, $icon, $href])
                <li><x-shop.nav-item :href="$href" :icon="$icon" :active="$active === $key">{{ $label }}</x-shop.nav-item></li>
            @endforeach
        </ul>
    </nav>
</x-layouts::app>
