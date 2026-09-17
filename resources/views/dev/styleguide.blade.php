@php
    $swatches = [
        ['ink', '#1F1424', 'Text', 'bg-ink'],
        ['ink-soft', '#665A6B', 'Secondary text', 'bg-ink-soft'],
        ['paper', '#FAF7F8', 'Page background', 'bg-paper'],
        ['mist', '#F3EDF0', 'Quiet fills', 'bg-mist'],
        ['line-strong', '#8A7B8F', 'Field borders', 'bg-line-strong'],
        ['brand', '#7B1E45', 'Actions, links, focus', 'bg-brand'],
        ['brand-tint', '#F6E6EC', 'Selected, price tag', 'bg-brand-tint'],
        ['accent', '#D6A64B', 'Offers and waiting only', 'bg-accent'],
        ['pistachio', '#2F6B4E', 'Paid, delivered', 'bg-pistachio'],
        ['info', '#2A5B87', 'New, confirmed', 'bg-info'],
        ['danger', '#B3261E', 'Errors, cancelled', 'bg-danger'],
    ];
    $sections = [
        'colours' => 'Colours',
        'type' => 'Type',
        'buttons' => 'Buttons',
        'forms' => 'Forms',
        'status' => 'Status',
        'feedback' => 'Feedback',
        'overlays' => 'Overlays',
        'navigation' => 'Navigation',
        'prices' => 'Prices',
        'products' => 'Products',
        'cart' => 'Bag and checkout',
        'tracking' => 'Order tracking',
        'screens' => 'Other screens',
        'icons' => 'Icons',
    ];
@endphp

