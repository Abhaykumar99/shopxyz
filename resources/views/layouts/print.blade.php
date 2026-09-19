{{--
    Printable documents (invoice, parcel label) in the selected paper format
    (ADR-014). On screen it shows a paper preview with a toolbar; when printed
    only the sheet is output at the exact @page size.
--}}
@props([
    'title',
    'document',
    'format',
    'sheets' => 1,
])

@php
    /** @var \App\Enums\PrintDocument $document */
    /** @var \App\Enums\PrintFormat $format */
    ['width' => $width, 'height' => $height] = $format->dimensions();
    $margin = $format === \App\Enums\PrintFormat::Thermal4x6 ? 3 : 10;
@endphp

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <title>{{ $title }} | {{ $shop->name }}</title>
    @fonts
    @vite(['resources/css/app.css'])
    <style>
        @page { size: {{ $format->cssPageSize() }}; margin: 0; }
        .print-sheet { width: {{ $width }}mm; min-height: {{ $height }}mm; padding: {{ $margin }}mm; }
        @media print {
            html, body { background: #fff; }
            .print-sheet { box-shadow: none; margin: 0; height: {{ $height }}mm; overflow: hidden; }
            /* One sheet per page, with nothing spilling onto the next. */
            .print-sheet + .print-sheet { break-before: page; }
        }
    </style>
</head>
<body class="bg-mist text-ink print:bg-white" data-print-format="{{ $format->value }}">
    <header class="sticky top-0 z-10 border-b border-line bg-surface print:hidden">
        <form method="get" class="mx-auto flex max-w-3xl flex-wrap items-end gap-3 px-4 py-3">
            @foreach (request()->except('format') as $key => $value)
                @if (is_string($value))
                    <input type="hidden" name="{{ $key }}" value="{{ $value }}">
                @endif
            @endforeach
            <div class="me-auto flex flex-col">
                <h1 class="text-lg font-bold">{{ $title }}</h1>
                <p class="text-sm text-ink-soft">
                    {{ $sheets > 1 ? $sheets.' '.\Illuminate\Support\Str::plural($document->label(), $sheets) : $document->label() }}
                    on {{ $format->label() }}{{ $sheets > 1 ? ', one per page' : '' }}
                </p>
            </div>
            <x-ui.select
                name="format"
                label="Paper"
                required
                :selected="$format->value"
                :options="collect(\App\Enums\PrintFormat::forDocument($document))->mapWithKeys(fn ($f) => [$f->value => $f->label()])->all()"
                onchange="this.form.submit()"
                class="w-48"
            />
            <noscript><x-ui.button type="submit" variant="secondary">Apply</x-ui.button></noscript>
            <x-ui.button icon="printer" onclick="window.print()">Print</x-ui.button>
        </form>
    </header>

    {{-- Focusable so keyboard users can scroll a sheet wider than the screen. --}}
    <main tabindex="0" aria-label="Print preview" class="flex flex-col items-center gap-6 overflow-x-auto p-4 sm:p-8 print:block print:gap-0 print:p-0">
        {{ $slot }}
    </main>
</body>
</html>
