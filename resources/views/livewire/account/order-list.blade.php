@use('App\Support\Money')

<div class="flex flex-col gap-4">
    <div role="group" aria-label="Show orders" class="inline-flex self-start rounded-full border border-line bg-surface p-1">
        @foreach (['active' => 'In progress', 'past' => 'Past orders'] as $value => $label)
            <button
                type="button"
                wire:click="$set('show', '{{ $value }}')"
                aria-pressed="{{ $current === $value ? 'true' : 'false' }}"
                @class([
                    'inline-flex min-h-10 items-center gap-2 rounded-full px-4 text-sm font-semibold transition-colors',
                    'bg-brand text-white' => $current === $value,
                    'text-ink-soft hover:text-ink' => $current !== $value,
                ])
            >
                {{ $label }}
                <span class="figures">{{ $counts[$value] }}</span>
            </button>
        @endforeach
    </div>

    @if ($orders === [])
        <x-ui.card>
            <x-ui.empty-state icon="package" :title="$current === 'past' ? 'No past orders yet' : 'No orders in progress'">
                {{ $current === 'past' ? 'Delivered and cancelled orders appear here.' : 'When you place an order, you can track it here.' }}
                <x-slot:action>
                    <x-ui.button :href="route('shop.home')">Start shopping</x-ui.button>
                </x-slot:action>
            </x-ui.empty-state>
        </x-ui.card>
    @else
        <ul class="flex flex-col gap-3" wire:loading.class="opacity-60" wire:target="show">
            @foreach ($orders as $order)
                <li wire:key="order-{{ $order->number }}">
                    <article class="relative flex flex-col gap-3 rounded-card border border-line bg-surface p-4 transition-colors hover:border-line-strong sm:flex-row sm:items-center sm:gap-5">
                        <div class="flex shrink-0 -space-x-3">
                            @foreach (array_slice($order->items, 0, 3) as $item)
                                <x-shop.product-image :category="$item['category']" alt="" class="size-12 rounded-full ring-2 ring-surface" />
                            @endforeach
                        </div>
                        <div class="flex min-w-0 grow flex-col gap-1">
                            <div class="flex flex-wrap items-center gap-2">
                                <h2 class="figures font-display text-lg font-bold">
                                    <a href="{{ route('account.order', $order->number) }}" class="after:absolute after:inset-0">{{ $order->number }}</a>
                                </h2>
                                <x-ui.status-pill :tone="$order->status->tone()">{{ $order->status->label() }}</x-ui.status-pill>
                                @if ($order->isActive() && in_array($order->paymentStatus->tone(), ['offer', 'danger'], true))
                                    <x-ui.status-pill :tone="$order->paymentStatus->tone()">{{ $order->paymentStatus->label() }}</x-ui.status-pill>
                                @endif
                            </div>
                            <p class="truncate text-sm text-ink-soft">
                                {{ collect($order->items)->pluck('name')->join(', ') }}
                            </p>
                            <p class="figures text-sm text-ink-soft">
                                {{ $order->placedAt->format('j M Y, g:i a') }}, {{ $order->itemCount() }} {{ \Illuminate\Support\Str::plural('item', $order->itemCount()) }}
                            </p>
                        </div>
                        <div class="flex items-center justify-between gap-3 sm:flex-col sm:items-end">
                            <x-shop.price :paise="$order->total()" />
                            @if ($order->needsPaymentProof())
                                <x-ui.button size="sm" :href="route('orders.pay', $order->number)" class="relative z-10">Pay now</x-ui.button>
                            @else
                                <span class="inline-flex items-center gap-1 text-sm font-semibold text-brand">
                                    {{ $order->isActive() ? 'Track' : 'View' }} <x-ui.icon name="chevron-right" :size="16" />
                                </span>
                            @endif
                        </div>
                    </article>
                </li>
            @endforeach
        </ul>
    @endif
</div>
