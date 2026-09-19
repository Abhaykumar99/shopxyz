@use('App\Support\Money')

<x-filament-panels::page>
    <div class="flex flex-wrap items-center justify-between gap-3">
        <p class="text-sm text-gray-500">
            {{ $from->format('j M Y') }} to {{ $to->format('j M Y') }}
        </p>

        <div class="flex flex-wrap gap-2">
            @foreach ($periods as $value => $label)
                <button
                    type="button"
                    wire:click="$set('period', '{{ $value }}')"
                    @class([
                        'fi-btn rounded-lg px-3 py-1.5 text-sm font-medium ring-1 ring-inset transition',
                        'bg-primary-600 text-white ring-primary-600' => $period === (string) $value,
                        'bg-white text-gray-700 ring-gray-950/10 hover:bg-gray-50' => $period !== (string) $value,
                    ])
                    @if ($period === (string) $value) aria-current="true" @endif
                >{{ $label }}</button>
            @endforeach
        </div>
    </div>

    <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        @foreach ([
            ['Takings', Money::format($revenue), $orderCount.' orders placed'],
            ['Average order', Money::format($averageOrder), $wholesaleOrders.' wholesale orders'],
            ['Delivered', (string) $delivered, $cancelled.' cancelled'],
            ['Cash vs UPI', Money::format($byMethod['Cash on delivery']).' / '.Money::format($byMethod['UPI']), 'Collected in cash / paid online'],
        ] as [$label, $value, $hint])
            <x-filament::section>
                <p class="text-sm text-gray-500">{{ $label }}</p>
                <p class="mt-1 font-display text-2xl font-bold tabular-nums">{{ $value }}</p>
                <p class="mt-1 text-sm text-gray-500">{{ $hint }}</p>
            </x-filament::section>
        @endforeach
    </div>

    <div class="grid gap-4 lg:grid-cols-[2fr_1fr]">
        <x-filament::section heading="Best sellers">
            @if ($topProducts->isEmpty())
                <p class="text-sm text-gray-500">Nothing sold in this period.</p>
            @else
                <table class="w-full text-sm">
                    <thead>
                        <tr class="text-left text-gray-500">
                            <th class="pb-2 font-medium">Product</th>
                            <th class="pb-2 text-right font-medium">Units</th>
                            <th class="pb-2 text-right font-medium">Takings</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @foreach ($topProducts as $row)
                            <tr>
                                <td class="py-2 pr-3">{{ $row->product_name }}</td>
                                <td class="py-2 text-right tabular-nums">{{ $row->units }}</td>
                                <td class="py-2 text-right font-medium tabular-nums">{{ Money::format((int) $row->revenue) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @endif
        </x-filament::section>

        <x-filament::section heading="Orders by status">
            @if ($byStatus->isEmpty())
                <p class="text-sm text-gray-500">No orders in this period.</p>
            @else
                <ul class="divide-y divide-gray-100 text-sm">
                    @foreach ($byStatus as $label => $count)
                        <li class="flex items-center justify-between py-2">
                            <span>{{ $label }}</span>
                            <span class="font-medium tabular-nums">{{ $count }}</span>
                        </li>
                    @endforeach
                </ul>
            @endif
        </x-filament::section>
    </div>
</x-filament-panels::page>