<x-layouts::shop title="Design system" active="home" noindex class="flex flex-col gap-10">

    <div class="flex flex-col gap-4">
        <x-ui.breadcrumb :items="['Developer' => url('/dev/ui'), 'Design system' => null]" />
        <h1 class="text-3xl font-bold">The shop counter</h1>
        <p class="max-w-2xl text-lg text-ink-soft">
            Components for {{ $shop->name }}. Every screen in the shop, the delivery panel and the printed
            documents is built from these pieces. This page only exists on developer machines.
        </p>
        <nav aria-label="Sections" class="flex flex-wrap gap-2">
            @foreach ($sections as $anchor => $label)
                <a href="#{{ $anchor }}" class="rounded-full border border-line bg-surface px-3 py-1.5 text-sm font-medium hover:border-brand hover:text-brand">{{ $label }}</a>
            @endforeach
        </nav>
    </div>

    <x-dev.section id="colours" title="Colours" description="Only these colours exist in the theme. Every text pairing meets WCAG AA contrast.">
        <div class="grid grid-cols-2 gap-3 sm:grid-cols-3 lg:grid-cols-4">
            @foreach ($swatches as [$name, $hex, $use, $class])
                <div class="flex items-center gap-3 rounded-card border border-line bg-surface p-2">
                    <span class="size-12 shrink-0 rounded-field border border-line {{ $class }}"></span>
                    <span class="min-w-0">
                        <span class="block font-semibold">{{ $name }}</span>
                        <span class="figures block text-sm text-ink-soft">{{ $hex }}</span>
                        <span class="block truncate text-sm text-ink-soft">{{ $use }}</span>
                    </span>
                </div>
            @endforeach
        </div>
    </x-dev.section>

    <x-dev.section id="type" title="Type" description="Bricolage Grotesque for headings and prices, Mukta for reading. Mukta also covers Hindi: नमस्ते, आपका ऑर्डर रास्ते में है।">
        <x-ui.card class="flex flex-col gap-3">
            <p class="font-display text-3xl font-bold">Gifts for every occasion</p>
            <p class="font-display text-2xl font-bold">Fresh kaju katli, packed today</p>
            <p class="font-display text-xl font-semibold">Your bag</p>
            <p class="text-lg">Delivered to your door by our own delivery team.</p>
            <p class="max-w-prose">Pay in cash when your order arrives, or pay by UPI and upload the screenshot. We check every payment by hand and confirm your order within the hour.</p>
            <p class="text-sm text-ink-soft">Prices include all taxes.</p>
        </x-ui.card>
    </x-dev.section>

    <x-dev.section id="buttons" title="Buttons">
        <div class="flex flex-wrap items-center gap-3">
            <x-ui.button icon="shopping-bag">Add to bag</x-ui.button>
            <x-ui.button variant="secondary">Save address</x-ui.button>
            <x-ui.button variant="ghost" icon="pencil">Edit</x-ui.button>
            <x-ui.button variant="danger" icon="x">Cancel order</x-ui.button>
            <x-ui.button disabled>Place order</x-ui.button>
        </div>
        <div class="flex flex-wrap items-center gap-3">
            <x-ui.button size="sm">Small</x-ui.button>
            <x-ui.button>Medium</x-ui.button>
            <x-ui.button size="lg" icon-end="chevron-right">Continue to payment</x-ui.button>
            <x-ui.icon-button icon="search" label="Search" />
            <x-ui.icon-button icon="shopping-bag" label="Your bag" variant="secondary" :count="3" />
            <x-ui.icon-button icon="plus" label="Add" variant="primary" />
        </div>
        <div class="max-w-sm">
            <x-ui.button block size="lg">Place order</x-ui.button>
        </div>
        <p>Inline link: <x-ui.link :href="url('/dev/ui#forms')">change delivery address</x-ui.link></p>
    </x-dev.section>

    <x-dev.section id="forms" title="Forms" description="16 px text so phones don't zoom. Every field has a visible label; optional fields say so.">
        <div class="grid gap-5 md:grid-cols-2">
            <x-ui.input label="Full name" name="demo_name" autocomplete="name" required value="Priya Sharma" />
            <x-ui.input label="Mobile number" name="demo_phone" type="tel" prefix="+91" inputmode="numeric" autocomplete="tel-national" required hint="The delivery partner will call this number." />
            <x-ui.input label="Pincode" name="demo_pincode" inputmode="numeric" required value="80001" error="Enter a 6-digit pincode." />
            <x-ui.input label="Landmark" name="demo_landmark" placeholder="Near Pani Tanki" />
            <x-ui.select label="State" name="demo_state" required :options="['BR' => 'Bihar', 'JH' => 'Jharkhand', 'UP' => 'Uttar Pradesh']" placeholder="Choose a state" />
            <x-ui.input label="Search" name="demo_search" type="search" icon="search" placeholder="Search products" />
            <x-ui.textarea label="Note for delivery" name="demo_note" hint="For example: call before arriving." class="md:col-span-2" />
            <x-ui.checkbox label="Make this my default address" name="demo_default" checked />
        </div>
        <fieldset class="flex flex-col gap-3">
            <legend class="mb-2 text-sm font-semibold">Payment method</legend>
            <div class="grid gap-3 md:grid-cols-2">
                <x-shop.payment-option method="cod" name="demo_payment" checked />
                <x-shop.payment-option method="upi" name="demo_payment" />
            </div>
        </fieldset>
    </x-dev.section>

    <x-dev.section id="status" title="Status" description="One colour per meaning, used everywhere: blue for new, gold for waiting, mulberry for on the move, green for done, red for stopped.">
        <div class="flex flex-wrap gap-2">
            <x-ui.status-pill tone="info">Placed</x-ui.status-pill>
            <x-ui.status-pill tone="offer">Payment check</x-ui.status-pill>
            <x-ui.status-pill tone="info">Confirmed</x-ui.status-pill>
            <x-ui.status-pill tone="offer">Packing</x-ui.status-pill>
            <x-ui.status-pill tone="brand">Out for delivery</x-ui.status-pill>
            <x-ui.status-pill tone="success">Delivered</x-ui.status-pill>
            <x-ui.status-pill tone="danger">Cancelled</x-ui.status-pill>
            <x-ui.status-pill>Draft</x-ui.status-pill>
        </div>
        <div class="flex flex-wrap gap-2">
            <x-ui.badge>Home</x-ui.badge>
            <x-ui.badge tone="brand">Default</x-ui.badge>
            <x-ui.badge tone="offer">Festive offer</x-ui.badge>
            <x-ui.badge tone="success" icon="check">Paid by UPI</x-ui.badge>
            <x-ui.badge tone="info" icon="clock">Arrives today</x-ui.badge>
            <x-ui.badge tone="danger">Only 2 left</x-ui.badge>
        </div>
    </x-dev.section>

    <x-dev.section id="feedback" title="Feedback">
        <div class="grid gap-3 md:grid-cols-2">
            <x-ui.alert title="We're checking your payment">Your order is confirmed as soon as we match UTR 412345678901. This usually takes under an hour.</x-ui.alert>
            <x-ui.alert tone="success" title="Order placed">We'll send updates to your email.</x-ui.alert>
            <x-ui.alert tone="warning" title="Add your mobile number">The delivery partner needs it to reach you.</x-ui.alert>
            <x-ui.alert tone="danger" title="Payment not found">We couldn't match that UTR. Check the number in your UPI app and upload the screenshot again.</x-ui.alert>
        </div>
        <div class="flex flex-wrap gap-3">
            <x-ui.button variant="secondary" x-data x-on:click="$dispatch('toast', { message: 'Added to your bag', tone: 'success' })">Show success toast</x-ui.button>
            <x-ui.button variant="secondary" x-data x-on:click="$dispatch('toast', { message: 'Only 2 left in stock', tone: 'warning' })">Show warning toast</x-ui.button>
        </div>
        <div class="grid gap-3 md:grid-cols-2">
            <x-ui.card>
                <x-ui.empty-state icon="shopping-bag" title="Your bag is empty" :level="3">
                    Browse sweets, make-up and gifts, and add what you like.
                    <x-slot:action>
                        <x-ui.button :href="url('/')">Start shopping</x-ui.button>
                    </x-slot:action>
                </x-ui.empty-state>
            </x-ui.card>
            <x-ui.card class="flex flex-col gap-3" aria-busy="true">
                <p class="sr-only">Loading products</p>
                <x-ui.skeleton class="aspect-video w-full" />
                <x-ui.skeleton class="h-5 w-3/4" />
                <x-ui.skeleton class="h-5 w-1/3" />
            </x-ui.card>
        </div>
    </x-dev.section>

    <x-dev.section id="overlays" title="Overlays">
        <div class="flex flex-wrap items-start gap-3">
            <x-ui.button variant="secondary" x-data x-on:click="$dispatch('open-modal', 'demo-modal')">Open dialog</x-ui.button>
            <x-ui.button variant="secondary" icon="funnel" x-data x-on:click="$dispatch('open-modal', 'demo-sheet')">Open filter sheet</x-ui.button>
            <x-ui.dropdown align="start">
                <x-slot:trigger>
                    <x-ui.button variant="secondary" icon-end="chevron-down">Order actions</x-ui.button>
                </x-slot:trigger>
                <x-ui.dropdown-item icon="receipt-indian-rupee" href="#">Download invoice</x-ui.dropdown-item>
                <x-ui.dropdown-item icon="message-circle" href="#">Get help</x-ui.dropdown-item>
                <x-ui.dropdown-item icon="x" tone="danger">Cancel order</x-ui.dropdown-item>
            </x-ui.dropdown>
        </div>

        <x-ui.modal name="demo-modal" title="Cancel this order?" max-width="sm">
            <p>Order {{ $order['number'] }} hasn't been packed yet, so you can still cancel it. Items go back on the shelf.</p>
            <x-slot:footer>
                <x-ui.button variant="secondary" x-on:click="$dispatch('close-modal', 'demo-modal')">Keep order</x-ui.button>
                <x-ui.button variant="danger">Cancel order</x-ui.button>
            </x-slot:footer>
        </x-ui.modal>

        <x-ui.modal name="demo-sheet" title="Filter" sheet>
            <div class="flex flex-col gap-4">
                <fieldset class="flex flex-col gap-2">
                    <legend class="mb-1 text-sm font-semibold">Category</legend>
                    @foreach ($categories as $category)
                        <x-ui.checkbox :label="$category['name']" :name="'demo_filter_'.$category['slug']" />
                    @endforeach
                </fieldset>
                <x-ui.select label="Sort by" name="demo_sort" required :options="['popular' => 'Most popular', 'price_asc' => 'Price: low to high', 'price_desc' => 'Price: high to low', 'new' => 'Newest']" selected="popular" />
            </div>
            <x-slot:footer>
                <x-ui.button variant="secondary" x-on:click="$dispatch('close-modal', 'demo-sheet')">Clear</x-ui.button>
                <x-ui.button x-on:click="$dispatch('close-modal', 'demo-sheet')">Show 38 products</x-ui.button>
            </x-slot:footer>
        </x-ui.modal>
    </x-dev.section>

    <x-dev.section id="navigation" title="Navigation" description="The header, category bar and bottom bar on this page are the shop layout itself.">
        <x-ui.tabs label="Account sections demo">
            <x-ui.tab href="#navigation" icon="user">Profile</x-ui.tab>
            <x-ui.tab href="#navigation" icon="package" active>Orders</x-ui.tab>
            <x-ui.tab href="#navigation" icon="map-pin">Addresses</x-ui.tab>
        </x-ui.tabs>
        <x-ui.pagination :paginator="$paginator" />
    </x-dev.section>

    <x-dev.section id="prices" title="Prices" description="The shelf tag is the one signature shape. Gold tags only ever mean a discount.">
        <div class="flex flex-wrap items-center gap-4">
            <x-shop.price-tag :paise="34900" :mrp="49900" />
            <x-shop.price-tag :paise="52000" />
            <x-shop.price-tag :paise="149900" :mrp="179900" size="lg" />
            <x-shop.price-tag offer :paise="34900" :mrp="49900" />
            <x-shop.price :paise="19950" :mrp="22000" />
            <x-shop.price :paise="12500050" size="lg" />
        </div>
    </x-dev.section>

    <x-dev.section id="products" title="Products and categories">
        <div class="grid gap-3 sm:grid-cols-3">
            @foreach ($categories as $category)
                <x-shop.category-tile :name="$category['name']" :slug="$category['slug']" :count="$category['count']" url="#products" />
            @endforeach
        </div>
        <div class="grid grid-cols-2 gap-3 sm:grid-cols-3 lg:grid-cols-4">
            @foreach ($products as $product)
                <x-shop.product-card
                    :name="$product['name']"
                    :category="$product['category']"
                    :brand="$product['brand']"
                    :variant="$product['variant']"
                    :paise="$product['paise']"
                    :mrp="$product['mrp']"
                    :in-stock="$product['in_stock']"
                >
                    <x-slot:action>
                        @if ($product['in_stock'])
                            <x-ui.button size="sm" variant="secondary" icon="plus" block x-data x-on:click="$dispatch('toast', { message: 'Added to your bag', tone: 'success' })">Add</x-ui.button>
                        @else
                            <x-ui.button size="sm" variant="secondary" block disabled>Out of stock</x-ui.button>
                        @endif
                    </x-slot:action>
                </x-shop.product-card>
            @endforeach
        </div>
    </x-dev.section>

    <x-dev.section id="cart" title="Bag and checkout">
        <div class="grid items-start gap-5 lg:grid-cols-[1fr_22rem]">
            <x-ui.card padding="none" class="divide-y divide-line px-4">
                @foreach (array_slice($order['items'], 0, 2) as $item)
                    <x-shop.cart-line :name="$item['name']" :variant="$item['variant']" :paise="$item['paise']" :quantity="$item['quantity']" category="cosmetics" />
                @endforeach
            </x-ui.card>
            <x-ui.card class="flex flex-col gap-3">
                <h3 class="text-xl font-semibold">Order summary</h3>
                <dl class="figures grid grid-cols-[1fr_auto] gap-y-1.5">
                    <dt class="text-ink-soft">Items (MRP)</dt><dd class="text-end">{{ \App\Support\Money::format($order['mrp_total']) }}</dd>
                    <dt class="text-ink-soft">You save</dt><dd class="text-end text-pistachio">−{{ \App\Support\Money::format($order['discount']) }}</dd>
                    <dt class="text-ink-soft">Delivery</dt><dd class="text-end">{{ \App\Support\Money::format($order['delivery']) }}</dd>
                    <dt class="border-t border-line pt-2 font-display text-lg font-bold">To pay</dt>
                    <dd class="border-t border-line pt-2 text-end font-display text-lg font-bold">{{ \App\Support\Money::format($order['total']) }}</dd>
                </dl>
                <x-ui.button block size="lg">Checkout</x-ui.button>
            </x-ui.card>
        </div>

        <div class="grid items-start gap-5 lg:grid-cols-2">
            <x-ui.card>
                <x-slot:header>
                    <h3 class="text-lg font-semibold">Deliver to</h3>
                    <x-ui.button variant="ghost" size="sm" icon="pencil">Change</x-ui.button>
                </x-slot:header>
                <x-shop.address-card :label="$address['label']" :name="$address['name']" :phone="$address['phone']" :lines="$address['lines']" :pincode="$address['pincode']" is-default />
            </x-ui.card>

            <x-shop.upi-qr-panel :paise="$order['total']" vpa="demoshop@okaxis" payee="the shop's UPI account" :order-number="$order['number']">
                <x-shop.file-drop label="Payment screenshot" name="demo_proof" />
                <x-ui.input label="UTR number" name="demo_utr" inputmode="numeric" maxlength="12" required hint="12 digits, shown in your UPI app under transaction details." />
                <x-ui.button block size="lg" icon="upload">Submit payment details</x-ui.button>
            </x-shop.upi-qr-panel>
        </div>
    </x-dev.section>

    <x-dev.section id="tracking" title="Order tracking">
        <div class="grid items-start gap-5 md:grid-cols-2">
            <x-ui.card class="flex flex-col gap-4">
                <div class="flex items-center justify-between gap-2">
                    <h3 class="figures text-lg font-bold">{{ $order['number'] }}</h3>
                    <x-ui.status-pill tone="brand">Out for delivery</x-ui.status-pill>
                </div>
                <x-shop.order-tracker :steps="$steps" />
            </x-ui.card>
            <x-ui.card class="flex flex-col gap-4">
                <div class="flex items-center justify-between gap-2">
                    <h3 class="figures text-lg font-bold">ORD-10198</h3>
                    <x-ui.status-pill tone="danger">Delivery failed</x-ui.status-pill>
                </div>
                <x-shop.order-tracker failed :steps="[
                    ['label' => 'Order placed', 'time' => 'Yesterday, 6:05 pm', 'state' => 'done'],
                    ['label' => 'Out for delivery', 'time' => 'Today, 9:40 am', 'state' => 'done'],
                    ['label' => 'Delivery failed', 'time' => 'Today, 10:15 am', 'state' => 'current', 'note' => 'Nobody was home. We will call you to arrange another time.'],
                ]" />
            </x-ui.card>
        </div>
    </x-dev.section>

    <x-dev.section id="screens" title="Other screens" description="Full-page previews of the other layouts.">
        <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
            <x-ui.card class="flex flex-col gap-2">
                <h3 class="text-lg font-semibold">Delivery panel</h3>
                <p class="text-ink-soft">Phone-only layout for delivery partners.</p>
                <x-ui.link :href="route('dev.ui.delivery')">Open delivery preview</x-ui.link>
            </x-ui.card>
            <x-ui.card class="flex flex-col gap-2">
                <h3 class="text-lg font-semibold">Sign in</h3>
                <p class="text-ink-soft">Customer sign-in with Google.</p>
                <x-ui.link :href="route('dev.ui.sign-in')">Open sign-in preview</x-ui.link>
            </x-ui.card>
            <x-ui.card class="flex flex-col gap-2">
                <h3 class="text-lg font-semibold">Printing</h3>
                <p class="text-ink-soft">Defaults from shop settings: labels print on {{ $shop->labelFormat->label() }}, invoices on {{ $shop->invoiceFormat->label() }}.</p>
                <ul class="flex flex-col gap-1">
                    @foreach (\App\Enums\PrintFormat::forDocument(\App\Enums\PrintDocument::Label) as $format)
                        <li><x-ui.link :href="route('dev.ui.print', ['document' => 'label', 'format' => $format->value])">Label on {{ $format->label() }}</x-ui.link></li>
                    @endforeach
                    <li><x-ui.link :href="route('dev.ui.print', ['document' => 'label', 'payment' => 'upi'])">Prepaid label (default format)</x-ui.link></li>
                    @foreach (\App\Enums\PrintFormat::forDocument(\App\Enums\PrintDocument::Invoice) as $format)
                        <li><x-ui.link :href="route('dev.ui.print', ['document' => 'invoice', 'format' => $format->value])">Invoice on {{ $format->label() }}</x-ui.link></li>
                    @endforeach
                </ul>
            </x-ui.card>
        </div>
    </x-dev.section>

    <x-dev.section id="icons" title="Icons" description="Lucide icons (ISC licence), copied into resources/icons. Add a file there to add an icon.">
        <ul class="grid grid-cols-3 gap-2 sm:grid-cols-4 lg:grid-cols-6">
            @foreach ($icons as $icon)
                <li class="flex flex-col items-center gap-1 rounded-field border border-line bg-surface p-3 text-center">
                    <x-ui.icon :name="$icon" :size="24" />
                    <span class="text-xs break-all text-ink-soft">{{ $icon }}</span>
                </li>
            @endforeach
        </ul>
    </x-dev.section>
</x-layouts::shop>
