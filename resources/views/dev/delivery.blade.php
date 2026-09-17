{{-- Delivery panel preview (dummy data). Real screens are built in Phase 3. --}}
@use('App\Support\Money')

@php($customer = $order['customer'])

<x-layouts::delivery :title="$order['number']" :back="route('dev.ui.index').'#screens'">
    <x-ui.card class="flex flex-col gap-3">
        <div class="flex items-center justify-between gap-2">
            <x-ui.status-pill tone="berry">Out for delivery</x-ui.status-pill>
            <span class="figures text-sm text-ink-soft">Assigned 11:52 am</span>
        </div>
        <div>
            <p class="text-xl font-semibold">{{ $customer['name'] }}</p>
            <address class="mt-1 text-lg not-italic">
                @foreach ($customer['lines'] as $line)
                    {{ $line }}<br>
                @endforeach
                <span class="figures font-semibold">{{ $customer['pincode'] }}</span>
            </address>
        </div>
        <div class="grid grid-cols-2 gap-2">
            <x-ui.button variant="secondary" icon="phone" :href="'tel:'.preg_replace('/\s+/', '', $customer['phone'])">Call</x-ui.button>
            <x-ui.button variant="secondary" icon="navigation" href="#">Directions</x-ui.button>
        </div>
        @if ($order['note'])
            <x-ui.alert tone="warning" title="Customer note">{{ $order['note'] }}</x-ui.alert>
        @endif
    </x-ui.card>

    <x-ui.card class="flex items-center justify-between gap-3 bg-marigold-tint">
        <span class="flex items-center gap-2 font-semibold">
            <x-ui.icon name="banknote" :size="24" class="text-marigold-ink" />
            Collect in cash
        </span>
        <x-shop.price :paise="$order['total']" size="lg" />
    </x-ui.card>

    <x-ui.card>
        <x-slot:header>
            <h2 class="text-lg font-semibold">Items</h2>
            <span class="figures text-sm text-ink-soft">{{ collect($order['items'])->sum('quantity') }} in total</span>
        </x-slot:header>
        <ul class="flex flex-col divide-y divide-line">
            @foreach ($order['items'] as $item)
                <li class="flex gap-3 py-2">
                    <span class="figures w-8 shrink-0 font-semibold">{{ $item['quantity'] }}×</span>
                    <span>{{ $item['name'] }} <span class="text-ink-soft">({{ $item['variant'] }})</span></span>
                </li>
            @endforeach
        </ul>
    </x-ui.card>

    <x-ui.card class="flex flex-col gap-3">
        <h2 class="text-lg font-semibold">Customer's delivery code</h2>
        <p class="text-ink-soft">Ask the customer for the 6-digit code shown in their order page.</p>
        <x-ui.input label="Delivery code" name="demo_otp" inputmode="numeric" autocomplete="one-time-code" maxlength="6" required class="[&_input]:figures [&_input]:text-center [&_input]:text-2xl [&_input]:tracking-[0.4em]" />
        <x-ui.input label="Cash collected" name="demo_cash" inputmode="decimal" prefix="₹" required :value="$order['total'] / 100" />
    </x-ui.card>

    <h2 class="mt-2 text-lg font-semibold">Other deliveries today</h2>
    @foreach ($deliveries as $delivery)
        <x-shop.delivery-order-card
            :order-number="$delivery['number']"
            :customer="$delivery['customer']"
            :area="$delivery['area']"
            :pincode="$delivery['pincode']"
            :items="$delivery['items']"
            :cod-paise="$delivery['cod']"
            :status="$delivery['status']"
            :tone="$delivery['tone']"
        />
    @endforeach

    <x-slot:action>
        <x-ui.button size="lg" block icon="check">Confirm delivery</x-ui.button>
        <x-ui.button variant="ghost" block>Couldn't deliver</x-ui.button>
    </x-slot:action>
</x-layouts::delivery>
