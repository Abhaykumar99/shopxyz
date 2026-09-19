@use('App\Support\Money')

{{-- The screenshot a customer sent with their UPI payment, shown for checking. --}}
<div class="fi-modal-content flex flex-col gap-4">
    <dl class="grid grid-cols-2 gap-3 text-sm">
        <div>
            <dt class="text-gray-500">Amount</dt>
            <dd class="font-semibold">{{ Money::format($payment->amount_paise) }}</dd>
        </div>
        <div>
            <dt class="text-gray-500">UTR</dt>
            <dd class="font-mono font-semibold">{{ $payment->utr }}</dd>
        </div>
        <div>
            <dt class="text-gray-500">Sent</dt>
            <dd>{{ $payment->submitted_at?->format('j M Y, g:i a') ?? '—' }}</dd>
        </div>
        <div>
            <dt class="text-gray-500">Customer</dt>
            <dd>{{ $payment->order?->customer?->name }}</dd>
        </div>
    </dl>

    @if ($payment->proof_path && \Illuminate\Support\Facades\Storage::disk('local')->exists($payment->proof_path))
        <img
            src="{{ route('admin.payment-proof', $payment) }}"
            alt="Payment screenshot for {{ $payment->order?->order_number }}"
            class="max-h-[60vh] w-full rounded-lg border border-gray-200 object-contain"
        >
    @else
        <p class="rounded-lg bg-gray-50 p-4 text-sm text-gray-600">
            The screenshot is not on this machine (sample data). In production it is streamed from private storage,
            never served from a public URL.
        </p>
    @endif
</div>
