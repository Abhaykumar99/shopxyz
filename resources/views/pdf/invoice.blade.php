{{--
    Invoice (ADR-014): A4 or A5. Sizes use `em` so A5 scales down cleanly.
    $order: see App\Support\Demo\DemoData::order() for the expected shape.
    GST columns are added once client question 1 is answered.
--}}
@use('App\Enums\PrintFormat')
@use('App\Support\Money')

@php
    $scale = $format === PrintFormat::A5 ? '11px' : '13px';
    $customer = $order['customer'];
@endphp

<x-layouts::print :title="'Invoice '.$order['invoice_number']" :document="\App\Enums\PrintDocument::Invoice" :format="$format">
    <div class="flex flex-col gap-[1.6em] font-sans leading-snug" style="font-size: {{ $scale }}">
        <header class="flex items-start justify-between gap-[2em] border-b-2 border-black pb-[1em]">
            <div class="min-w-0">
                <p class="font-display text-[2em] leading-none font-bold">{{ $shop->name }}</p>
                @if ($shop->address)
                    <p class="mt-[0.5em]">{{ $shop->address }}</p>
                @endif
                <p class="figures">
                    @if ($shop->phone) {{ $shop->phone }} @endif
                    @if ($shop->email) <br>{{ $shop->email }} @endif
                </p>
            </div>
            <div class="shrink-0 text-end">
                <h2 class="font-display text-[1.8em] leading-none font-bold">Invoice</h2>
                <dl class="figures mt-[0.6em] grid grid-cols-[auto_auto] gap-x-[1em] gap-y-[0.15em] text-start">
                    <dt>Invoice no.</dt><dd class="font-semibold">{{ $order['invoice_number'] }}</dd>
                    <dt>Order no.</dt><dd class="font-semibold">{{ $order['number'] }}</dd>
                    <dt>Date</dt><dd>{{ $order['placed_at'] }}</dd>
                </dl>
            </div>
        </header>

        <section class="grid grid-cols-2 gap-[2em]">
            <div>
                <h2 class="font-sans text-[0.9em] font-semibold">Billed and delivered to</h2>
                <p class="mt-[0.3em] font-semibold">{{ $customer['name'] }}</p>
                <p>
                    @foreach ($customer['lines'] as $line)
                        {{ $line }}<br>
                    @endforeach
                    <span class="figures">{{ $customer['pincode'] }}</span>
                </p>
                <p class="figures">{{ $customer['phone'] }}</p>
            </div>
            <div>
                <h2 class="font-sans text-[0.9em] font-semibold">Payment</h2>
                <p class="mt-[0.3em]">{{ $order['payment_method'] === 'upi' ? 'UPI' : 'Cash on delivery' }}</p>
                <p>{{ $order['payment_status'] }}</p>
            </div>
        </section>

        <table class="figures w-full border-collapse text-start">
            <thead>
                <tr class="border-y-2 border-black">
                    <th scope="col" class="py-[0.5em] pe-[1em] text-start font-semibold">Item</th>
                    <th scope="col" class="py-[0.5em] pe-[1em] text-end font-semibold">Qty</th>
                    <th scope="col" class="py-[0.5em] pe-[1em] text-end font-semibold">MRP</th>
                    <th scope="col" class="py-[0.5em] pe-[1em] text-end font-semibold">Price</th>
                    <th scope="col" class="py-[0.5em] text-end font-semibold">Amount</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($order['items'] as $item)
                    <tr class="border-b border-black/30 align-top">
                        <td class="py-[0.5em] pe-[1em]">
                            {{ $item['name'] }}
                            <span class="block text-[0.9em]">{{ $item['variant'] }}, SKU {{ $item['sku'] }}</span>
                        </td>
                        <td class="py-[0.5em] pe-[1em] text-end">{{ $item['quantity'] }}</td>
                        <td class="py-[0.5em] pe-[1em] text-end">{{ Money::format($item['mrp']) }}</td>
                        <td class="py-[0.5em] pe-[1em] text-end">{{ Money::format($item['paise']) }}</td>
                        <td class="py-[0.5em] text-end">{{ Money::format($item['paise'] * $item['quantity']) }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        <dl class="figures ms-auto grid w-[60%] grid-cols-[1fr_auto] gap-y-[0.3em]">
            <dt>Total MRP</dt><dd class="text-end">{{ Money::format($order['mrp_total']) }}</dd>
            <dt>Discount</dt><dd class="text-end">−{{ Money::format($order['discount']) }}</dd>
            <dt>Delivery</dt><dd class="text-end">{{ $order['delivery'] ? Money::format($order['delivery']) : 'Free' }}</dd>
            <dt class="border-t-2 border-black pt-[0.4em] font-display text-[1.3em] font-bold">Total</dt>
            <dd class="border-t-2 border-black pt-[0.4em] text-end font-display text-[1.3em] font-bold">{{ Money::format($order['total']) }}</dd>
        </dl>

        <footer class="border-t border-black/30 pt-[1em] text-[0.9em]">
            <p>
                Thank you for shopping with {{ $shop->name }}.
                @if ($shop->phone)
                    For help with this order, call {{ $shop->phone }} and quote {{ $order['number'] }}.
                @endif
            </p>
        </footer>
    </div>
</x-layouts::print>
