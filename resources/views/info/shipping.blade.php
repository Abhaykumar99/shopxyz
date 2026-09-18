@use('App\Support\Money')

<x-shop.info-page :title="$title" :pages="$pages" :current="$current">
    <h2>Where we deliver</h2>
    <p>We deliver within {{ $shop->deliveryArea ?? 'our local area' }} using our own delivery team. Checkout tells you straight away if we can reach your pincode.</p>
    <h2>Delivery times</h2>
    <p>{{ $shop->deliveryEta ?? 'Most orders arrive the same or next day.' }} Personalised gifts may take up to 2 days to prepare.</p>
    <h2>Delivery charges</h2>
    <ul>
        @if ($shop->deliveryChargePaise)
            <li>{{ Money::format($shop->deliveryChargePaise) }} per order.</li>
        @endif
        @if ($shop->freeDeliveryAbovePaise)
            <li>Free delivery on orders of {{ Money::format($shop->freeDeliveryAbovePaise) }} or more.</li>
        @endif
        @if ($shop->minOrderPaise)
            <li>The minimum order value is {{ Money::format($shop->minOrderPaise) }}.</li>
        @endif
    </ul>
    <h2>Receiving your order</h2>
    <p>When your order is out for delivery, your order page shows a 6-digit delivery code. Share it with the delivery partner only once you have your parcel. For cash on delivery, please keep the exact amount ready.</p>
    <h2>If we miss you</h2>
    <p>If nobody is available, the delivery partner will call you. We'll arrange another time at no extra charge.</p>
</x-shop.info-page>
