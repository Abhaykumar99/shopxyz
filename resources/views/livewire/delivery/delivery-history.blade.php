<div class="flex flex-col gap-3">
    <label for="history-search" class="sr-only">Search finished deliveries</label>
    <div class="flex h-12 items-center gap-2 rounded-field border border-line-strong bg-surface px-3 focus-within:outline-2 focus-within:outline-offset-2 focus-within:outline-brand">
        <x-ui.icon name="search" :size="20" class="text-ink-soft" />
        <input
            id="history-search"
            type="search"
            wire:model.live.debounce.400ms="search"
            maxlength="30"
            placeholder="Order number, customer or area"
            class="min-w-0 grow bg-transparent text-base focus:outline-none"
        >
    </div>

    <p class="figures px-1 text-sm text-ink-soft" aria-live="polite">
        {{ count($jobs) }} {{ \Illuminate\Support\Str::plural('delivery', count($jobs)) }}
    </p>

    @if ($jobs === [])
        <x-ui.card>
            <x-ui.empty-state icon="clock" title="Nothing found" :level="2">
                Finished deliveries appear here. Try another order number or area.
            </x-ui.empty-state>
        </x-ui.card>
    @else
        <h2 class="sr-only">Finished deliveries</h2>
        <div wire:loading.class="opacity-60" wire:target="search" class="flex flex-col gap-3">
            @foreach ($jobs as $job)
                <div wire:key="history-{{ $job->number }}" class="flex flex-col gap-1">
                    <p class="figures px-1 text-sm font-medium text-ink-soft">{{ $job->day() }}, {{ $job->finishedAt() }}</p>
                    <x-shop.delivery-order-card
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
                </div>
            @endforeach
        </div>
    @endif
</div>
