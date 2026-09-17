{{--
    Customer-facing layout: sticky header with search and bag, category bar on
    desktop, bottom navigation on phones.
    `active`: home | categories | cart | orders | account
    Links use url() until the Phase 2 routes exist; switch to route() then.
--}}
@props([
    'title' => null,
    'description' => null,
    'active' => null,
    'cartCount' => 0,
    'search' => '',
    'noindex' => false,
])

@php
    $categories = [
        'Cosmetics' => url('/c/cosmetics'),
        'Confectionery' => url('/c/confectionery'),
        'Gifts' => url('/c/gifts'),
    ];
    $bottomNav = [
        'home' => ['Home', 'house', url('/')],
        'categories' => ['Shop', 'layout-grid', url('/categories')],
        'cart' => ['Bag', 'shopping-bag', url('/cart')],
        'orders' => ['Orders', 'package', url('/account/orders')],
        'account' => ['Account', 'user', url('/account')],
    ];
@endphp

<x-layouts::app :title="$title" :description="$description" :noindex="$noindex" class="pb-safe-nav lg:pb-0">
    <header class="sticky top-0 z-30 border-b border-line bg-paper">
        <div class="mx-auto flex h-16 max-w-6xl items-center gap-3 px-4">
            <x-shop.logo :href="url('/')" class="me-auto lg:me-0" />

            <form action="{{ url('/search') }}" method="get" role="search" class="hidden grow lg:mx-8 lg:block">
                <label for="header-search" class="sr-only">Search products</label>
                <div class="flex h-11 items-center gap-2 rounded-full border border-line-strong bg-surface ps-4 pe-1 focus-within:outline-2 focus-within:outline-offset-2 focus-within:outline-brand">
                    <x-ui.icon name="search" class="text-ink-soft" />
                    <input id="header-search" type="search" name="q" value="{{ $search }}" placeholder="Search lipsticks, chocolates, gift hampers" class="min-w-0 grow bg-transparent focus:outline-none">
                    <x-ui.button type="submit" size="sm" class="rounded-full">Search</x-ui.button>
                </div>
            </form>

            <div class="flex shrink-0 items-center gap-1">
                <span class="lg:hidden"><x-ui.icon-button icon="search" label="Search" :href="url('/search')" /></span>
                <span class="hidden lg:block"><x-ui.icon-button icon="user" label="Your account" :href="url('/account')" /></span>
                <x-ui.icon-button icon="shopping-bag" label="Your bag" :href="url('/cart')" :count="$cartCount" />
            </div>
        </div>

        <nav aria-label="Categories" class="hidden border-t border-line lg:block">
            <ul class="mx-auto flex max-w-6xl gap-6 px-4">
                @foreach ($categories as $label => $href)
                    <li>
                        <a href="{{ $href }}" class="flex h-11 items-center font-medium text-ink-soft hover:text-brand">{{ $label }}</a>
                    </li>
                @endforeach
            </ul>
        </nav>
    </header>

    <main id="main" {{ $attributes->class('mx-auto w-full max-w-6xl px-4 py-5 lg:py-8') }}>
        {{ $slot }}
    </main>

    <footer class="mt-8 border-t border-line bg-surface">
        <div class="mx-auto grid max-w-6xl gap-6 px-4 py-8 sm:grid-cols-2 lg:grid-cols-3">
            <div class="flex flex-col gap-2">
                <x-shop.logo />
                @if ($shop->tagline)
                    <p class="text-ink-soft">{{ $shop->tagline }}</p>
                @endif
            </div>
            <div class="flex flex-col gap-1.5 text-ink-soft">
                <h2 class="font-sans text-base font-semibold text-ink">Visit or call us</h2>
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
            <nav aria-label="Footer" class="flex flex-col gap-1.5">
                <h2 class="font-sans text-base font-semibold text-ink">Shop</h2>
                @foreach ($categories as $label => $href)
                    <a href="{{ $href }}" class="text-ink-soft hover:text-brand">{{ $label }}</a>
                @endforeach
                <a href="{{ url('/account/orders') }}" class="text-ink-soft hover:text-brand">Track an order</a>
            </nav>
        </div>
        <p class="border-t border-line py-4 text-center text-sm text-ink-soft">© {{ now()->year }} {{ $shop->name }}</p>
    </footer>

    <nav aria-label="Main" class="pb-safe fixed inset-x-0 bottom-0 z-30 border-t border-line bg-surface lg:hidden">
        <ul class="grid grid-cols-5">
            @foreach ($bottomNav as $key => [$label, $icon, $href])
                <li>
                    <a
                        href="{{ $href }}"
                        @if ($active === $key) aria-current="page" @endif
                        @class([
                            'relative flex h-16 flex-col items-center justify-center gap-0.5 text-xs font-medium',
                            'text-brand' => $active === $key,
                            'text-ink-soft' => $active !== $key,
                        ])
                    >
                        @if ($active === $key)
                            <span aria-hidden="true" class="absolute top-0 h-0.5 w-8 rounded-full bg-brand"></span>
                        @endif
                        <span class="relative">
                            <x-ui.icon :name="$icon" :size="22" />
                            @if ($key === 'cart' && $cartCount)
                                <span class="figures absolute -top-1.5 -right-2.5 inline-flex h-4 min-w-4 items-center justify-center rounded-full bg-brand px-1 text-[0.6875rem] font-semibold text-white">{{ $cartCount }}</span>
                            @endif
                        </span>
                        {{ $label }}
                    </a>
                </li>
            @endforeach
        </ul>
    </nav>
</x-layouts::app>
