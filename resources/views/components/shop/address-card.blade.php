@props([
    'label' => null,
    'name',
    'phone',
    'lines' => [],
    'pincode',
    'isDefault' => false,
])

<div {{ $attributes->class('flex flex-col gap-1 text-base') }}>
    <div class="flex flex-wrap items-center gap-2">
        <p class="font-semibold text-ink">{{ $name }}</p>
        @if ($label)
            <x-ui.badge>{{ $label }}</x-ui.badge>
        @endif
        @if ($isDefault)
            <x-ui.badge tone="brand">Default</x-ui.badge>
        @endif
    </div>
    <address class="text-ink-soft not-italic">
        @foreach (array_filter($lines) as $line)
            {{ $line }}<br>
        @endforeach
        <span class="figures">{{ $pincode }}</span>
    </address>
    <p class="figures flex items-center gap-1.5 text-ink-soft">
        <x-ui.icon name="phone" :size="16" />
        {{ $phone }}
    </p>
    {{ $slot }}
</div>
