{{--
    One admin-managed banner (ADR-024): a desktop hero slide, the phone hero or
    a promotion card. The theme picks the surface; everything else comes from
    the row, so nothing on the homepage is written into the template.

    `layout`: hero | promo
--}}
@props([
    'banner',
    'layout' => 'hero',
    'showcase' => null,
    'heading' => 'h2',
    'headingId' => null,
])

@php
    $surface = match ($banner->theme) {
        'ink' => 'bg-ink text-white',
        'accent' => 'bg-accent-tint text-ink',
        'mist' => 'bg-mist text-ink',
        default => 'bg-brand text-white',
    };
    $onDark = in_array($banner->theme, ['ink', 'brand'], true);
    $eyebrowClass = $onDark ? 'bg-accent text-ink' : 'bg-brand text-white';
    $bodyClass = $onDark ? 'text-white/85' : 'text-ink-soft';
    $primaryVariant = $onDark ? 'inverse' : 'primary';
    $secondaryVariant = $onDark ? 'inverse-outline' : 'secondary';
@endphp

<div {{ $attributes->class([
    'relative overflow-hidden',
    $surface,
    'grid items-center gap-8 px-4 py-9 sm:px-8 lg:px-12 lg:py-14' => $layout === 'hero',
    'flex flex-col gap-4 rounded-sheet p-6 lg:flex-row lg:items-center lg:justify-between lg:p-8' => $layout === 'promo',
    'lg:grid-cols-[1.2fr_1fr]' => $layout === 'hero' && (isset($showcase) || $banner->image_path),
]) }}>
    <div class="flex flex-col gap-4 lg:gap-5">
        @if ($banner->eyebrow)
            <span class="tag-shape inline-flex h-8 items-center self-start pe-3 text-sm font-semibold {{ $eyebrowClass }}">
                {{ $banner->eyebrow }}
            </span>
        @endif

        <{{ $heading }} @if ($headingId) id="{{ $headingId }}" @endif @class([
            'leading-[1.05]',
            'text-[2.4rem] sm:text-5xl lg:text-6xl' => $layout === 'hero',
            'text-2xl sm:text-3xl' => $layout === 'promo',
        ])>{{ $banner->title }}</{{ $heading }}>

        @if ($banner->subtitle)
            <p class="max-w-xl text-lg {{ $bodyClass }}">{{ $banner->subtitle }}</p>
        @endif

        @if ($banner->body)
            <p class="max-w-2xl {{ $bodyClass }}">{{ $banner->body }}</p>
        @endif

        @if ($banner->cta_label || $banner->secondary_cta_label)
            <div class="flex flex-wrap gap-2">
                @if ($banner->cta_label && $banner->cta_url)
                    <x-ui.button :href="$banner->cta_url" :size="$layout === 'hero' ? 'lg' : 'md'" :variant="$primaryVariant" wire:navigate>
                        {{ $banner->cta_label }}
                    </x-ui.button>
                @endif
                @if ($banner->secondary_cta_label && $banner->secondary_cta_url)
                    <x-ui.button :href="$banner->secondary_cta_url" :size="$layout === 'hero' ? 'lg' : 'md'" :variant="$secondaryVariant" wire:navigate>
                        {{ $banner->secondary_cta_label }}
                    </x-ui.button>
                @endif
            </div>
        @endif

        {{ $slot }}
    </div>

    @if ($banner->image_path)
        <img
            src="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($banner->image_path) }}"
            alt="{{ $banner->image_alt ?? '' }}"
            @class([
                'w-full rounded-sheet object-cover shadow-overlay',
                'max-h-[22rem]' => $layout === 'hero',
                'max-h-40 lg:max-w-xs' => $layout === 'promo',
            ])
            loading="lazy"
        >
    @elseif (isset($showcase))
        {{ $showcase }}
    @endif
</div>
