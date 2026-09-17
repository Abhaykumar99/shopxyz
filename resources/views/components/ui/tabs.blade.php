{{-- Navigation tabs (each tab is a link to its own page). Use <x-ui.tab> inside. --}}
@props(['label'])

<nav aria-label="{{ $label }}" {{ $attributes->class('-mx-4 overflow-x-auto px-4 sm:mx-0 sm:px-0') }}>
    <ul class="flex min-w-max gap-1 border-b border-line">
        {{ $slot }}
    </ul>
</nav>
