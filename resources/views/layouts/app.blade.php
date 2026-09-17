{{-- Base HTML document. Every other layout wraps this one. --}}
@props([
    'title' => null,
    'description' => null,
    'noindex' => false,
    'ogType' => 'website',
])

@php
    $pageTitle = $title ? $title.' | '.$shop->name : $shop->name;
    $pageDescription = $description ?? $shop->tagline;
@endphp

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="theme-color" content="#fffafa">
    <title>{{ $pageTitle }}</title>
    <meta name="description" content="{{ $pageDescription }}">
    @if ($noindex)
        <meta name="robots" content="noindex, nofollow">
    @else
        <link rel="canonical" href="{{ url()->current() }}">
    @endif
    <meta property="og:site_name" content="{{ $shop->name }}">
    <meta property="og:type" content="{{ $ogType }}">
    <meta property="og:title" content="{{ $title ?? $shop->name }}">
    <meta property="og:description" content="{{ $pageDescription }}">
    <meta property="og:url" content="{{ url()->current() }}">
    <meta name="twitter:card" content="summary">

    @fonts
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
    {{ $head ?? '' }}
</head>
<body {{ $attributes->class('min-h-dvh') }}>
    <a href="#main" class="sr-only z-50 rounded-field bg-ink text-white focus:not-sr-only focus:fixed focus:top-2 focus:left-2 focus:px-4 focus:py-2">Skip to content</a>

    {{ $slot }}

    <x-ui.toaster />
    @livewireScripts
</body>
</html>
