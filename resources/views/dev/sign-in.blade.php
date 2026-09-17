{{-- Customer sign-in preview. The real Google flow is built in Phase 4 (ADR-004). --}}
<x-layouts::auth title="Sign in" class="flex flex-col gap-5 text-center">
    <div class="flex flex-col gap-1">
        <h1 class="text-2xl font-bold">Sign in to order</h1>
        <p class="text-ink-soft">Use your Google account. We never see your password.</p>
    </div>

    <x-shop.google-button href="#" />

    <p class="text-sm text-ink-soft">Your orders, addresses and delivery updates stay in one place.</p>

    <x-slot:footer>
        Prefer to order by phone? Call
        <a href="tel:{{ preg_replace('/\s+/', '', (string) $shop->phone) }}" class="figures font-medium text-berry">{{ $shop->phone }}</a>
    </x-slot:footer>
</x-layouts::auth>
