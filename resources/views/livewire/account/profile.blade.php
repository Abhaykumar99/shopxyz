@use('App\Support\IndianPhone')

<div class="grid items-start gap-5 lg:grid-cols-[1fr_20rem]">
    <div class="flex flex-col gap-5">
        <div class="flex items-center gap-4">
            <span aria-hidden="true" class="flex size-16 shrink-0 items-center justify-center rounded-full bg-brand font-display text-2xl font-bold text-white">{{ $customer->initials() }}</span>
            <div class="min-w-0">
                <h1 class="truncate text-2xl font-bold sm:text-3xl">Hello, {{ $customer->firstName() }}</h1>
                <p class="truncate text-ink-soft">{{ $profile['email'] }}</p>
            </div>
        </div>

        @unless ($customer->hasPhone())
            <x-ui.alert tone="warning" title="Add your mobile number">
                We need it before your first order so the delivery partner can reach you.
            </x-ui.alert>
        @endunless

        <x-ui.card padding="lg" class="flex flex-col gap-4">
            <h2 class="text-xl font-bold">Your details</h2>
            <dl class="flex flex-col divide-y divide-line">
                <div class="flex flex-col gap-1 py-3 sm:flex-row sm:gap-4">
                    <dt class="w-40 shrink-0 text-ink-soft">Name</dt>
                    <dd class="font-medium">{{ $profile['name'] }}</dd>
                </div>
                <div class="flex flex-col gap-1 py-3 sm:flex-row sm:gap-4">
                    <dt class="w-40 shrink-0 text-ink-soft">Email</dt>
                    <dd class="min-w-0 font-medium break-all">{{ $profile['email'] }}</dd>
                </div>
                <div class="flex flex-col gap-2 py-3 sm:flex-row sm:gap-4">
                    <dt class="w-40 shrink-0 text-ink-soft sm:pt-2">Mobile number</dt>
                    <dd class="grow">
                        @if ($editingPhone)
                            <form wire:submit="savePhone" class="flex flex-col gap-3" novalidate>
                                <x-ui.input
                                    label="Mobile number"
                                    name="phoneForm.phone"
                                    wire:model="phoneForm.phone"
                                    type="tel"
                                    prefix="+91"
                                    inputmode="numeric"
                                    autocomplete="tel-national"
                                    maxlength="14"
                                    hint="Used only for delivery updates and calls about your order."
                                    required
                                    class="[&_label]:sr-only"
                                />
                                <div class="flex gap-2">
                                    <x-ui.button type="submit" loading="savePhone">Save number</x-ui.button>
                                    @if ($customer->hasPhone())
                                        <x-ui.button variant="ghost" wire:click="cancelPhoneEdit">Cancel</x-ui.button>
                                    @endif
                                </div>
                            </form>
                        @else
                            <div class="flex flex-wrap items-center justify-between gap-2">
                                <span class="figures font-medium sm:pt-2">{{ IndianPhone::format($profile['phone']) }}</span>
                                <x-ui.button variant="ghost" size="sm" icon="pencil" wire:click="$set('editingPhone', true)">Change</x-ui.button>
                            </div>
                        @endif
                    </dd>
                </div>
            </dl>
            <p class="text-sm text-ink-soft">Your name and email come from your Google account.</p>
        </x-ui.card>
    </div>

    <div class="flex flex-col gap-3">
        <a href="{{ route('account.orders') }}" class="flex items-center gap-3 rounded-card border border-line bg-surface p-4 hover:border-brand">
            <span class="flex size-10 items-center justify-center rounded-full bg-brand-tint text-brand"><x-ui.icon name="package" /></span>
            <span class="grow">
                <span class="block font-semibold">Your orders</span>
                <span class="text-sm text-ink-soft">{{ $activeOrders ? $activeOrders.' in progress' : 'Nothing in progress' }}</span>
            </span>
            <x-ui.icon name="chevron-right" class="text-ink-soft" />
        </a>
        <a href="{{ route('account.addresses') }}" class="flex items-center gap-3 rounded-card border border-line bg-surface p-4 hover:border-brand">
            <span class="flex size-10 items-center justify-center rounded-full bg-brand-tint text-brand"><x-ui.icon name="map-pin" /></span>
            <span class="grow">
                <span class="block font-semibold">Saved addresses</span>
                <span class="text-sm text-ink-soft">{{ $addressCount }} {{ \Illuminate\Support\Str::plural('address', $addressCount) }}</span>
            </span>
            <x-ui.icon name="chevron-right" class="text-ink-soft" />
        </a>
        @if ($shop->whatsappLink())
            <a href="{{ $shop->whatsappLink() }}" target="_blank" rel="noopener" class="flex items-center gap-3 rounded-card border border-line bg-surface p-4 hover:border-brand">
                <span class="flex size-10 items-center justify-center rounded-full bg-brand-tint text-brand"><x-ui.icon name="message-circle" /></span>
                <span class="grow">
                    <span class="block font-semibold">Need help?</span>
                    <span class="text-sm text-ink-soft">Chat with us on WhatsApp</span>
                </span>
                <x-ui.icon name="chevron-right" class="text-ink-soft" />
            </a>
        @endif
        <form method="post" action="{{ route('auth.logout') }}">
            @csrf
            <x-ui.button type="submit" variant="secondary" icon="log-out" block>Sign out</x-ui.button>
        </form>
    </div>
</div>
