@props(['href'])

<a href="{{ $href }}" {{ $attributes->class('font-medium text-brand underline decoration-brand/40 underline-offset-4 hover:decoration-brand') }}>{{ $slot }}</a>
