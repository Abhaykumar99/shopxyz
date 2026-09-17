{{-- Phone-width preview frame for visual checks: /dev/ui/phone?src=/dev/ui&w=360 --}}
@php
    $requested = (string) request('src');
    $src = preg_match('#^/(?![/\\\\])#', $requested) ? $requested : '/dev/ui';
    $width = min(max((int) request('w', 360), 320), 768);
    $height = min(max((int) request('h', 800), 400), 20000);
@endphp
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="robots" content="noindex, nofollow">
    <title>Phone preview</title>
</head>
<body style="margin: 0; background: #2b1631;">
    <iframe
        src="{{ url($src) }}"
        title="Phone preview"
        style="display: block; border: 0; width: {{ $width }}px; height: {{ $height }}px; background: #fff;"
    ></iframe>
</body>
</html>
