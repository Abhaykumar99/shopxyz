{{-- Customer sign-in: Google only (ADR-004). --}}
<x-layouts::auth title="Sign in" class="flex flex-col gap-6 text-center">
    <div class="flex flex-col gap-2">
        <h1 class="text-2xl font-bold">
            {{ $returningToCheckout ? 'Sign in to place your order' : 'Sign in to your account' }}
        </h1>
        <p class="text-ink-soft">
            Use your Google account. It's quick, and we never see your password.
        </p>
    </div>

    @if ($googleUrl)
        <x-shop.google-button :href="$googleUrl" />
    @else
        <x-shop.google-button href="#" aria-disabled="true" />
        <p class="text-sm text-ink-soft">Sign-in opens soon.</p>
    @endif

    @if ($developerUrl)
        <div class="flex flex-col gap-2 rounded-field bg-accent-tint px-3 py-2 text-sm text-accent-ink">
            <span>Developer shortcut: this machine has no Google client configured.</span>
            <a href="{{ $developerUrl }}" class="font-semibold underline">Continue as a sample customer</a>
        </div>
    @endif

    <ul class="flex flex-col gap-2 text-start text-sm text-ink-soft">
        <li class="flex items-start gap-2"><x-ui.icon name="package" :size="18" class="mt-0.5 text-brand" /> Track every order and share the delivery code</li>
        <li class="flex items-start gap-2"><x-ui.icon name="map-pin" :size="18" class="mt-0.5 text-brand" /> Save your delivery addresses</li>
        <li class="flex items-start gap-2"><x-ui.icon name="shopping-bag" :size="18" class="mt-0.5 text-brand" /> Your bag stays with you</li>
    </ul>

    <p class="text-xs text-ink-soft">
        By continuing you agree to our
        <a href="{{ route('pages.show', 'terms') }}" class="underline hover:text-brand">terms of use</a>
        and
        <a href="{{ route('pages.show', 'privacy') }}" class="underline hover:text-brand">privacy policy</a>.
    </p>

    <x-slot:footer>
        <span class="flex flex-col gap-2">
            <a href="{{ route('shop.home') }}" class="font-medium text-brand hover:underline">Back to the shop</a>
            @if ($shop->phone)
                <span>Prefer to order by phone? Call <a href="tel:{{ preg_replace('/\s+/', '', $shop->phone) }}" class="figures font-medium text-brand">{{ $shop->phone }}</a></span>
            @endif
        </span>
    </x-slot:footer>
</x-layouts::auth>
