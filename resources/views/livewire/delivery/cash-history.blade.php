@use('App\Support\Money')

<div class="flex flex-col gap-3">
    <x-ui.card padding="lg" class="flex flex-col gap-1">
        <h2 class="text-lg font-semibold">Settled with the shop</h2>
        <p class="figures font-display text-3xl font-bold">{{ Money::format($settledTotal) }}</p>
        <p class="text-ink-soft">Across {{ count($batches) }} {{ \Illuminate\Support\Str::plural('handover', count($batches)) }}.</p>
    </x-ui.card>

    @if ($batches === [])
        <x-ui.card>
            <x-ui.empty-state icon="banknote" title="No handovers yet" :level="2">
                Cash you hand in at the shop will be listed here with what the shop did with it.
            </x-ui.empty-state>
        </x-ui.card>
    @else
        <h2 class="mt-1 px-1 text-lg font-semibold">Handovers</h2>
        @foreach ($batches as $batch)
            <x-ui.card wire:key="batch-{{ $batch->reference }}" class="flex flex-col gap-2">
                <div class="flex items-start justify-between gap-3">
                    <div class="min-w-0">
                        <p class="figures font-semibold">{{ $batch->reference }}</p>
                        <p class="text-sm text-ink-soft">
                            {{ $batch->handedOverOn() }}, {{ $batch->handedOverTime() }} ·
                            {{ $batch->orderCount() }} {{ \Illuminate\Support\Str::plural('order', $batch->orderCount()) }}
                        </p>
                    </div>
                    <x-shop.price :paise="$batch->amountPaise" />
                </div>

                <div class="flex flex-wrap items-center gap-2">
                    <x-ui.status-pill :tone="$batch->status->tone()">{{ $batch->status->label() }}</x-ui.status-pill>
                    @if ($batch->verifiedOn())
                        <span class="text-sm text-ink-soft">
                            Counted {{ $batch->verifiedOn() }}@if ($batch->verifiedBy) by {{ $batch->verifiedBy }}@endif
                        </span>
                    @endif
                </div>

                <ul class="figures flex flex-col gap-1 border-t border-line pt-2 text-sm text-ink-soft">
                    @foreach ($batch->collections as $collection)
                        <li class="flex justify-between gap-3">
                            <span class="min-w-0 truncate">{{ $collection['number'] }} · {{ $collection['customer'] }}</span>
                            <span class="shrink-0">{{ Money::format($collection['paise']) }}</span>
                        </li>
                    @endforeach
                </ul>

                @if ($batch->note)
                    <p class="text-sm text-ink-soft">{{ $batch->note }}</p>
                @endif
            </x-ui.card>
        @endforeach
    @endif
</div>
