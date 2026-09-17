@props([
    'method',
    'name' => 'payment_method',
])

@php
    [$title, $description, $icon] = match ($method) {
        'upi' => ['Pay by UPI', 'Scan our QR with any UPI app, then upload the payment screenshot.', 'qr-code'],
        default => ['Cash on delivery', 'Pay in cash when your order arrives.', 'banknote'],
    };
@endphp

<x-ui.radio-card :name="$name" :value="$method" :title="$title" :description="$description" :icon="$icon" :attributes="$attributes" />
