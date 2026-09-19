{{-- Why this option's stock is what it is. Rows are only ever added. --}}
<div class="fi-modal-content">
    @if ($movements->isEmpty())
        <p class="text-sm text-gray-600">No movements recorded yet.</p>
    @else
        <ul class="divide-y divide-gray-100">
            @foreach ($movements as $movement)
                <li class="flex items-center justify-between gap-4 py-2 text-sm">
                    <div>
                        <p class="font-medium">{{ $movement->type->label() }}</p>
                        <p class="text-gray-500">
                            {{ $movement->created_at?->format('j M Y, g:i a') }}@if ($movement->note) · {{ $movement->note }}@endif
                        </p>
                    </div>
                    <div class="text-right">
                        <p class="font-semibold {{ $movement->quantity_change < 0 ? 'text-danger-600' : 'text-success-600' }}">
                            {{ $movement->quantity_change > 0 ? '+' : '' }}{{ $movement->quantity_change }}
                        </p>
                        <p class="text-gray-500">{{ $movement->stock_after }} left</p>
                    </div>
                </li>
            @endforeach
        </ul>
    @endif
</div>
