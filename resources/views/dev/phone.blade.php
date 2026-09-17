{{-- Phone-width preview frame for visual checks: /dev/ui/phone?src=/dev/ui&w=360 --}}
@php
    $src = str_starts_with((string) request('src'), '/') ? request('src') : '/dev/ui';
    $width = min(max((int) request('w', 360), 320), 768);
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
        style="display: block; border: 0; width: {{ $width }}px; height: {{ (int) request('h', 800) }}px; background: #fff;"
    ></iframe>
</body>
</html>
