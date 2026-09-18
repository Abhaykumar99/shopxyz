@use('App\Support\Money')

<div class="flex flex-col gap-3">
    {{-- 1. Cash collected, still with the delivery boy --}}
    <x-ui.card padding="lg" class="flex flex-col gap-2 bg-accent-tint">
        <h2 class="text-lg font-semibold">Cash with you</h2>
        <p class="figures font-display text-4xl font-bold">{{ Money::format($withYouTotal) }}</p>
        <p class="text-ink-soft">
            From {{ count($withYou) }} {{ \Illuminate\Support\Str::plural('delivery', count($withYou)) }} today.
            Hand it in at the shop counter at the end of your round.
        </p>
        @if ($withYou !== [])
            <x-ui.button size="lg" icon="banknote" class="mt-1 self-start" x-on:click="$dispatch('open-modal', 'hand-over')">
                Hand over {{ Money::format($withYouTotal) }}
            </x-ui.button>
        @endif
    </x-ui.card>

    @if ($withYou !== [])
        <x-ui.card padding="none" class="divide-y divide-line px-4">
            @foreach ($withYou as $line)
                <div wire:key="withyou-{{ $line['number'] }}" class="flex items-center justify-between gap-3 py-3">
                    <div class="min-w-0">
                        <a href="{{ route('delivery.order', $line['number']) }}" wire:navigate class="figures font-semibold hover:underline">{{ $line['number'] }}</a>
                        <p class="truncate text-sm text-ink-soft">{{ $line['customer'] }} · {{ $line['time'] }}</p>
                    </div>
                    <x-shop.price :paise="$line['paise']" size="sm" />
                </div>
            @endforeach
        </x-ui.card>
    @endif

    {{-- 2. Handed over, waiting for the shop to count it --}}
    <section aria-labelledby="awaiting-title" class="flex flex-col gap-2">
        <div class="flex items-baseline justify-between gap-3 px-1">
            <h2 id="awaiting-title" class="mt-1 text-lg font-semibold">With the shop</h2>
            <span class="figures text-sm text-ink-soft">{{ Money::format($awaitingTotal) }} being checked</span>
        </div>

        @if ($awaiting === [])
            <x-ui.card class="text-ink-soft">
                Nothing is waiting to be counted. Cash you hand over appears here until the shop confirms it.
            </x-ui.card>
        @else
            @foreach ($awaiting as $batch)
                <x-ui.card wire:key="awaiting-{{ $batch->reference }}" class="flex flex-col gap-3">
                    <div class="flex items-start justify-between gap-3">
                        <div class="min-w-0">
                            <p class="figures font-semibold">{{ $batch->reference }}</p>
                            <p class="text-sm text-ink-soft">
                                {{ $batch->orderCount() }} {{ \Illuminate\Support\Str::plural('order', $batch->orderCount()) }} ·
                                handed over {{ $batch->handedOverOn() }}, {{ $batch->handedOverTime() }}
                            </p>
                        </div>
                        <x-shop.price :paise="$batch->amountPaise" />
                    </div>
                    <x-ui.status-pill :tone="$batch->status->tone()" class="self-start">{{ $batch->status->label() }}</x-ui.status-pill>
                    <ul class="figures flex flex-col gap-1 text-sm text-ink-soft">
                        @foreach ($batch->collections as $collection)
                            <li class="flex justify-between gap-3">
                                <span>{{ $collection['number'] }}</span>
                                <span>{{ Money::format($collection['paise']) }}</span>
                            </li>
                        @endforeach
                    </ul>
                    @if ($canPreviewVerification)
                        <x-ui.button variant="ghost" size="sm" class="self-start" wire:click="markVerified('{{ $batch->reference }}')" loading="markVerified('{{ $batch->reference }}')">
                            Preview: shop counts it and settles
                        </x-ui.button>
                    @endif
                </x-ui.card>
            @endforeach
        @endif
    </section>

    {{-- 3. Settled --}}
    <x-ui.card class="flex items-center justify-between gap-3">
        <span class="flex items-center gap-2 font-semibold">
            <x-ui.icon name="badge-check" :size="22" class="text-pistachio" />
            Settled today
        </span>
        <x-shop.price :paise="$settledToday" size="sm" />
    </x-ui.card>

    <x-ui.button variant="secondary" size="lg" block icon="clock" :href="route('delivery.cash.history')" wire:navigate>
        Cash history
    </x-ui.button>

    <x-ui.alert tone="info" title="How the cash trail works">
        Collect at the door → hand the whole round in at the shop → the shop counts it and marks it settled.
        Anything still under "Cash with you" is money you are carrying.
    </x-ui.alert>

    {{-- Handover confirmation --}}
    <x-ui.modal name="hand-over" title="Hand over cash" sheet>
        <div class="flex flex-col gap-3">
            <p class="text-ink-soft">Count the cash with the shop before you confirm. You are handing over:</p>
            <p class="figures font-display text-3xl font-bold">{{ Money::format($withYouTotal) }}</p>
            <ul class="figures flex flex-col divide-y divide-line rounded-card border border-line">
                @foreach ($withYou as $line)
                    <li wire:key="handover-{{ $line['number'] }}" class="flex justify-between gap-3 p-3">
                        <span>{{ $line['number'] }} <span class="text-ink-soft">{{ $line['customer'] }}</span></span>
                        <span class="font-semibold">{{ Money::format($line['paise']) }}</span>
                    </li>
                @endforeach
            </ul>
            <p class="text-sm text-ink-soft">The shop checks the amount and marks it settled. You will see it here either way.</p>
        </div>
        <x-slot:footer>
            <x-ui.button variant="secondary" x-on:click="$dispatch('close-modal', 'hand-over')">Not yet</x-ui.button>
            <x-ui.button wire:click="handOver" loading="handOver" icon="check">Hand over</x-ui.button>
        </x-slot:footer>
    </x-ui.modal>
</div>
