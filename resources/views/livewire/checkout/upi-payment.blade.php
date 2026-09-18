@use('App\Support\Money')

<div class="mx-auto flex w-full max-w-xl flex-col gap-5">
    <div class="flex flex-col gap-2 text-center">
        <p class="text-ink-soft">Order <span class="figures font-semibold text-ink">{{ $order->number }}</span> is saved</p>
        <h1 class="text-3xl font-bold">Pay {{ Money::format($order->total()) }} by UPI</h1>
    </div>

    @if ($order->rejectionReason)
        <x-ui.alert tone="danger" title="We couldn't match your last payment">{{ $order->rejectionReason }}</x-ui.alert>
    @endif

    <form wire:submit="submit" novalidate>
        <x-shop.upi-qr-panel :paise="$order->total()" :vpa="$shop->upiVpa" :payee="$shop->upiPayeeName ?? $shop->name" :order-number="$order->number">
            @if ($upiLink)
                <x-ui.button :href="$upiLink" variant="secondary" icon="qr-code" block class="sm:hidden">Pay with a UPI app on this phone</x-ui.button>
            @endif

            <div class="flex flex-col gap-4 border-t border-line pt-5">
                <h2 class="text-lg font-bold">After paying</h2>

                <x-shop.file-drop label="Payment screenshot" name="proof.screenshot" wire:model="proof.screenshot" />

                <div wire:loading.flex wire:target="proof.screenshot" class="items-center gap-2 text-sm text-ink-soft">
                    <x-ui.icon name="loader-circle" :size="16" class="animate-spin" /> Uploading screenshot…
                </div>

                @if ($proof->screenshot && ! $errors->has('proof.screenshot') && method_exists($proof->screenshot, 'isPreviewable') && $proof->screenshot->isPreviewable())
                    <div class="flex items-center gap-3 rounded-field bg-mist p-2">
                        <img src="{{ $proof->screenshot->temporaryUrl() }}" alt="Selected payment screenshot" class="size-16 rounded-field object-cover">
                        <p class="text-sm">Screenshot ready to send.</p>
                    </div>
                @endif

                <x-ui.input
                    label="UTR number"
                    name="proof.utr"
                    wire:model="proof.utr"
                    inputmode="numeric"
                    autocomplete="off"
                    maxlength="14"
                    placeholder="12 digits"
                    input-class="figures tracking-wider"
                    hint="Find it in your UPI app under the transaction details. It is sometimes called UPI Ref No."
                    required
                />

                <x-ui.button type="submit" size="lg" block icon="upload" loading="submit">Send payment details</x-ui.button>
                <p class="text-center text-sm text-ink-soft">
                    Paid a different amount or stuck? <x-ui.link :href="route('account.order', $order->number)">View order</x-ui.link>
                    @if ($shop->whatsappLink())
                        or <x-ui.link :href="$shop->whatsappLink().'?text='.rawurlencode('Hi, I need help paying for order '.$order->number)" target="_blank" rel="noopener">message us on WhatsApp</x-ui.link>.
                    @endif
                </p>
            </div>
        </x-shop.upi-qr-panel>
    </form>
</div>
