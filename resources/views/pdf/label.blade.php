{{--
    Parcel labels (ADR-014, ADR-021). Pure black on white for thermal printers.
    Sizes use `em` so the whole label scales with the paper format.

    One sheet per box: each label carries its own package id, "Box 1 of 3", the
    contents of that box and its pickup code. `$order`: see
    `App\Support\Demo\DemoData::order()` for the expected shape.
    The QR/barcode box is a placeholder until Phase 9.
--}}
@use('App\Enums\PrintFormat')
@use('App\Support\Money')

@php
    $scale = match ($format) {
        PrintFormat::A4 => '19px',
        PrintFormat::A5 => '15px',
        default => '10.5px',
    };
    $isCod = $order['payment_method'] === 'cod';
    $customer = $order['customer'];
    $packages = $order['packages'] ?? [];

    // Without packages (an order that has not been packed yet) we still print
    // one label for the whole order. `box` prints a single box from a set.
    $boxes = $packages === [] ? [null] : $packages;

    if ($packages !== [] && isset($box)) {
        $index = max(0, min(count($packages) - 1, (int) $box));
        $boxes = [$packages[$index]];
    }

    $total = count($packages);
@endphp

<x-layouts::print
    :title="'Label '.$order['number']"
    :document="\App\Enums\PrintDocument::Label"
    :format="$format"
    :sheets="count($boxes)"
>
    @foreach ($boxes as $package)
        @php
            $sequence = $package === null ? 1 : (int) ($package['sequence'] ?? array_search($package, $packages, true) + 1);
            $labelItems = $package['items'] ?? $order['items'];
        @endphp

        <div class="print-sheet mx-auto shrink-0 bg-white text-black shadow-overlay print:shadow-none">
            <div class="flex h-full flex-col gap-[0.8em] font-sans leading-tight" style="font-size: {{ $scale }}">
                <div class="flex items-start justify-between gap-[1em] border-b-2 border-black pb-[0.6em]">
                    <div class="min-w-0">
                        <p class="font-display text-[1.5em] leading-none font-bold">{{ $shop->name }}</p>
                        @if ($shop->phone)
                            <p class="figures mt-[0.3em]">{{ $shop->phone }}</p>
                        @endif
                    </div>
                    <div class="flex shrink-0 flex-col items-center gap-[0.2em]">
                        <div role="img" aria-label="Package QR code (placeholder)" class="flex size-[6.5em] items-center justify-center border-2 border-black">
                            <x-ui.icon name="qr-code" class="size-[5em]" />
                        </div>
                        @if ($package)
                            <p class="figures text-[0.85em] font-semibold">{{ $package['id'] }}</p>
                        @endif
                    </div>
                </div>

                <div class="flex items-end justify-between gap-[1em]">
                    <div>
                        <p class="text-[0.9em]">Order</p>
                        <p class="figures font-display text-[2.4em] leading-none font-bold">{{ $order['number'] }}</p>
                        <p class="figures mt-[0.2em] text-[0.9em]">{{ $order['placed_at'] }}</p>
                    </div>
                    @if ($total > 0)
                        <p class="figures shrink-0 border-2 border-black px-[0.5em] py-[0.2em] font-display text-[1.4em] leading-none font-bold">
                            Box {{ $sequence }} of {{ $total }}
                        </p>
                    @endif
                </div>

                <div class="border-2 border-black p-[0.7em]">
                    <p class="text-[0.9em]">Deliver to</p>
                    <p class="text-[1.5em] font-semibold">{{ $customer['name'] }}</p>
                    <p class="figures text-[1.3em] font-semibold">{{ $customer['phone'] }}</p>
                    <p class="mt-[0.3em] text-[1.15em]">
                        @foreach ($customer['lines'] as $line)
                            {{ $line }}<br>
                        @endforeach
                    </p>
                    <p class="figures mt-[0.3em] font-display text-[2.2em] leading-none font-bold tracking-wide">{{ $customer['pincode'] }}</p>
                </div>

                <div class="grow">
                    <p class="mb-[0.3em] text-[0.9em]">
                        {{ $package ? 'In this box' : 'Items' }} ({{ collect($labelItems)->sum('quantity') }})
                    </p>
                    <ul class="flex flex-col gap-[0.2em]">
                        @foreach ($labelItems as $item)
                            <li class="flex gap-[0.6em]">
                                <span class="figures w-[2em] shrink-0 font-semibold">{{ $item['quantity'] }}×</span>
                                <span class="min-w-0">{{ $item['name'] }} <span class="whitespace-nowrap">({{ $item['variant'] }})</span></span>
                            </li>
                        @endforeach
                    </ul>
                </div>

                @if ($package)
                    <div class="flex items-center justify-between gap-[1em] border-2 border-dashed border-black p-[0.7em]">
                        <p class="text-[0.95em] leading-tight">
                            Pickup code<br>
                            <span class="text-[0.8em]">Delivery partner enters this at the counter</span>
                        </p>
                        <p class="figures font-display text-[2.2em] leading-none font-bold tracking-[0.15em]">{{ $package['code'] }}</p>
                    </div>
                @endif

                <div @class(['flex items-center justify-between gap-[1em] p-[0.7em]', 'bg-black text-white' => $isCod, 'border-2 border-black' => ! $isCod])>
                    <p class="font-display text-[1.6em] leading-none font-bold">{{ $isCod ? 'Collect cash' : 'Prepaid' }}</p>
                    <p class="figures font-display text-[1.9em] leading-none font-bold">{{ $isCod ? Money::format($order['total']) : 'UPI' }}</p>
                </div>

                @if ($isCod && $total > 1)
                    <p class="text-[0.85em] font-semibold">Collect the full amount once, with the last box.</p>
                @endif

                @if ($shop->address)
                    <p class="text-[0.85em]">If undelivered, return to: {{ $shop->name }}, {{ $shop->address }}</p>
                @endif
            </div>
        </div>
    @endforeach
</x-layouts::print>
