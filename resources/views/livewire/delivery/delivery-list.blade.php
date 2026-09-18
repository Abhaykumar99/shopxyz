@use('App\Enums\DeliveryStep')
@use('App\Support\Money')

<div class="flex flex-col gap-3">
    <section aria-labelledby="round-title" class="flex flex-col gap-3 rounded-card bg-ink p-4 text-white">
        <div class="flex items-start justify-between gap-3">
            <div>
                <p class="text-sm text-white/70">{{ now()->format('l, j F') }}</p>
                <h2 id="round-title" class="text-2xl">Good to see you, {{ $firstName }}</h2>
            </div>
            <x-ui.icon name="bike" :size="28" class="text-accent" />
        </div>
        <dl class="figures grid grid-cols-3 gap-2 text-center">
            <div class="rounded-field bg-white/10 px-2 py-2">
                <dt class="text-xs text-white/70">To deliver</dt>
                <dd class="text-xl font-semibold">{{ count($active) }}</dd>
            </div>
            <div class="rounded-field bg-white/10 px-2 py-2">
                <dt class="text-xs text-white/70">Delivered</dt>
                <dd class="text-xl font-semibold">{{ $cash['deliveries'] }}</dd>
            </div>
            <div class="rounded-field bg-white/10 px-2 py-2">
                <dt class="text-xs text-white/70">Cash held</dt>
                <dd class="text-xl font-semibold">{{ Money::format($cash['to_hand_over']) }}</dd>
            </div>
        </dl>
    </section>

    @if ($active === [])
        <x-ui.card>
            <x-ui.empty-state icon="check" title="Nothing left to deliver" :level="2">
                The shop will message you when the next parcel is ready.
                <x-slot:action>
                    <x-ui.button variant="secondary" :href="route('delivery.cash')" wire:navigate icon="banknote">Cash to hand over</x-ui.button>
                </x-slot:action>
            </x-ui.empty-state>
        </x-ui.card>
    @else
        <h2 class="mt-1 px-1 text-lg font-semibold">To deliver ({{ count($active) }})</h2>
        @foreach ($active as $job)
            <div wire:key="job-{{ $job->number }}" class="flex flex-col gap-2">
                <x-shop.delivery-order-card
                    :order-number="$job->number"
                    :url="route('delivery.order', $job->number)"
                    :customer="$job->customerName"
                    :area="$job->area"
                    :pincode="$job->pincode"
                    :items="$job->itemCount()"
                    :cod-paise="$job->isCod() ? $job->codPaise : null"
                    :status="$job->step->label()"
                    :tone="$job->step->tone()"
                />
                @if ($job->step === DeliveryStep::Assigned)
                    <x-ui.button wire:click="accept('{{ $job->number }}')" loading="accept('{{ $job->number }}')" size="lg" block icon="check">
                        Accept {{ $job->number }}
                    </x-ui.button>
                @endif
            </div>
        @endforeach
    @endif

    @if ($finished !== [])
        <h2 class="mt-3 px-1 text-lg font-semibold">Finished today ({{ count($finished) }})</h2>
        @foreach ($finished as $job)
            <x-shop.delivery-order-card
                wire:key="done-{{ $job->number }}"
                :order-number="$job->number"
                :url="route('delivery.order', $job->number)"
                :customer="$job->customerName"
                :area="$job->area"
                :pincode="$job->pincode"
                :items="$job->itemCount()"
                :cod-paise="$job->cashCollectedPaise > 0 ? $job->cashCollectedPaise : null"
                collected
                :status="$job->step->label()"
                :tone="$job->step->tone()"
            />
        @endforeach
    @endif
</div>
