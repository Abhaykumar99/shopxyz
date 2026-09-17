<x-shop.info-page :title="$title" :pages="$pages" :current="$current" :placeholder="false">
    <p>We're a local shop, and we're happy to help with orders, gifting ideas and delivery questions.</p>

    <div class="grid gap-3 sm:grid-cols-2">
        @if ($shop->phone)
            <x-ui.card class="flex flex-col gap-2">
                <h2 class="text-lg font-bold">Call us</h2>
                <a href="tel:{{ preg_replace('/\s+/', '', $shop->phone) }}" class="figures text-lg font-semibold text-brand hover:underline">{{ $shop->phone }}</a>
                @if ($shop->hours)
                    <p class="text-sm text-ink-soft">{{ $shop->hours }}</p>
                @endif
            </x-ui.card>
        @endif
        @if ($shop->whatsappLink())
            <x-ui.card class="flex flex-col gap-2">
                <h2 class="text-lg font-bold">WhatsApp</h2>
                <p class="text-sm text-ink-soft">Send a message any time. We reply during shop hours.</p>
                <x-ui.button variant="secondary" icon="message-circle" :href="$shop->whatsappLink()" target="_blank" rel="noopener" class="self-start">Chat with us</x-ui.button>
            </x-ui.card>
        @endif
        @if ($shop->email)
            <x-ui.card class="flex flex-col gap-2">
                <h2 class="text-lg font-bold">Email</h2>
                <a href="mailto:{{ $shop->email }}" class="font-semibold break-all text-brand hover:underline">{{ $shop->email }}</a>
            </x-ui.card>
        @endif
        @if ($shop->address)
            <x-ui.card class="flex flex-col gap-2">
                <h2 class="text-lg font-bold">Visit the shop</h2>
                <address class="not-italic">{{ $shop->address }}</address>
                <x-ui.link :href="'https://www.google.com/maps/search/?api=1&query='.rawurlencode($shop->address)" target="_blank" rel="noopener">Open in Google Maps</x-ui.link>
            </x-ui.card>
        @endif
    </div>

    @if ($shop->servedPincodes)
        <h2>Where we deliver</h2>
        <p>We currently deliver to these pincodes{{ $shop->deliveryArea ? ' in '.$shop->deliveryArea : '' }}:</p>
        <p class="figures flex flex-wrap gap-2">
            @foreach ($shop->servedPincodes as $pincode)
                <span class="rounded-full bg-mist px-3 py-1 text-sm">{{ $pincode }}</span>
            @endforeach
        </p>
    @endif
</x-shop.info-page>
