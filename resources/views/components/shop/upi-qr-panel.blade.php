{{--
    UPI payment instructions. The real QR (upi://pay?pa=…&am=…&tn=<order>) is generated
    in Phase 6 and passed in as `qr` (trusted SVG markup from the QR library).
--}}
@props([
    'paise',
    'vpa',
    'payee',
    'orderNumber',
    'qr' => null,
])

<div {{ $attributes->class('flex flex-col gap-5 rounded-card border border-line bg-surface p-4 sm:p-5') }}>
    <div class="flex flex-col items-center gap-3 text-center">
        <div class="flex size-52 items-center justify-center rounded-card border-2 border-ink bg-white p-2">
            @if ($qr)
                {{-- Trusted markup: generated server-side by the QR library. --}}
                {!! $qr !!}
            @else
                <x-ui.icon name="qr-code" :size="120" label="UPI QR code for {{ $orderNumber }}" class="text-ink" />
            @endif
        </div>
        <div>
            <p class="text-sm text-ink-soft">Pay exactly</p>
            <x-shop.price :paise="$paise" size="lg" />
        </div>
        <div
            x-data="{ copied: false }"
            class="flex items-center gap-2 rounded-full bg-mist py-1 ps-4 pe-1"
        >
            <span class="text-sm text-ink-soft">UPI ID</span>
            <span class="font-semibold break-all">{{ $vpa }}</span>
            <x-ui.icon-button
                icon="copy"
                label="Copy UPI ID"
                class="size-9"
                x-on:click="navigator.clipboard.writeText(@js($vpa)); copied = true; $dispatch('toast', { message: 'UPI ID copied', tone: 'success' })"
            />
        </div>
        <p class="text-sm text-ink-soft">Paying to {{ $payee }}. Add <span class="figures font-semibold text-ink">{{ $orderNumber }}</span> in the payment note.</p>
    </div>

    <ol class="flex flex-col gap-2 text-base">
        <li class="flex gap-3"><span class="figures flex size-6 shrink-0 items-center justify-center rounded-full bg-berry-tint text-sm font-bold text-berry-dark">1</span>Open any UPI app and scan the QR code.</li>
        <li class="flex gap-3"><span class="figures flex size-6 shrink-0 items-center justify-center rounded-full bg-berry-tint text-sm font-bold text-berry-dark">2</span>Pay the exact amount shown above.</li>
        <li class="flex gap-3"><span class="figures flex size-6 shrink-0 items-center justify-center rounded-full bg-berry-tint text-sm font-bold text-berry-dark">3</span>Upload the payment screenshot and enter the 12-digit UTR number below.</li>
    </ol>

    {{ $slot }}
</div>
