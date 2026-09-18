@use('App\Support\IndianPhone')
@use('App\Support\Money')

<div class="flex flex-col gap-3">
    <x-ui.card padding="lg" class="flex items-center gap-4">
        <span class="flex size-14 shrink-0 items-center justify-center rounded-full bg-brand-tint font-display text-xl text-brand">
            {{ \Illuminate\Support\Str::substr($profile['name'], 0, 1) }}
        </span>
        <div class="min-w-0">
            <h2 class="text-xl font-semibold">{{ $profile['name'] }}</h2>
            <p class="figures text-ink-soft">{{ IndianPhone::format($profile['phone']) }}</p>
        </div>
    </x-ui.card>

    <x-ui.card>
        <x-slot:header>
            <h2 class="text-lg font-semibold">Today</h2>
        </x-slot:header>
        <dl class="figures flex flex-col divide-y divide-line">
            <div class="flex items-center justify-between gap-3 py-2">
                <dt class="text-ink-soft">Still to deliver</dt>
                <dd class="font-semibold">{{ $summary['to_deliver'] }}</dd>
            </div>
            <div class="flex items-center justify-between gap-3 py-2">
                <dt class="text-ink-soft">Delivered</dt>
                <dd class="font-semibold">{{ $summary['delivered'] }}</dd>
            </div>
            <div class="flex items-center justify-between gap-3 py-2">
                <dt class="text-ink-soft">Could not deliver</dt>
                <dd class="font-semibold">{{ $summary['failed'] }}</dd>
            </div>
            <div class="flex items-center justify-between gap-3 py-2">
                <dt class="text-ink-soft">Cash with you</dt>
                <dd class="font-semibold">{{ Money::format($cashWithYou) }}</dd>
            </div>
        </dl>
    </x-ui.card>

    <x-ui.card>
        <x-slot:header>
            <h2 class="text-lg font-semibold">Your details</h2>
        </x-slot:header>
        <dl class="flex flex-col divide-y divide-line">
            <div class="flex items-baseline justify-between gap-3 py-2">
                <dt class="text-ink-soft">Delivery area</dt>
                <dd class="text-end font-medium">{{ $profile['area'] }}</dd>
            </div>
            <div class="flex items-baseline justify-between gap-3 py-2">
                <dt class="text-ink-soft">Vehicle</dt>
                <dd class="figures text-end font-medium">{{ $profile['vehicle'] }}</dd>
            </div>
            <div class="flex items-baseline justify-between gap-3 py-2">
                <dt class="text-ink-soft">With the shop since</dt>
                <dd class="text-end font-medium">{{ $profile['since'] }}</dd>
            </div>
        </dl>
        <p class="mt-3 text-sm text-ink-soft">
            The shop keeps these details. Ask them to change anything that is wrong.
        </p>
    </x-ui.card>

    @if ($shop->phone)
        <x-ui.button variant="secondary" size="lg" block icon="phone" :href="'tel:'.preg_replace('/\s+/', '', $shop->phone)">
            Call {{ $shop->name }}
        </x-ui.button>
    @endif

    <x-ui.button variant="ghost" size="lg" block icon="log-out" wire:click="signOut" loading="signOut">Sign out</x-ui.button>
</div>
