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
    @if ($job->step === DeliveryStep::Reached)
        <x-ui.card class="flex flex-col gap-3">
            <h2 class="text-lg font-semibold">Confirm with the customer</h2>
            <p class="text-ink-soft">Ask for the 6-digit delivery code on their order page.</p>
            <form wire:submit="confirmDelivery" id="confirm-form" class="flex flex-col gap-3" novalidate>
                <x-ui.input
                    label="Delivery code"
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
        </x-ui.alert>
    @endif

    {{-- The one action in thumb reach --}}
    @unless ($job->isFinished())
        <x-slot:action>
            @if ($job->step === DeliveryStep::Reached)
                <x-ui.button type="submit" form="confirm-form" size="lg" block icon="check" loading="confirmDelivery">Confirm delivery</x-ui.button>
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
