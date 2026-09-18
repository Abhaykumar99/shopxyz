@use('App\Enums\DeliveryStep')
@use('App\Support\Money')

<div class="flex flex-col gap-3">
    {{-- Where the round stands --}}
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
                <dd class="text-xl font-semibold">{{ $summary['to_deliver'] }}</dd>
            </div>
            <div class="rounded-field bg-white/10 px-2 py-2">
                <dt class="text-xs text-white/70">Delivered</dt>
                <dd class="text-xl font-semibold">{{ $summary['delivered'] }}</dd>
            </div>
            <div class="rounded-field bg-white/10 px-2 py-2">
                <dt class="text-xs text-white/70">Cash with you</dt>
                <dd class="text-xl font-semibold">{{ Money::format($cashWithYou) }}</dd>
            </div>
        </dl>
        @if ($nextJob)
            <a href="{{ route('delivery.order', $nextJob->number) }}" wire:navigate class="flex items-center justify-between gap-3 rounded-field bg-white/10 px-3 py-2 hover:bg-white/15">
                <span class="min-w-0">
                    <span class="block text-xs text-white/70">Next: {{ $nextJob->step->nextAction() }}</span>
                    <span class="figures block truncate font-semibold">{{ $nextJob->number }} · {{ $nextJob->area }}</span>
                </span>
                <x-ui.icon name="chevron-right" class="shrink-0 text-white/70" />
            </a>
        @endif
    </section>

    {{-- Groups, with counts --}}
    @if ($counts !== [])
        <nav aria-label="Filter deliveries" class="-mx-3 overflow-x-auto px-3 no-scrollbar">
            <ul class="flex min-w-max gap-2">
                <li>
                    <button
                        type="button"
                        wire:click="$set('filter', '')"
                        aria-pressed="{{ $filter === '' ? 'true' : 'false' }}"
                        @class([
                            'inline-flex min-h-10 items-center rounded-full border px-4 text-sm font-medium',
                            'border-brand bg-brand text-white' => $filter === '',
                            'border-line bg-surface text-ink-soft' => $filter !== '',
                        ])
                    >All ({{ array_sum($counts) }})</button>
                </li>
                @foreach ($counts as $group => $count)
                    <li>
                        <button
                            type="button"
                            wire:click="$set('filter', @js($group))"
                            aria-pressed="{{ $filter === $group ? 'true' : 'false' }}"
                            @class([
                                'inline-flex min-h-10 items-center rounded-full border px-4 text-sm font-medium',
                                'border-brand bg-brand text-white' => $filter === $group,
                                'border-line bg-surface text-ink-soft' => $filter !== $group,
                            ])
                        >{{ $group }} ({{ $count }})</button>
                    </li>
                @endforeach
            </ul>
        </nav>
    @endif

    @if ($groups === [])
        <x-ui.card>
            <x-ui.empty-state icon="check" title="Nothing to deliver" :level="2">
                The shop will message you when the next parcel is ready.
                <x-slot:action>
                    <x-ui.button variant="secondary" :href="route('delivery.cash')" wire:navigate icon="banknote">Cash to hand over</x-ui.button>
                </x-slot:action>
            </x-ui.empty-state>
        </x-ui.card>
    @endif

    @foreach ($groups as $group => $jobs)
        <section wire:key="group-{{ \Illuminate\Support\Str::slug($group) }}" aria-labelledby="group-{{ \Illuminate\Support\Str::slug($group) }}" class="flex flex-col gap-2">
            <h2 id="group-{{ \Illuminate\Support\Str::slug($group) }}" class="mt-1 px-1 text-lg font-semibold">
                {{ $group }} <span class="figures font-normal text-ink-soft">({{ count($jobs) }})</span>
            </h2>

            @foreach ($jobs as $job)
                <div wire:key="job-{{ $job->number }}" class="flex flex-col gap-2">
                    <x-shop.delivery-order-card
                        :order-number="$job->number"
                        :url="route('delivery.order', $job->number)"
                        :customer="$job->customerName"
                        :area="$job->area"
                        :pincode="$job->pincode"
                        :items="$job->itemCount()"
                        :cod-paise="$job->isCod() ? ($job->step === DeliveryStep::Delivered ? $job->cashCollectedPaise : $job->codPaise) : null"
                        :collected="$job->step === DeliveryStep::Delivered"
                        :status="$job->step->label()"
                        :tone="$job->step->tone()"
                        :boxes="$job->packageCount()"
                        :boxes-picked-up="count($job->pickedUpPackages())"
                    />

                    @if ($job->step === DeliveryStep::Assigned)
                        <x-ui.button wire:click="accept('{{ $job->number }}')" loading="accept('{{ $job->number }}')" size="lg" block icon="check">
                            Accept {{ $job->number }}
                        </x-ui.button>
                    @elseif ($job->step === DeliveryStep::Accepted)
                        <x-ui.button :href="route('delivery.order', $job->number).'#pickup'" wire:navigate variant="secondary" size="lg" block icon="boxes">
                            Enter pickup codes ({{ count($job->pickedUpPackages()) }}/{{ $job->packageCount() }})
                        </x-ui.button>
                    @elseif ($job->step === DeliveryStep::PickedUp)
                        <x-ui.button wire:click="startDelivery('{{ $job->number }}')" loading="startDelivery('{{ $job->number }}')" size="lg" block icon="truck">
                            Start delivery
                        </x-ui.button>
                    @endif
                </div>
            @endforeach
        </section>
    @endforeach
</div>
