@props(['href'])

<a href="{{ $href }}" {{ $attributes->class('font-medium text-berry underline decoration-berry/40 underline-offset-4 hover:decoration-berry') }}>{{ $slot }}</a>
