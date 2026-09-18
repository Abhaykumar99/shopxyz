@use('App\Enums\DeliveryStep')
@use('App\Support\IndianPhone')
@use('App\Support\Money')

<div class="flex flex-col gap-3">
    {{-- Where this delivery has got to --}}
    <x-ui.card class="flex flex-col gap-3">
        <div class="flex items-center justify-between gap-2">
            <x-ui.status-pill :tone="$job->step->tone()">{{ $job->step->label() }}</x-ui.status-pill>
            @if ($job->assignedAt())
                <span class="figures text-sm text-ink-soft">Assigned {{ $job->assignedAt() }}</span>
            @endif
        </div>
        <p class="text-ink-soft">{{ $job->step->hint() }}</p>
        <ol class="flex flex-col gap-2">
            @foreach ($job->progress() as $step)
                <li class="flex items-center gap-3">
                    <span @class([
                        'flex size-7 shrink-0 items-center justify-center rounded-full',
                        'bg-pistachio-tint text-pistachio' => $step['state'] === 'done',
                        'bg-brand text-white' => $step['state'] === 'current',
                        'bg-mist text-ink-soft' => $step['state'] === 'upcoming',
                    ])>
                        @if ($step['state'] === 'done')
                            <x-ui.icon name="check" :size="16" />
                        @else
                            <span aria-hidden="true" class="size-2 rounded-full bg-current"></span>
                        @endif
                    </span>
                    <span @class(['grow', 'font-semibold' => $step['state'] === 'current', 'text-ink-soft' => $step['state'] === 'upcoming'])>
                        {{ $step['label'] }}
                    </span>
                    @if ($step['time'])
                        <span class="figures text-sm text-ink-soft">{{ $step['time'] }}</span>
                    @endif
                </li>
            @endforeach
        </ol>
    </x-ui.card>

    {{-- Who and where --}}
    <x-ui.card class="flex flex-col gap-3">
        <div>
            <p class="text-xl font-semibold">{{ $job->customerName }}</p>
            <address class="mt-1 text-lg not-italic">
                @foreach ($job->addressLines as $line)
                    {{ $line }}<br>
                @endforeach
                {{ $job->area }}, <span class="figures font-semibold">{{ $job->pincode }}</span>
            </address>
            <p class="figures mt-1 text-ink-soft">{{ IndianPhone::format($job->phone) }}</p>
        </div>
        <div class="grid grid-cols-2 gap-2">
            <x-ui.button variant="secondary" icon="phone" :href="$job->callUrl()">Call</x-ui.button>
            <x-ui.button variant="secondary" icon="navigation" :href="$job->mapsUrl()" target="_blank" rel="noopener">Directions</x-ui.button>
        </div>
        @if ($job->note)
            <x-ui.alert tone="warning" title="Customer note">{{ $job->note }}</x-ui.alert>
        @endif
    </x-ui.card>

    {{-- Payment --}}
    @if ($job->isCod())
        <x-ui.card class="flex items-center justify-between gap-3 bg-accent-tint">
            <span class="flex items-center gap-2 font-semibold">
                <x-ui.icon name="banknote" :size="24" class="text-accent-ink" />
                {{ $job->step === DeliveryStep::Delivered ? 'Cash collected' : 'Collect in cash' }}
            </span>
            <x-shop.price :paise="$job->step === DeliveryStep::Delivered ? $job->cashCollectedPaise : $job->codPaise" size="lg" />
        </x-ui.card>
    @else
        <x-ui.card class="flex items-center justify-between gap-3">
            <span class="flex items-center gap-2 font-semibold">
                <x-ui.icon name="badge-check" :size="24" class="text-pistachio" />
                Already paid by UPI
            </span>
            <span class="figures text-ink-soft">{{ Money::format($job->totalPaise) }}</span>
        </x-ui.card>
    @endif

    {{-- Pickup: one code per box, read off the label (ADR-021) --}}
    @if ($job->step === DeliveryStep::Accepted)
        <x-ui.card id="pickup" class="flex scroll-mt-20 flex-col gap-3">
            <div class="flex items-center justify-between gap-2">
                <h2 class="text-lg font-semibold">Collect {{ $job->packageCount() }} {{ \Illuminate\Support\Str::plural('box', $job->packageCount()) }}</h2>
                <span class="figures text-sm text-ink-soft">{{ count($job->pickedUpPackages()) }} of {{ $job->packageCount() }} verified</span>
            </div>
            <p class="text-ink-soft">Enter the pickup code printed on each box label. The order goes out for delivery once every box is verified.</p>

            <ul class="flex flex-col gap-3">
                @foreach ($job->packages as $package)
                    <li wire:key="pkg-{{ $package->id }}" @class([
                        'rounded-card border p-3',
                        'border-pistachio/40 bg-pistachio-tint/40' => $package->isPickedUp(),
                        'border-line' => ! $package->isPickedUp(),
                    ])>
                        <div class="flex items-start justify-between gap-2">
                            <div class="min-w-0">
                                <p class="font-semibold">{{ $package->label() }}</p>
                                <p class="figures text-sm text-ink-soft">{{ $package->id }}</p>
                            </div>
                            @if ($package->isPickedUp())
                                <span class="flex items-center gap-1.5 text-sm font-semibold text-pistachio">
                                    <x-ui.icon name="circle-check" :size="18" /> Verified {{ $package->pickedUpTime() }}
                                </span>
                            @endif
                        </div>
                        <p class="mt-1 text-sm text-ink-soft">{{ $package->contents() }}</p>

                        @unless ($package->isPickedUp())
                            <form wire:submit="verifyPickup('{{ $package->id }}')" class="mt-3 flex items-end gap-2" novalidate>
                                <x-ui.input
                                    :label="'Pickup code for '.$package->label()"
                                    :name="'pickupCodes.'.$package->id"
                                    wire:model="pickupCodes.{{ $package->id }}"
                                    inputmode="numeric"
                                    maxlength="6"
                                    required
                                    class="grow"
                                    input-class="figures text-center font-display text-xl tracking-[0.3em]"
                                />
                                <x-ui.button type="submit" loading="verifyPickup('{{ $package->id }}')" class="mb-[2px] shrink-0">Verify</x-ui.button>
                            </form>
                        @endunless
                    </li>
                @endforeach
            </ul>

            @if ($pickupAttemptsLeft < \App\Support\Demo\DemoDeliveries::MAX_PICKUP_ATTEMPTS)
                <p class="text-sm text-ink-soft">
                    {{ $pickupAttemptsLeft }} {{ \Illuminate\Support\Str::plural('try', $pickupAttemptsLeft) }} left before the shop has to check the boxes.
                </p>
            @endif
        </x-ui.card>
    @elseif ($job->packages !== [])
        <x-ui.card class="flex items-center justify-between gap-3">
            <span class="flex items-center gap-2 font-semibold">
                <x-ui.icon name="boxes" :size="24" class="text-brand" />
                {{ $job->packageCount() }} {{ \Illuminate\Support\Str::plural('box', $job->packageCount()) }}
            </span>
            <span class="text-sm text-ink-soft">
                {{ $job->allPickedUp() ? 'All verified at pickup' : count($job->pickedUpPackages()).' of '.$job->packageCount().' picked up' }}
            </span>
        </x-ui.card>
    @endif

    {{-- Items --}}
    <x-ui.card>
        <x-slot:header>
            <h2 class="text-lg font-semibold">Items</h2>
            <span class="figures text-sm text-ink-soft">{{ $job->itemCount() }} in total</span>
        </x-slot:header>
        <ul class="flex flex-col divide-y divide-line">
            @foreach ($job->items as $item)
                <li class="flex gap-3 py-2">
                    <span class="figures w-9 shrink-0 font-semibold">{{ $item['quantity'] }}×</span>
                    <span>{{ $item['name'] }} <span class="text-ink-soft">({{ $item['variant'] }})</span></span>
                </li>
            @endforeach
        </ul>
    </x-ui.card>

    {{-- Confirming the delivery --}}
    @if ($job->step === DeliveryStep::OutForDelivery)
        <x-ui.card class="flex flex-col gap-3">
            <h2 class="text-lg font-semibold">Confirm with the customer</h2>
            <p class="text-ink-soft">Hand over the {{ $job->packageCount() }} {{ \Illuminate\Support\Str::plural('box', $job->packageCount()) }}, then ask for the 6-digit OTP on their order page.</p>
            <form wire:submit="confirmDelivery" id="confirm-form" class="flex flex-col gap-3" novalidate>
                <x-ui.input
                    label="Delivery OTP"
                    name="code"
                    wire:model="code"
                    inputmode="numeric"
                    autocomplete="one-time-code"
                    maxlength="6"
                    required
                    :hint="$attemptsLeft < \App\Support\Demo\DemoDeliveries::MAX_CODE_ATTEMPTS ? $attemptsLeft.' '.\Illuminate\Support\Str::plural('try', $attemptsLeft).' left' : null"
                    input-class="figures text-center font-display text-2xl tracking-[0.4em]"
                />
                @if ($job->isCod())
                    <x-ui.input
                        label="Cash collected"
                        name="cash"
                        wire:model="cash"
                        inputmode="numeric"
                        prefix="₹"
                        required
                        :hint="'The full amount is '.Money::format($job->codPaise).'.'"
                        input-class="figures"
                    />
                @endif
            </form>
        </x-ui.card>
    @endif

    {{-- What happened, once it is finished --}}
    @if ($job->step === DeliveryStep::Delivered)
        <x-ui.alert tone="success" title="Delivered at {{ $job->timeFor(DeliveryStep::Delivered) }}">
            @if ($job->cashCollectedPaise > 0)
                You are holding {{ Money::format($job->cashCollectedPaise) }} in cash for this order.
                <x-ui.link :href="route('delivery.cash')" wire:navigate>Cash to hand over</x-ui.link>
            @else
                This order was paid by UPI, so there is no cash to hand over.
            @endif
        </x-ui.alert>
    @elseif ($job->step === DeliveryStep::Failed)
        <x-ui.alert tone="danger" title="Could not deliver at {{ $job->timeFor(DeliveryStep::Failed) }}">
            {{ $job->failureReason?->label() }}@if ($job->failureNote): {{ $job->failureNote }}@endif

            @if ($job->packagesToReturn() > 0)
                <p class="mt-2 font-semibold">
                    Take {{ $job->packagesToReturn() }} {{ \Illuminate\Support\Str::plural('box', $job->packagesToReturn()) }} back to the shop.
                </p>
            @endif
        </x-ui.alert>
    @endif

    {{-- The one action in thumb reach --}}
    @unless ($job->isFinished())
        <x-slot:action>
            @if ($job->step === DeliveryStep::OutForDelivery)
                <x-ui.button type="submit" form="confirm-form" size="lg" block icon="check" loading="confirmDelivery">Confirm delivery</x-ui.button>
            @elseif ($job->step === DeliveryStep::Accepted)
                <x-ui.button href="#pickup" size="lg" block icon="boxes">
                    {{ $job->step->nextAction() }} ({{ count($job->pickedUpPackages()) }}/{{ $job->packageCount() }})
                </x-ui.button>
            @else
                <x-ui.button wire:click="advance" loading="advance" size="lg" block icon="check">{{ $job->step->nextAction() }}</x-ui.button>
            @endif
            @unless ($job->step === DeliveryStep::Assigned)
                <x-ui.button variant="ghost" block x-on:click="$dispatch('open-modal', 'delivery-failed')">Could not deliver</x-ui.button>
            @endunless
        </x-slot:action>
    @endunless

    {{-- Could not deliver --}}
    <x-ui.modal name="delivery-failed" title="Could not deliver" sheet>
        <form wire:submit="reportFailure" id="failure-form" class="flex flex-col gap-4" novalidate>
            <p class="text-ink-soft">Tell the shop what happened. They will call the customer and sort out a new time.</p>
            <fieldset class="flex flex-col gap-2">
                <legend class="mb-1 text-sm font-semibold">What happened?</legend>
                @foreach ($reasons as $value => $label)
                    <x-ui.radio-card
                        wire:key="reason-{{ $value }}"
                        name="failureReason"
                        :value="$value"
                        :title="$label"
                        wire:model.live="failureReason"
                    />
                @endforeach
                @error('failureReason')
                    <p class="text-sm font-medium text-danger">{{ $message }}</p>
                @enderror
            </fieldset>
            <x-ui.textarea
                label="Note for the shop"
                name="failureNote"
                wire:model="failureNote"
                rows="3"
                maxlength="200"
                :required="$needsNote"
                placeholder="For example: the flat number does not exist, customer asked for Sunday morning"
            />
        </form>
        <x-slot:footer>
            <x-ui.button variant="secondary" x-on:click="$dispatch('close-modal', 'delivery-failed')">Keep trying</x-ui.button>
            <x-ui.button type="submit" form="failure-form" variant="danger" loading="reportFailure">Report to the shop</x-ui.button>
        </x-slot:footer>
    </x-ui.modal>
</div>
